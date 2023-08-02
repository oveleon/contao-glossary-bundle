<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @package     contao-glossary-bundle
 * @license     AGPL-3.0
 * @author      Fabian Ekert        <https://github.com/eki89>
 * @author      Sebastian Zoglowek  <https://github.com/zoglo>
 * @copyright   Oveleon             <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;

/**
 * Front end module "glossary list".
 *
 * @property array  $glossary_archives
 * @property int    $glossary_readerModule
 * @property bool   $glossary_hideEmptyGroups
 * @property bool   $glossary_singleGroup
 * @property bool   $glossary_utf8Transliteration
 * @property bool   $glossary_quickLinks
 * @property string $glossary_letter
 *
 * @author Fabian Ekert <https://github.com/eki89>
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
class ModuleGlossaryList extends ModuleGlossary
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_glossary';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '. $GLOBALS['TL_LANG']['FMD']['glossary'][0] .' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

            return $objTemplate->parse();
        }

        $this->glossary_archives = $this->sortOutProtected(StringUtil::deserialize($this->glossary_archives));

        // Return if there are no glossaries
        if (empty($this->glossary_archives) || !\is_array($this->glossary_archives))
        {
            return '';
        }

        // Show the glossary reader if an item has been selected
        if ($this->glossary_readerModule > 0 && (isset($_GET['items']) || (Config::get('useAutoItem') && isset($_GET['auto_item']))))
        {
            return $this->getFrontendModule($this->glossary_readerModule, $this->strColumn);
        }

        // Tag glossary archives
        if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
        {
            $responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(array_map(static fn ($id) => 'contao.db.tl_glossary.'.$id, $this->glossary_archives));
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyGlossaryList'];

        if ($this->glossary_singleGroup)
        {
            // Get the current page
            $id = 'page_g'.$this->id;
            $letter = Input::get($id) ?? $this->glossary_letter;

            $objGlossaryItems = GlossaryItemModel::findPublishedByLetterAndPids($letter, $this->glossary_archives);
        }
        else
        {
            $objGlossaryItems = GlossaryItemModel::findPublishedByPids($this->glossary_archives);
        }

        $this->parseGlossaryGroups($objGlossaryItems, $this->Template, (bool) $this->glossary_singleGroup, (bool) $this->glossary_hideEmptyGroups, (bool) $this->glossary_utf8Transliteration, (bool) $this->glossary_quickLinks);
    }
}
