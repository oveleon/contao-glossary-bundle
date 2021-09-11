<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\ArticleModel;
use Contao\Config;
use Contao\ContentModel;
use Contao\Environment;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Patchwork\Utf8;

/**
 * Parent class for glossary modules.
 *
 * @property string $glossary_template
 * @property mixed $glossary_metaFields
 *
 * @author Fabian Ekert <https://github.com/eki89>
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
abstract class ModuleGlossary extends \Module
{
	/**
	 * Sort out protected glossaries
	 *
	 * @param array $arrGlossaries
	 *
	 * @return array
	 */
	protected function sortOutProtected($arrGlossaries)
	{
		if (empty($arrGlossaries) || !\is_array($arrGlossaries))
		{
			return $arrGlossaries;
		}

		$this->import(FrontendUser::class, 'User');
		$objGlossary = GlossaryModel::findMultipleByIds($arrGlossaries);
		$arrGlossaries = array();

		if ($objGlossary !== null)
		{
			$blnFeUserLoggedIn = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();

			while ($objGlossary->next())
			{
				if ($objGlossary->protected)
				{
					if (!$blnFeUserLoggedIn || !\is_array($this->User->groups))
					{
						continue;
					}

					$groups = StringUtil::deserialize($objGlossary->groups);

					if (empty($groups) || !\is_array($groups) || !\count(array_intersect($groups, $this->User->groups)))
					{
						continue;
					}
				}

				$arrGlossaries[] = $objGlossary->id;
			}
		}

		return $arrGlossaries;
	}

	/**
	 * Parse a glossary item and return it as string
	 *
	 * @param GlossaryItemModel $objGlossaryItem
	 * @param string            $strClass
	 *
	 * @return string
	 */
	protected function parseGlossaryItem($objGlossaryItem, $strClass='')
	{

		$objTemplate = new FrontendTemplate($this->glossary_template ?: 'glossary_simple');
		$objTemplate->setData($objGlossaryItem->row());

		if ($objGlossaryItem->cssClass)
		{
			$strClass = ' ' . $objGlossaryItem->cssClass . $strClass;
		}

		$objTemplate->class = $strClass;
		$objTemplate->headline = $objGlossaryItem->keyword;
		$objTemplate->subHeadline = $objGlossaryItem->subheadline;
		$objTemplate->hasSubHeadline = $objGlossaryItem->subheadline ? true : false;
		$objTemplate->linkHeadline = $this->generateLink($objGlossaryItem->keyword, $objGlossaryItem);
		$objTemplate->more = $this->generateLink($GLOBALS['TL_LANG']['MSC']['more'], $objGlossaryItem, true);
		$objTemplate->text = '';
		$objTemplate->hasText = false;
		$objTemplate->hasTeaser = false;

		if($objGlossaryItem->teaser)
		{
			$objTemplate->hasTeaser = true;
			$objTemplate->teaser = StringUtil::toHtml5($objGlossaryItem->teaser);
			$objTemplate->teaser = StringUtil::encodeEmail($objTemplate->teaser);
		}

		// Display the "read more" button for external/article links
		if ($objGlossaryItem->source != 'default')
		{
			$objTemplate->text = true;
			$objTemplate->hasText = true;
		}

		// Compile the glossary item
		else
		{
			$id = $objGlossaryItem->id;

			$objTemplate->text = function () use ($id)
			{
				$strText = '';
				$objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_glossary_item');

				if ($objElement !== null)
				{
					while ($objElement->next())
					{
						$strText .= $this->getContentElement($objElement->current());
					}
				}

				return $strText;
			};

			$objTemplate->hasText = static function () use ($objGlossaryItem)
			{
				return ContentModel::countPublishedByPidAndTable($objGlossaryItem->id, 'tl_glossary_item') > 0;
			};
		}

		$objTemplate->addImage = false;

		// Add an image
		if ($objGlossaryItem->addImage && $objGlossaryItem->singleSRC)
		{
			$objModel = FilesModel::findByUuid($objGlossaryItem->singleSRC);

			if ($objModel !== null && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objModel->path))
			{
				// Do not override the field now that we have a model registry
				$arrGlossaryItem = $objGlossaryItem->row();

				// Override the default image size
				if ($this->imgSize)
				{
					$size = StringUtil::deserialize($this->imgSize);

					if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
					{
						$arrGlossaryItem['size'] = $this->imgSize;
					}
				}

				$arrGlossaryItem['singleSRC'] = $objModel->path;
				$this->addImageToTemplate($objTemplate, $arrGlossaryItem, null, null, $objModel);

				// Link to the glossary item if no image link has been defined
				if (!$objTemplate->fullsize && !$objTemplate->imageUrl)
				{
					// Unset the image title attribute
					$picture = $objTemplate->picture;
					unset($picture['title']);
					$objTemplate->picture = $picture;

					// Link to the news article
					$objTemplate->href = $objTemplate->link;
					$objTemplate->linkTitle = StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objGlossaryItem->keyword), true);

					// If the external link is opened in a new window, open the image link in a new window, too
					if ($objTemplate->source == 'external' && $objTemplate->target && strpos($objTemplate->attributes, 'target="_blank"') === false)
					{
						$objTemplate->attributes .= ' target="_blank"';
					}
				}
			}
		}

		// ToDo: Check and test tagging
		// Tag the glossary item
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags(array('contao.db.tl_glossary_item.' . $objGlossaryItem->id));
			//$responseTagger->addTags(array('contao.db.tl_glossary.' . $objGlossaryItem->pid));
		}

		return $objTemplate->parse();
	}

	/**
	 * @param GlossaryItemModel $objGlossaryItems
	 * @param object            $objTemplate
	 * @param boolean           $blnSingleGroup
	 * @param boolean           $blnHideEmptyGroups
	 *
	 * @return void
	 */
	protected function parseGlossaryGroups($objGlossaryItems, &$objTemplate, $blnSingleGroup=false, $blnHideEmptyGroups=false)
	{
		$availableGroups = array();

		if ($blnSingleGroup)
		{
			// Fetch all glossary items to generate pagination links
			$objAvailableGlossaryItems = GlossaryItemModel::findPublishedByPids($this->glossary_archives);

			foreach ($objAvailableGlossaryItems as $item)
			{
				// Transliterate letters to valid Ascii
				$itemGroup = Utf8::toAscii($item->letter);

				$availableGroups[$itemGroup] = array
				(
					'item' => $this->generateGroupAnchorLink($itemGroup, $blnSingleGroup),
					'class' => 'active'
				);
			}
		}
		elseif (!$blnHideEmptyGroups)
		{
			$arrLetterRange = range('A', 'Z');

			foreach ($arrLetterRange as $letter)
			{
				$availableGroups[$letter] = array
				(
					'item' => sprintf('<span>%s</span>', $letter),
					'class' => 'inactive'
				);
			}
		}

		$objTemplate->availableGroups = $availableGroups;

		if ($objGlossaryItems === null)
		{
			return;
		}

		$arrGlossaryGroups = array();

		$limit = $objGlossaryItems->count();

		if ($limit < 1)
		{
			return;
		}

		$uuids = array();

		foreach ($objGlossaryItems as $objGlossaryItem)
		{
			if ($objGlossaryItem->addImage && $objGlossaryItem->singleSRC)
			{
				$uuids[] = $objGlossaryItem->singleSRC;
			}
		}

		// Preload all images in one query so they are loaded into the model registry
		FilesModel::findMultipleByUuids($uuids);

		foreach ($objGlossaryItems as $objGlossaryItem)
		{
			// Transliterate letters to valid Ascii
			$itemGroup = Utf8::toAscii($objGlossaryItem->letter);

			$arrGlossaryGroups[$itemGroup]['id'] = 'group'.$this->id.'_'.$itemGroup;
			$arrGlossaryGroups[$itemGroup]['items'][] = $this->parseGlossaryItem($objGlossaryItem);

			$availableGroups[$itemGroup] = array
			(
				'item' => $this->generateGroupAnchorLink($itemGroup, $blnSingleGroup),
				'class' => $blnSingleGroup ? 'active selected' : 'active'
			);
		}

		// Sort available groups
		ksort($availableGroups);

		$objTemplate->availableGroups = $availableGroups;
		$objTemplate->glossarygroups = $arrGlossaryGroups;
	}

	/**
	 * Returns a glossary group link
	 *
	 * @param string    $letter
	 * @param boolean   $blnPageUrl
	 *
	 * @return string
	 */
	protected function generateGroupAnchorLink($letter, $blnPageUrl=false)
	{
		if ($blnPageUrl)
		{
			return sprintf('<a href="%s?page_g%s=%s">%s</a>', explode('?', $this->Environment->get('request'), 2)[0], $this->id, $letter, $letter);
		}

		return sprintf('<a href="%s#group%s_%s">%s</a>', $this->Environment->get('request'), $this->id, $letter, $letter);
	}

	/**
	 * URL cache array
	 * @var array
	 */
	private static $arrUrlCache = array();

	/**
	 * Generate a link and return it as string
	 *
	 * @param string            $strLink
	 * @param GlossaryItemModel $objGlossaryItem
	 *
	 * @return string
	 */
	protected function generateLink($strLink, $objGlossaryItem, $blnIsReadMore=false)
	{
		$blnIsInternal = $objGlossaryItem->source != 'external';
		$strReadMore = $blnIsInternal ? $GLOBALS['TL_LANG']['MSC']['readMore'] : $GLOBALS['TL_LANG']['MSC']['open'];
		$strGlossaryItemUrl = $this->generateGlossaryItemUrl($objGlossaryItem);

		return sprintf(
			'<a href="%s" title="%s"%s itemprop="url">%s%s</a>',
			$strGlossaryItemUrl,
			StringUtil::specialchars(sprintf($strReadMore, $blnIsInternal ? $objGlossaryItem->keyword : $strGlossaryItemUrl), true),
			($objGlossaryItem->target && !$blnIsInternal ? ' target="_blank" rel="noreferrer noopener"' : ''),
			($blnIsReadMore ? $strLink : '<span itemprop="headline">' . $strLink . '</span>'),
			($blnIsReadMore && $blnIsInternal ? '<span class="invisible"> ' . $objGlossaryItem->keyword . '</span>' : '')
		);
	}

	/**
	 * Generate a URL and return it as string
	 *
	 * @param GlossaryItemModel $objGlossaryItem
	 * @param boolean           $blnAbsolute
	 *
	 * @return string
	 */
	public static function generateGlossaryItemUrl($objGlossaryItem, $blnAbsolute=false)
	{
		$strCacheKey = 'id_' . $objGlossaryItem->id;

		// Load the URL from cache
		if (isset(self::$arrUrlCache[$strCacheKey]))
		{
			return self::$arrUrlCache[$strCacheKey];
		}

		// Initialize the cache
		self::$arrUrlCache[$strCacheKey] = null;

		switch ($objGlossaryItem->source)
		{
			// Link to an external page
			case 'external':
				if (0 === strncmp($objGlossaryItem->url, 'mailto:', 7))
				{
					self::$arrUrlCache[$strCacheKey] = StringUtil::encodeEmail($objGlossaryItem->url);
				}
				else
				{
					self::$arrUrlCache[$strCacheKey] = ampersand($objGlossaryItem->url);
				}
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objGlossaryItem->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					self::$arrUrlCache[$strCacheKey] = ampersand($blnAbsolute ? $objTarget->getAbsoluteUrl() : $objTarget->getFrontendUrl());
				}
				break;

			// Link to an article
			case 'article':
				if (($objArticle = ArticleModel::findByPk($objGlossaryItem->articleId)) instanceof ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
				{
					$params = '/articles/' . ($objArticle->alias ?: $objArticle->id);

					/** @var PageModel $objPid */
					self::$arrUrlCache[$strCacheKey] = ampersand($blnAbsolute ? $objPid->getAbsoluteUrl($params) : $objPid->getFrontendUrl($params));
				}
				break;
		}

		// Link to the default page
		if (self::$arrUrlCache[$strCacheKey] === null)
		{
			$objPage = PageModel::findByPk($objGlossaryItem->getRelated('pid')->jumpTo);

			if (!$objPage instanceof PageModel)
			{
				self::$arrUrlCache[$strCacheKey] = ampersand(Environment::get('request'));
			}
			else
			{
				$params = (Config::get('useAutoItem') ? '/' : '/items/') . ($objGlossaryItem->alias ?: $objGlossaryItem->id);

				self::$arrUrlCache[$strCacheKey] = ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
			}
		}

		return self::$arrUrlCache[$strCacheKey];
	}
}
