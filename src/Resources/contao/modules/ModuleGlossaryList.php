<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\CoreBundle\Exception\PageNotFoundException;
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
			/** @var BackendTemplate|object $objTemplate */
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

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$limit = null;
		$offset = (int) $this->skipFirst;

		// Maximum number of items
		if ($this->numberOfItems > 0)
		{
			$limit = $this->numberOfItems;
		}

		$this->Template->articles = array();
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyGlossaryList'];

		// Get the total number of items
		$intTotal = $this->countItems($this->glossary_archives);

		if ($intTotal < 1)
		{
			return;
		}

		$total = $intTotal - $offset;

		// Split the results
		if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage))
		{
			// Adjust the overall limit
			if (isset($limit))
			{
				$total = min($limit, $total);
			}

			// Get the current page
			$id = 'page_n' . $this->id;
			$page = (\Input::get($id) !== null) ? \Input::get($id) : 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
			{
				throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));
			}

			// Set limit and offset
			$limit = $this->perPage;
			$offset += (max($page, 1) - 1) * $this->perPage;
			$skip = (int) $this->skipFirst;

			// Overall limit
			if ($offset + $limit > $total + $skip)
			{
				$limit = $total + $skip - $offset;
			}

			// Add the pagination menu
			$objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$objArticles = $this->fetchItems($this->glossary_archives, ($limit ?: 0), $offset);

		// Add the articles
		if ($objArticles !== null)
		{
			$this->Template->articles = $this->parseArticles($objArticles);
		}
	}

	/**
	 * Count the total matching items
	 *
	 * @param array   $glossaryArchives
	 *
	 * @return integer
	 */
	protected function countItems($glossaryArchives)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['glossaryListCountItems']) && \is_array($GLOBALS['TL_HOOKS']['glossaryListCountItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['glossaryListCountItems'] as $callback)
			{
				if (($intResult = \System::importStatic($callback[0])->{$callback[1]}($glossaryArchives, $this)) === false)
				{
					continue;
				}

				if (\is_int($intResult))
				{
					return $intResult;
				}
			}
		}

		return GlossaryItemModel::countPublishedByPids($glossaryArchives);
	}

	/**
	 * Fetch the matching items
	 *
	 * @param array   $glossaryArchives
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return \Model\Collection|GlossaryItemModel|null
	 */
	protected function fetchItems($glossaryArchives, $limit, $offset)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['glossaryListFetchItems']) && \is_array($GLOBALS['TL_HOOKS']['glossaryListFetchItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['glossaryListFetchItems'] as $callback)
			{
				if (($objCollection = \System::importStatic($callback[0])->{$callback[1]}($glossaryArchives, $limit, $offset, $this)) === false)
				{
					continue;
				}

				if ($objCollection === null || $objCollection instanceof \Model\Collection)
				{
					return $objCollection;
				}
			}
		}

		return GlossaryItemModel::findPublishedByPids($glossaryArchives, $limit, $offset);
	}
}
