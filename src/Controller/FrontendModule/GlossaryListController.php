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
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Utils\GlossaryTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(GlossaryListController::TYPE, category: 'glossaries', template: 'mod_glossary')]
class GlossaryListController extends AbstractFrontendModuleController
{
    use GlossaryTrait;

    public const TYPE = 'glossary';

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
        elseif ($model->glossary_readerModule > 0 && (isset($_GET['items']) || (isset($_GET['auto_item']))))
        {
            // Show the glossary reader if an item has been selected
            return new Response(Controller::getFrontendModule($model->glossary_readerModule, $template->inColumn));
        }
        else
        {
            $this->model = $model;

            $this->parse();
        }

        return $this->template->getResponse();
    }

    private function renderGlossaryList(): void
    {
        $this->template->empty = $this->translator->trans('MSC.emptyGlossaryList', [], 'contao_default');

        if ($this->model->glossary_singleGroup)
        {
            // Get the current page
            $id = 'page_g'.$this->model->id;
            $letter = Input::get($id) ?? $this->model->glossary_letter;

            $glossaryItems = GlossaryItemModel::findPublishedByLetterAndPids($letter, $this->archiveIds);
        }
        else
        {
            $glossaryItems = GlossaryItemModel::findPublishedByPids($this->archiveIds);
        }

        $this->parseGlossaryGroups(
            $glossaryItems,
            $this->template,
            $this->archiveIds,
            $this->model,
        );
    }

    private function parse(): void
    {
        $this->archiveIds = $this->sortOutProtected(StringUtil::deserialize($this->model->glossary_archives, true));

        // Tag glossary archives
        if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
        {
            $responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(array_map(static fn ($id): string => 'contao.db.tl_glossary.'.$id, $this->archiveIds));
        }

        $this->renderGlossaryList();
    }
}
