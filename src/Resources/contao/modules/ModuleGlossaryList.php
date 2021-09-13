<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\System;
use Contao\StringUtil;
use Patchwork\Utf8;

/**
 * Front end module "glossary list".
 *
 * @property array    $glossary_archives
 * @property integer  $glossary_readerModule
 * @property boolean  $glossary_hideEmptyGroups
 * @property boolean  $glossary_singleGroup
 * @property string   $glossary_letter
 *
 * @author Fabian Ekert <https://github.com/eki89>
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
class ModuleGlossaryList extends ModuleGlossary
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_glossary';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['glossary'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

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
			$responseTagger->addTags(array_map(static function ($id) { return 'contao.db.tl_glossary.' . $id; }, $this->glossary_archives));
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyGlossaryList'];

	    if ($this->glossary_singleGroup)
        {
            // Get the current page
            $id = 'page_g' . $this->id;
            $letter = \Input::get($id) ?? $this->glossary_letter;

	        $objGlossaryItems = GlossaryItemModel::findPublishedByLetterAndPids($letter, $this->glossary_archives);
        }
	    else
        {
	        $objGlossaryItems = GlossaryItemModel::findPublishedByPids($this->glossary_archives);
        }

		$this->parseGlossaryGroups($objGlossaryItems, $this->Template, $this->glossary_singleGroup, $this->glossary_hideEmptyGroups);
	}
}
