<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\Config;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\FrontendTemplate;
use Model\Collection;
use Patchwork\Utf8;

/**
 * Front end module "glossary list".
 *
 * @property array  $glossary_archives
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class ModuleGlossaryList extends ModuleGlossary
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_glossarylist';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['glossarylist'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		$this->glossary_archives = $this->sortOutProtected(\StringUtil::deserialize($this->glossary_archives, true));

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

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$objItems = GlossaryItemModel::findPublishedByPids($this->glossary_archives);

		if ($objItems === null)
        {
            return '';
        }

        $arrLetterRange = range('A', 'Z');
		$availableGroups = array();
		$arrGroups = array();

		foreach ($arrLetterRange as $letter)
        {
            $availableGroups[$letter] = false;
        }

		while ($objItems->next())
        {
            $group = strtoupper(substr($objItems->keyword, 0, 1));

            $arrItem = $objItems->row();

            $arrItem['link'] = $this->generateLink($objItems->keyword, $objItems);

            // Clean the RTE output
            if ($objItems->teaser != '')
            {
                $arrItem['teaser'] = \StringUtil::encodeEmail(\StringUtil::toHtml5($objItems->teaser));
            }

            $arrGroups[$group][] = $arrItem;

            // Flag group as available
            $availableGroups[$group] = true;
        }

		$this->Template->availableGroups = $availableGroups;
		$this->Template->groups = $arrGroups;
	}

    /**
     * Generate a link and return it as string
     *
     * @param string            $strLink
     * @param GlossaryItemModel $objItem
     *
     * @return string
     */
    protected function generateLink($strLink, $objItem)
    {
        // Internal link
        if ($objItem->source != 'external')
        {
            return sprintf(
                '<a href="%s" title="%s" itemprop="url"><span itemprop="headline">%s</span></a>',
                GlossaryItem::generateUrl($objItem),
                \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objItem->keyword), true),
                $strLink
            );
        }

        // Encode e-mail addresses
        if (0 === strncmp($objItem->url, 'mailto:', 7))
        {
            $strArticleUrl = \StringUtil::encodeEmail($objItem->url);
        }

        // Ampersand URIs
        else
        {
            $strArticleUrl = ampersand($objItem->url);
        }

        // External link
        return sprintf(
            '<a href="%s" title="%s"%s itemprop="url"><span itemprop="headline">%s</span></a>',
            $strArticleUrl,
            \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['open'], $strArticleUrl)),
            ($objItem->target ? ' target="_blank"' : ''),
            $strLink
        );
    }
}
