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

use Contao\ArticleModel;
use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\FrontendTemplate;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

/**
 * Provide methods regarding glossaries.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class Glossary extends Frontend
{
    /**
     * URL cache array.
     */
    private static array $arrUrlCache = [];

    /**
     * Add glossary items to the indexer.
     */
    public function getSearchablePages(array $arrPages, $intRoot = 0, bool $blnIsSitemap = false): array
    {
        $arrRoot = [];

        if ($intRoot > 0)
        {
            $arrRoot = $this->Database->getChildRecords($intRoot, PageModel::getTable());
        }

        $arrProcessed = [];
        $time = time();

        // Get all glossaries
        $objGlossary = GlossaryModel::findByProtected('');

        // Walk through each glossary
        if (null !== $objGlossary)
        {
            while ($objGlossary->next())
            {
                // Skip glossaries without target page
                if (!$objGlossary->jumpTo)
                {
                    continue;
                }

                // Skip glossaries outside the root nodes
                if (!empty($arrRoot) && !\in_array($objGlossary->jumpTo, $arrRoot, true))
                {
                    continue;
                }

                // Get the URL of the jumpTo page
                if (!isset($arrProcessed[$objGlossary->jumpTo]))
                {
                    $objParent = PageModel::findWithDetails($objGlossary->jumpTo);

                    // The target page does not exist
                    if (null === $objParent)
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
                        if ('noindex,nofollow' === $objParent->robots)
                        {
                            continue;
                        }
                    }

                    // Generate the URL
                    $arrProcessed[$objGlossary->jumpTo] = $objParent->getAbsoluteUrl(Config::get('useAutoItem') ? '/%s' : '/items/%s');
                }

                $strUrl = $arrProcessed[$objGlossary->jumpTo];

                // Get the items
                $objArticle = GlossaryItemModel::findPublishedDefaultByPid($objGlossary->id);

                if (null !== $objArticle)
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
     * Generate a URL and return it as string.
     */
    public static function generateUrl(GlossaryItemModel $objItem, bool $blnAbsolute = false): string
    {
        $strCacheKey = 'id_'.$objItem->id.($blnAbsolute ? '_absolute' : '');

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
                    self::$arrUrlCache[$strCacheKey] = StringUtil::encodeEmail($objItem->url);
                }
                else
                {
                    self::$arrUrlCache[$strCacheKey] = ampersand($objItem->url);
                }
                break;

            // Link to an internal page
            case 'internal':
                if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel)
                {
                    /** @var PageModel $objTarget */
                    self::$arrUrlCache[$strCacheKey] = ampersand($blnAbsolute ? $objTarget->getAbsoluteUrl() : $objTarget->getFrontendUrl());
                }
                break;

            // Link to an article
            case 'article':
                if (($objArticle = ArticleModel::findByPk($objItem->articleId)) instanceof ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
                {
                    $params = '/articles/'.($objArticle->alias ?: $objArticle->id);

                    /** @var PageModel $objPid */
                    self::$arrUrlCache[$strCacheKey] = ampersand($blnAbsolute ? $objPid->getAbsoluteUrl($params) : $objPid->getFrontendUrl($params));
                }
                break;
        }

        // Link to the default page
        if (null === self::$arrUrlCache[$strCacheKey])
        {
            $objPage = PageModel::findByPk($objItem->getRelated('pid')->jumpTo);

            if (!$objPage instanceof PageModel)
            {
                self::$arrUrlCache[$strCacheKey] = ampersand(Environment::get('request'));
            }
            else
            {
                $params = (Config::get('useAutoItem') ? '/' : '/items/').($objItem->alias ?: $objItem->id);

                self::$arrUrlCache[$strCacheKey] = ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
            }
        }

        return self::$arrUrlCache[$strCacheKey];
    }

    /**
     * Generate a link and return it as string.
     */
    public static function generateLink(string $strLink, GlossaryItemModel $objGlossaryItem, bool $blnIsReadMore = false): string
    {
        $blnIsInternal = 'external' !== $objGlossaryItem->source;
        $strReadMore = $blnIsInternal ? $GLOBALS['TL_LANG']['MSC']['readMore'] : $GLOBALS['TL_LANG']['MSC']['open'];
        $strGlossaryItemUrl = self::generateUrl($objGlossaryItem);

        return sprintf(
            '<a href="%s" title="%s"%s itemprop="url">%s%s</a>',
            $strGlossaryItemUrl,
            StringUtil::specialchars(sprintf($strReadMore, $blnIsInternal ? $objGlossaryItem->keyword : $strGlossaryItemUrl), true),
            ($objGlossaryItem->target && !$blnIsInternal ? ' target="_blank" rel="noreferrer noopener"' : ''),
            ($blnIsReadMore ? $strLink : '<span itemprop="headline">'.$strLink.'</span>'),
            ($blnIsReadMore && $blnIsInternal ? '<span class="invisible"> '.$objGlossaryItem->keyword.'</span>' : '')
        );
    }

    /**
     * Parse a glossary item and return it as string.
     *
     * @throws \Exception
     */
    public static function parseGlossaryItem(GlossaryItemModel $objGlossaryItem, string $strTemplate, $imgSize, string $strClass = ''): string
    {
        // Load language for 'read more' link
        System::loadLanguageFile('default');

        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->setData($objGlossaryItem->row());

        if ($objGlossaryItem->cssClass)
        {
            $strClass = ' '.$objGlossaryItem->cssClass.$strClass;
        }

        $objTemplate->class = $strClass;
        $objTemplate->headline = $objGlossaryItem->keyword;
        $objTemplate->subHeadline = $objGlossaryItem->subheadline;
        $objTemplate->hasSubHeadline = $objGlossaryItem->subheadline ? true : false;
        $objTemplate->linkHeadline = self::generateLink($objGlossaryItem->keyword, $objGlossaryItem);
        $objTemplate->more = self::generateLink($GLOBALS['TL_LANG']['MSC']['more'], $objGlossaryItem, true);
        $objTemplate->glossary = $objGlossaryItem->getRelated('pid');
        $objTemplate->text = '';
        $objTemplate->hasText = false;
        $objTemplate->hasTeaser = false;

        if ($objGlossaryItem->teaser)
        {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = StringUtil::toHtml5($objGlossaryItem->teaser);
            $objTemplate->teaser = StringUtil::encodeEmail($objTemplate->teaser);

            // Replace insert tags within teaser when fetching items via controller (see #13)
            $objTemplate->teaser = Controller::replaceInsertTags($objTemplate->teaser);
        }

        // Display the "read more" button for external/article links
        if ('default' !== $objGlossaryItem->source)
        {
            $objTemplate->text = true;
            $objTemplate->hasText = true;
        }

        // Compile the glossary item
        else
        {
            $id = $objGlossaryItem->id;

            $objTemplate->text = static function () use ($id) {
                $strText = '';
                $objElement = ContentModel::findPublishedByPidAndTable($id, GlossaryItemModel::getTable());

                if (null !== $objElement)
                {
                    while ($objElement->next())
                    {
                        $strText .= Controller::getContentElement($objElement->current());
                    }
                }

                return $strText;
            };

            $objTemplate->hasText = static fn () => ContentModel::countPublishedByPidAndTable($objGlossaryItem->id, GlossaryItemModel::getTable()) > 0;
        }

        $objTemplate->addImage = false;

        // Add an image
        if ($objGlossaryItem->addImage && $objGlossaryItem->singleSRC)
        {
            $objModel = FilesModel::findByUuid($objGlossaryItem->singleSRC);

            if (null !== $objModel && is_file(System::getContainer()->getParameter('kernel.project_dir').'/'.$objModel->path))
            {
                // Do not override the field now that we have a model registry
                $arrGlossaryItem = $objGlossaryItem->row();

                // Override the default image size
                if ($imgSize)
                {
                    $size = StringUtil::deserialize($imgSize);

                    if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
                    {
                        $arrGlossaryItem['size'] = $imgSize;
                    }
                }

                $arrGlossaryItem['singleSRC'] = $objModel->path;
                Controller::addImageToTemplate($objTemplate, $arrGlossaryItem, null, null, $objModel);

                // Link to the glossary item if no image link has been defined
                if (!$objTemplate->fullsize && !$objTemplate->imageUrl)
                {
                    // Load language for 'read more' link
                    System::loadLanguageFile('default');

                    // Unset the image title attribute
                    $picture = $objTemplate->picture;
                    unset($picture['title']);
                    $objTemplate->picture = $picture;

                    // Link to the glossary item
                    $objTemplate->href = $objTemplate->link;
                    $objTemplate->linkTitle = StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objGlossaryItem->keyword), true);

                    // If the external link is opened in a new window, open the image link in a new window, too
                    if ('external' === $objTemplate->source && $objTemplate->target && false === strpos($objTemplate->attributes, 'target="_blank"'))
                    {
                        $objTemplate->attributes .= ' target="_blank"';
                    }
                }
            }
        }

        // Tag glossary items
        if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
        {
            $responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(['contao.db.tl_glossary_item.'.$objGlossaryItem->id]);
        }

        return $objTemplate->parse();
    }

    /**
     * Return the link of a glossary item.
     */
    protected function getLink($objItem, string $strUrl, string $strBase = ''): string
    {
        switch ($objItem->source)
        {
            // Link to an external page
            case 'external':
                return $objItem->url;

            // Link to an internal page
            case 'internal':
                if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel)
                {
                    /** @var PageModel $objTarget */
                    return $objTarget->getAbsoluteUrl();
                }
                break;

            // Link to an article
            case 'article':
                if (($objArticle = ArticleModel::findByPk($objItem->articleId)) instanceof ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
                {
                    /** @var PageModel $objPid */
                    return ampersand($objPid->getAbsoluteUrl('/articles/'.($objArticle->alias ?: $objArticle->id)));
                }
                break;
        }

        // Backwards compatibility (see #8329)
        if ('' !== $strBase && !preg_match('#^https?://#', $strUrl))
        {
            $strUrl = $strBase.$strUrl;
        }

        // Link to the default page
        return sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), ($objItem->alias ?: $objItem->id));
    }
}
