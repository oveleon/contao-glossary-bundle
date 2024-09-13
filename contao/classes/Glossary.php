<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @package     contao-glossary-bundle
 * @license     AGPL-3.0
 * @author      Sebastian Zoglowek    <https://github.com/zoglo>
 * @author      Fabian Ekert          <https://github.com/eki89>
 * @author      Daniele Sciannimanica <https://github.com/doishub>
 * @copyright   Oveleon               <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\Controller;
use Contao\Environment;
use Contao\Frontend;
use Contao\FrontendTemplate;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;

use function Symfony\Component\String\u;

/**
 * Provide methods regarding glossaries.
 */
class Glossary extends Frontend
{
    /**
     * URL cache array.
     */
    private static array $arrUrlCache = [];

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
                if (str_starts_with($objItem->url, 'mailto:'))
                {
                    self::$arrUrlCache[$strCacheKey] = StringUtil::encodeEmail($objItem->url);
                }
                else
                {
                    $url = $objItem->url;

                    if (self::isRelativeUrl($url))
                    {
                        $url = Environment::get('path').'/'.$url;
                    }

                    self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($url);
                }
                break;

            // Link to an internal page
            case 'internal':
                if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel)
                {
                    /** @var PageModel $objTarget */
                    self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objTarget->getAbsoluteUrl() : $objTarget->getFrontendUrl());
                }
                break;

            // Link to an article
            case 'article':
                if (($objArticle = ArticleModel::findById($objItem->articleId)) instanceof ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
                {
                    $params = '/articles/'.($objArticle->alias ?: $objArticle->id);

                    /** @var PageModel $objPid */
                    self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPid->getAbsoluteUrl($params) : $objPid->getFrontendUrl($params));
                }
                break;
        }

        // Link to the default page
        if (null === self::$arrUrlCache[$strCacheKey])
        {
            $objPage = PageModel::findById($objItem->getRelated('pid')->jumpTo);

            if (!$objPage instanceof PageModel)
            {
                self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand(Environment::get('requestUri'));
            }
            else
            {
                $params = (ModuleGlossary::useAutoItem() ? '/' : '/items/').($objItem->alias ?: $objItem->id);

                self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
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

        return \sprintf(
            '<a href="%s" title="%s"%s itemprop="url">%s%s</a>',
            $strGlossaryItemUrl,
            StringUtil::specialchars(\sprintf($strReadMore, $blnIsInternal ? $objGlossaryItem->keyword : $strGlossaryItemUrl), true),
            $objGlossaryItem->target && !$blnIsInternal ? ' target="_blank" rel="noreferrer noopener"' : '',
            $blnIsReadMore ? $strLink : '<span itemprop="headline">'.$strLink.'</span>',
            $blnIsReadMore && $blnIsInternal ? '<span class="invisible"> '.$objGlossaryItem->keyword.'</span>' : '',
        );
    }

    /**
     * Parse a glossary item and return it as string.
     */
    public static function parseGlossaryItem(GlossaryItemModel $objGlossaryItem, string $strTemplate, string $modelImgSize, string $strClass = ''): string
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
        $objTemplate->hasSubHeadline = (bool) $objGlossaryItem->subheadline;
        $objTemplate->linkHeadline = self::generateLink($objGlossaryItem->keyword, $objGlossaryItem);
        $objTemplate->more = self::generateLink($GLOBALS['TL_LANG']['MSC']['more'], $objGlossaryItem, true);
        $objTemplate->glossary = $objGlossaryItem->getRelated('pid');
        $objTemplate->text = '';
        $objTemplate->hasText = false;
        $objTemplate->hasTeaser = false;

        if ($objGlossaryItem->teaser)
        {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = $objGlossaryItem->teaser;
            $objTemplate->teaser = StringUtil::encodeEmail($objTemplate->teaser);

            // Replace insert tags within teaser when fetching items via controller (see #13)
            $parser = System::getContainer()->get('contao.insert_tag.parser');
            $objTemplate->teaser = $parser->replace((string) $objTemplate->teaser);
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

            $objTemplate->text = static function () use ($id)
            {
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
        if ($objGlossaryItem->addImage)
        {
            $imgSize = $objGlossaryItem->size ?: null;

            // ToDo: Move method into src
            // Override the default image size
            if ('' !== $modelImgSize)
            {
                $size = StringUtil::deserialize($modelImgSize);

                if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
                {
                    $imgSize = $modelImgSize;
                }
            }

            $figureBuilder = System::getContainer()
                ->get('contao.image.studio')
                ->createFigureBuilder()
                ->from($objGlossaryItem->singleSRC)
                ->setSize($imgSize)
                ->enableLightbox((bool) $objGlossaryItem->fullsize)
            ;

            // If the external link is opened in a new window, open the image link in a new window as well (see #210)
            if ('external' === $objTemplate->source && $objTemplate->target)
            {
                $figureBuilder->setLinkAttribute('target', '_blank');
            }

            if (null !== ($figure = $figureBuilder->buildIfResourceExists()))
            {
                if (!$figure->getLinkHref())
                {
                    $linkTitle = StringUtil::specialchars(\sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objGlossaryItem->keyword), true);

                    $figure = $figureBuilder
                        ->setLinkHref($objTemplate->link)
                        ->setLinkAttribute('title', $linkTitle)
                        ->build()
                    ;
                }

                $figure->applyLegacyTemplateData($objTemplate, $objGlossaryItem->imagemargin, $objGlossaryItem->floating);
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
     * Returns a transliterated string.
     */
    public static function transliterateAscii(string $string): string
    {
        return (string) u($string)->ascii();
    }

    /**
     * Valid relative URL.
     */
    private static function isRelativeUrl(mixed $varValue): bool
    {
        return Validator::isUrl($varValue) && !preg_match('(^([0-9a-z+.-]+:|#|/|\{\{))i', (string) $varValue);
    }
}
