<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

/**
 * Provide methods regarding glossaries.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class Glossary extends \Frontend
{
	/**
	 * URL cache array
	 * @var array
	 */
	private static $arrUrlCache = array();

    /**
     * Add glossary items to the indexer
     *
     * @param array   $arrPages
     * @param integer $intRoot
     * @param boolean $blnIsSitemap
     *
     * @return array
     */
    public function getSearchablePages($arrPages, $intRoot=0, $blnIsSitemap=false)
    {
        $arrRoot = array();

        if ($intRoot > 0)
        {
            $arrRoot = $this->Database->getChildRecords($intRoot, 'tl_page');
        }

        $arrProcessed = array();
        $time = time();

        // Get all glossaries
        $objGlossary = GlossaryModel::findByProtected('');

        // Walk through each glossary
        if ($objGlossary !== null)
        {
            while ($objGlossary->next())
            {
                // Skip glossaries without target page
                if (!$objGlossary->jumpTo)
                {
                    continue;
                }

                // Skip glossaries outside the root nodes
                if (!empty($arrRoot) && !\in_array($objGlossary->jumpTo, $arrRoot))
                {
                    continue;
                }

                // Get the URL of the jumpTo page
                if (!isset($arrProcessed[$objGlossary->jumpTo]))
                {
                    $objParent = \PageModel::findWithDetails($objGlossary->jumpTo);

                    // The target page does not exist
                    if ($objParent === null)
                    {
                        continue;
                    }

                    // The target page has not been published (see #5520)
                    if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time))
                    {
                        continue;
                    }

                    if ($blnIsSitemap)
                    {
                        // The target page is protected (see #8416)
                        if ($objParent->protected)
                        {
                            continue;
                        }

                        // The target page is exempt from the sitemap (see #6418)
                        if ($objParent->robots == 'noindex,nofollow')
                        {
                            continue;
                        }
                    }

                    // Generate the URL
                    $arrProcessed[$objGlossary->jumpTo] = $objParent->getAbsoluteUrl(\Config::get('useAutoItem') ? '/%s' : '/items/%s');
                }

                $strUrl = $arrProcessed[$objGlossary->jumpTo];

                // Get the items
                $objArticle = GlossaryItemModel::findPublishedDefaultByPid($objGlossary->id);

                if ($objArticle !== null)
                {
                    while ($objArticle->next())
                    {
                        $arrPages[] = $this->getLink($objArticle, $strUrl);
                    }
                }
            }
        }

        return $arrPages;
    }

	/**
	 * Generate a URL and return it as string
	 *
	 * @param GlossaryItemModel $objItem
	 * @param boolean           $blnAbsolute
	 *
	 * @return string
	 */
	public static function generateUrl($objItem, $blnAbsolute=false)
	{
		$strCacheKey = 'id_' . $objItem->id . ($blnAbsolute ? '_absolute' : '');

		// Load the URL from cache
		if (isset(self::$arrUrlCache[$strCacheKey]))
		{
			return self::$arrUrlCache[$strCacheKey];
		}

		// Initialize the cache
		self::$arrUrlCache[$strCacheKey] = null;

		switch ($objItem->source)
		{
			// Link to an external page
			case 'external':
				if (0 === strncmp($objItem->url, 'mailto:', 7))
				{
					self::$arrUrlCache[$strCacheKey] = \StringUtil::encodeEmail($objItem->url);
				}
				else
				{
					self::$arrUrlCache[$strCacheKey] = ampersand($objItem->url);
				}
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) instanceof \PageModel)
				{
					/** @var \PageModel $objTarget */
					self::$arrUrlCache[$strCacheKey] = ampersand($blnAbsolute ? $objTarget->getAbsoluteUrl() : $objTarget->getFrontendUrl());
				}
				break;

			// Link to an article
			case 'article':
				if (($objArticle = \ArticleModel::findByPk($objItem->articleId)) instanceof \ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof \PageModel)
				{
					$params = '/articles/' . ($objArticle->alias ?: $objArticle->id);

					/** @var \PageModel $objPid */
					self::$arrUrlCache[$strCacheKey] = ampersand($blnAbsolute ? $objPid->getAbsoluteUrl($params) : $objPid->getFrontendUrl($params));
				}
				break;
		}

		// Link to the default page
		if (self::$arrUrlCache[$strCacheKey] === null)
		{
			$objPage = \PageModel::findByPk($objItem->getRelated('pid')->jumpTo);

			if (!$objPage instanceof \PageModel)
			{
				self::$arrUrlCache[$strCacheKey] = ampersand(\Environment::get('request'));
			}
			else
			{
				$params = (\Config::get('useAutoItem') ? '/' : '/items/') . ($objItem->alias ?: $objItem->id);

				self::$arrUrlCache[$strCacheKey] = ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
			}
		}

		return self::$arrUrlCache[$strCacheKey];
	}

    /**
     * Return the link of a glossary item
     *
     * @param GlossaryItemModel $objItem
     * @param string            $strUrl
     * @param string            $strBase
     *
     * @return string
     */
    protected function getLink($objItem, $strUrl, $strBase='')
    {
        switch ($objItem->source)
        {
            // Link to an external page
            case 'external':
                return $objItem->url;

            // Link to an internal page
            case 'internal':
                if (($objTarget = $objItem->getRelated('jumpTo')) instanceof \PageModel)
                {
                    /** @var \PageModel $objTarget */
                    return $objTarget->getAbsoluteUrl();
                }
                break;

            // Link to an article
            case 'article':
                if (($objArticle = \ArticleModel::findByPk($objItem->articleId)) instanceof \ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof \PageModel)
                {
                    /** @var \PageModel $objPid */
                    return ampersand($objPid->getAbsoluteUrl('/articles/' . ($objArticle->alias ?: $objArticle->id)));
                }
                break;
        }

        // Backwards compatibility (see #8329)
        if ($strBase != '' && !preg_match('#^https?://#', $strUrl))
        {
            $strUrl = $strBase . $strUrl;
        }

        // Link to the default page
        return sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), ($objItem->alias ?: $objItem->id));
    }
}
