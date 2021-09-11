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
        $time = \Date::floorToMinute();

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
                    if (!$objParent->published || ($objParent->start != '' && $objParent->start > $time) || ($objParent->stop != '' && $objParent->stop <= ($time + 60)))
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
	            // ToDo:: add glossary items exempt from the sitemap
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
