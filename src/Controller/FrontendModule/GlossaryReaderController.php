<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @package     contao-glossary-bundle
 * @license     AGPL-3.0
 * @author      Sebastian Zoglowek    <https://github.com/zoglo>
 * @author      Fabian Ekert          <https://github.com/eki89>
 * @author      Daniele Sciannimanica <https://github.com/doishub>
 * @copyright   Oveleon               <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoGlossaryBundle\Controller\FrontendModule;

use Contao\BackendTemplate;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Environment;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Utils\GlossaryTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Front end module "glossary reader".
 *
 * @property array $glossary_archives
 */
#[AsFrontendModule(GlossaryReaderController::TYPE, category: 'glossaries', template: 'mod_glossaryreader')]
class GlossaryReaderController extends AbstractFrontendModuleController
{
    use GlossaryTrait;

    public const TYPE = 'glossaryreader';

    private FragmentTemplate|Template $template;

    private ModuleModel $model;

    private array $archiveIds = [];

    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
    ) {
    }

    protected function getResponse(FragmentTemplate|Template $template, ModuleModel $model, Request $request): Response
    {
        $this->template = $template;

        if ($this->scopeMatcher->isBackendRequest($request))
        {
            $this->template = new BackendTemplate('be_wildcard');
        }
        else
        {
            $this->initialize();

            if (empty($this->archiveIds = StringUtil::deserialize($model->glossary_archives, true)))
            {
                throw new InternalServerErrorException('The publication reader ID '.$model->id.' has no archives specified.');
            }

            $this->model = $model;

            $this->parse();
        }

        return $this->template->getResponse();
    }

    private function setResponseData(GlossaryItemModel $glossaryItem): void
    {
        $responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

        if ($responseContext && $responseContext->has(HtmlHeadBag::class))
        {
            /** @var HtmlHeadBag $htmlHeadBag */
            $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
            $htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

            if ($glossaryItem->pageTitle)
            {
                $htmlHeadBag->setTitle($glossaryItem->pageTitle); // Already stored decoded
            }
            elseif ($glossaryItem->keyword)
            {
                $htmlHeadBag->setTitle($htmlDecoder->inputEncodedToPlainText($glossaryItem->keyword));
            }

            if ($glossaryItem->description)
            {
                $htmlHeadBag->setMetaDescription($htmlDecoder->inputEncodedToPlainText($glossaryItem->description));
            }
            elseif ($glossaryItem->teaser)
            {
                $htmlHeadBag->setMetaDescription($htmlDecoder->htmlToPlainText($glossaryItem->teaser));
            }

            if ($glossaryItem->robots)
            {
                $htmlHeadBag->setMetaRobots($glossaryItem->robots);
            }
        }
    }

    private function initialize(): Response|null
    {
        $auto_item = Input::get('auto_item');

        if (
            version_compare(ContaoCoreBundle::getVersion(), '5', '<')
            && !isset($_GET['items'])
            && isset($_GET['auto_item'])
            && $this->useAutoItem()
        ) {
            // Set the item from the auto_item parameter - Contao 4.13 BC
            Input::setGet('items', Input::get('auto_item'));
            $auto_item = Input::get('items');
        }

        if (null === $auto_item)
        {
            return $this->template->getResponse();
        }

        return null;
    }

    private function parse(): void
    {
        $this->template->referer = 'javascript:history.go(-1)';
        $this->template->back = $this->translator->trans('MSC.goBack', [], 'contao_default');

        if (!($objGlossaryItem = GlossaryItemModel::findPublishedByParentAndIdOrAlias(Input::get('auto_item'), $this->archiveIds)) instanceof GlossaryItemModel)
        {
            throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
        }

        $arrGlossaryItem = $this->parseGlossaryItem(
            $objGlossaryItem,
            $this->model->glossary_template ?: 'glossary_full',
            $this->model->imgSize,
        );

        $this->template->glossaryentry = $arrGlossaryItem;

        $this->setResponseData($objGlossaryItem);
    }
}
