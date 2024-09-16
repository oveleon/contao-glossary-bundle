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
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(GlossaryListController::TYPE, category: 'glossaries', template: 'mod_glossary')]
class GlossaryListController extends AbstractGlossaryController
{
    public const TYPE = 'glossary';

    private FragmentTemplate|Template $template;

    private ModuleModel $model;

    private array $archiveIds = [];

    protected function getResponse(FragmentTemplate|Template $template, ModuleModel $model, Request $request): Response
    {
        $this->template = $template;

        if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $this->template = new BackendTemplate('be_wildcard');
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
        $this->template->empty = $GLOBALS['TL_LANG']['MSC']['emptyGlossaryList'];

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
            $this->model->id,
            (bool) $this->model->glossary_singleGroup,
            (bool) $this->model->glossary_hideEmptyGroups,
            (bool) $this->model->glossary_utf8Transliteration,
            (bool) $this->model->glossary_quickLinks,
        );
    }

    private function parse(): void
    {
        $this->archiveIds = $this->sortOutProtected(StringUtil::deserialize($this->model->glossary_archives, true));

        // Show the glossary reader if an item has been selected
        if ($this->model->glossary_readerModule > 0 && (isset($_GET['items']) || ($this->useAutoItem() && isset($_GET['auto_item']))))
        {
            $this->template->content = Controller::getFrontendModule($this->model->glossary_readerModule); // this->strColumn
        }

        // Tag glossary archives
        if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
        {
            $responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(array_map(static fn ($id) => 'contao.db.tl_glossary.'.$id, $this->archiveIds));
        }

        $this->renderGlossaryList();
    }
}
