<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

/**
 * Provide methods regarding glossary items.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class GlossaryItem extends \Frontend
{
    /**
     * URL cache array
     * @var array
     */
    private static $arrUrlCache = array();

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
}
