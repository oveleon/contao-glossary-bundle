<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\Config;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Database;
use Contao\FrontendTemplate;
use Model\Collection;
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
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['glossary'][0]) . ' ###';
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
	    $objItems = null;

	    if ($this->glossary_singleGroup)
        {
            // Get the current page
            $id = 'page_n' . $this->id;
            $letter = \Input::get($id) ?? $this->glossary_letter;

            $objItems = GlossaryItemModel::findPublishedByLetterAndPids($letter, $this->glossary_archives);
        }
	    else
        {
            $objItems = GlossaryItemModel::findPublishedByPids($this->glossary_archives);
        }

        $arrLetterRange = range('A', 'Z');
		$availableGroups = array();
		$arrGroups = array();

		foreach ($arrLetterRange as $letter)
        {
            $availableGroups[$letter] = false;
        }

        if ($objItems !== null)
        {
		    while ($objItems->next())
            {
                $group = Utf8::strtoupper(mb_substr($objItems->keyword, 0, 1, 'UTF-8'));

                $arrItem = $objItems->row();

                $arrItem['id'] = 'item'.$this->id.'_'.$objItems->id;
                $arrItem['linkHeadline'] = $this->generateLink($objItems->keyword, $objItems);
                $arrItem['more'] = $this->generateLink($GLOBALS['TL_LANG']['MSC']['more'], $objItems);
                $arrItem['item'] = sprintf('<a href="%s#item%s_%s">%s</a>', $this->Environment->get('request'), $this->id, $objItems->id, $objItems->keyword);

                // Clean the RTE output
                if ($objItems->teaser != '')
                {
                    $arrItem['teaser'] = \StringUtil::encodeEmail(\StringUtil::toHtml5($objItems->teaser));
                }

                $arrGroups[$group]['id'] = 'group'.$this->id.'_'.$group;
                $arrGroups[$group]['items'][] = $arrItem;

                // Flag group as available
                $availableGroups[$group] = true;
            }
        }

        $this->generateGroupAnchors($availableGroups);

        $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyGlossaryList'];
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

    /**
     * Generate group anchor links and return them as array
     *
     * @param array $availableGroups
     */
    protected function generateGroupAnchors(&$availableGroups)
    {
        if ($this->glossary_singleGroup)
        {
            $id = 'page_n' . $this->id;
            $letter = \Input::get($id) ?? $this->glossary_letter;

            $objItems = $this->Database->prepare("SELECT DISTINCT letter FROM tl_glossary_item WHERE pid IN(" . implode(',', array_map('\intval', $this->glossary_archives)) . ") AND published='1'")->execute();

            while ($objItems->next())
            {
                $item = sprintf('<a href="%s?page_n%s=%s">%s</a>', explode('?', $this->Environment->get('request'), 2)[0], $this->id, $objItems->letter, $objItems->letter);

                $availableGroups[$objItems->letter] = array
                (
                    'item' => $item,
                    'class' => $objItems->letter == $letter ? 'active selected' : 'active'
                );
            }

            foreach ($availableGroups as $group => $available)
            {
                if (!$available)
                {
                    if ($this->glossary_hideEmptyGroups)
                    {
                        unset($availableGroups[$group]);

                        continue;
                    }

                    $item = sprintf('<span>%s</span>', $group);

                    $availableGroups[$group] = array
                    (
                        'item' => $item,
                        'class' => 'inactive'
                    );
                }
            }
        }
        else
        {
            foreach ($availableGroups as $group => $available)
            {
                if ($available)
                {
                    $item = sprintf('<a href="%s#group%s_%s">%s</a>', $this->Environment->get('request'), $this->id, $group, $group);
                }
                else
                {
                    if ($this->glossary_hideEmptyGroups)
                    {
                        unset($availableGroups[$group]);

                        continue;
                    }

                    $item = sprintf('<span>%s</span>', $group);
                }

                $availableGroups[$group] = array
                (
                    'item' => $item,
                    'class' => $available ? 'active' : 'inactive'
                );
            }
        }
    }
}
