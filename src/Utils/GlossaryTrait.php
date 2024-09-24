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

namespace Oveleon\ContaoGlossaryBundle\Utils;

use Contao\ArticleModel;
use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Environment;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Contao\Validator;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;

use function Symfony\Component\String\u;

trait GlossaryTrait
{
    /**
     * URL cache array.
     */
    private static array $arrUrlCache = [];

    /**
     * Generate an anchor link and return it as string.
     */
    public function generateAnchorLink(string $strLink, GlossaryItemModel $objGlossaryItem): string
    {
        return \sprintf(
            '<a href="%s#g_entry_%s">%s</a>',
            Environment::get('request'),
            $objGlossaryItem->id,
            $strLink,
        );
    }

    /**
     * Generate a URL and return it as string.
     */
    public function generateDetailUrl(GlossaryItemModel $objItem, bool $blnAbsolute = false): string
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
                $params = (self::useAutoItem() ? '/' : '/items/').($objItem->alias ?: $objItem->id);

                self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
            }
        }

        return self::$arrUrlCache[$strCacheKey];
    }

    /**
     * Generate a link and return it as string.
     */
    public function generateLink(string $strLink, GlossaryItemModel $objGlossaryItem, bool $blnIsReadMore = false): string
    {
        $blnIsInternal = 'external' !== $objGlossaryItem->source;
        $strReadMore = $blnIsInternal ? $GLOBALS['TL_LANG']['MSC']['readMore'] : $GLOBALS['TL_LANG']['MSC']['open'];
        $strGlossaryItemUrl = self::generateDetailUrl($objGlossaryItem);

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
     * Parse the item and return it as a string.
     */
    public function parseItem(GlossaryItemModel $objGlossaryItem, string $strTemplate, string $modelImgSize, string $strClass = ''): string
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
    public function transliterateAscii(string $string): string
    {
        return (string) u($string)->ascii();
    }

    /**
     * Checks weather auto_item should be used to provide BC.
     *
     * @deprecated - To be removed when contao 4.13 support ends
     *
     * @internal
     */
    public function useAutoItem(): bool
    {
        return str_starts_with(ContaoCoreBundle::getVersion(), '5.') ? true : Config::get('useAutoItem');
    }

    /**
     * Returns a glossary group link.
     */
    protected function generateGroupAnchorLink(string $letter, int $id, bool $blnPageUrl = false): string
    {
        if ($blnPageUrl)
        {
            return \sprintf('<a href="%s?page_g%s=%s">%s</a>', explode('?', (string) Environment::get('request'), 2)[0], $id, $letter, $letter);
        }

        return \sprintf('<a href="%s#group%s_%s">%s</a>', Environment::get('request'), $id, $letter, $letter);
    }

    /**
     * Parse a glossary item and return it as string.
     *
     * Used within frontend modules.
     */
    protected function parseGlossaryItem(GlossaryItemModel $objGlossaryItem, string $template = 'glossary_latest', string $imgSize = '', string $strClass = ''): string
    {
        if ($objGlossaryItem->cssClass)
        {
            $strClass = ' '.$objGlossaryItem->cssClass.$strClass;
        }

        return $this->parseItem($objGlossaryItem, $template, $imgSize, $strClass);
    }

    /**
     * Parse glossary groups and injects them to the template.
     *
     * @param Collection<GlossaryItemModel>|GlossaryItemModel|array<GlossaryItemModel>|null $objGlossaryItems
     */
    protected function parseGlossaryGroups(Collection|GlossaryItemModel|array|null $objGlossaryItems, FragmentTemplate|Template &$objTemplate, array $archivePids, ModuleModel $model): void
    {
        $availableGroups = [];
        $arrQuickLinks = [];

        if (!((bool) $model->glossary_hideEmptyGroups))
        {
            $arrLetterRange = range('A', 'Z');

            foreach ($arrLetterRange as $letter)
            {
                $availableGroups[$letter] = [
                    'item' => \sprintf('<span>%s</span>', $letter),
                    'class' => 'inactive',
                ];
            }
        }

        $id = $model->id;
        $blnQuickLinks = (bool) $this->model->glossary_quickLinks;
        $blnTransliteration = (bool) $this->model->glossary_utf8Transliteration;
        $blnSingleGroup = (bool) $model->glossary_singleGroup;

        if ($blnSingleGroup)
        {
            // Fetch all glossary items to generate pagination links
            $objAvailableGlossaryItems = GlossaryItemModel::findPublishedByPids($archivePids);

            foreach ($objAvailableGlossaryItems as $item)
            {
                // Transliterate letters to valid Ascii
                $itemGroup = $blnTransliteration ? $this->transliterateAscii($item->letter) : $item->letter;

                $availableGroups[$itemGroup] = [
                    'item' => $this->generateGroupAnchorLink($itemGroup, $id, $blnSingleGroup),
                    'class' => 'active',
                ];
            }
        }

        $objTemplate->availableGroups = $availableGroups;

        if (null === $objGlossaryItems)
        {
            return;
        }

        $arrGlossaryGroups = [];

        $limit = \count($objGlossaryItems);

        if ($limit < 1)
        {
            return;
        }

        $uuids = [];

        foreach ($objGlossaryItems as $objGlossaryItem)
        {
            if ($objGlossaryItem->addImage && $objGlossaryItem->singleSRC)
            {
                $uuids[] = $objGlossaryItem->singleSRC;
            }
        }

        $itemTemplate = $model->glossary_template ?: 'glossary_latest';

        // Preload all images in one query so they are loaded into the model registry
        FilesModel::findMultipleByUuids($uuids);

        foreach ($objGlossaryItems as $objGlossaryItem)
        {
            // Transliterate letters to valid Ascii
            $itemGroup = $blnTransliteration ? $this->transliterateAscii($objGlossaryItem->letter) : $objGlossaryItem->letter;

            $arrGlossaryGroups[$itemGroup]['id'] = 'group'.$id.'_'.$itemGroup;
            $arrGlossaryGroups[$itemGroup]['items'][] = $this->parseGlossaryItem($objGlossaryItem, $itemTemplate);

            $availableGroups[$itemGroup] = [
                'item' => $this->generateGroupAnchorLink($itemGroup, $id, $blnSingleGroup),
                'class' => $blnSingleGroup ? 'active selected' : 'active',
            ];

            if ($blnQuickLinks)
            {
                $arrQuickLinks[] = $this->generateAnchorLink($objGlossaryItem->keyword, $objGlossaryItem);
            }
        }

        // Sort available groups
        uksort($availableGroups, 'strnatcasecmp');

        $objTemplate->availableGroups = $availableGroups;
        $objTemplate->glossarygroups = $arrGlossaryGroups;
        $objTemplate->hasQuickLinks = false;

        if ($blnQuickLinks)
        {
            $objTemplate->hasQuickLinks = true;
            $objTemplate->quickLinks = $arrQuickLinks;
        }
    }

    /**
     * Sort out protected glossaries.
     */
    protected function sortOutProtected(array $glossaryIds): array
    {
        if ([] === $glossaryIds)
        {
            return [];
        }

        /** @var FrontendUser $user */
        $user = System::getContainer()->get('security.helper')?->getUser();

        if ($user instanceof FrontendUser)
        {
            return [];
        }

        if (null === $objGlossaries = GlossaryModel::findMultipleByIds($glossaryIds))
        {
            return [];
        }

        $blnFeUserLoggedIn = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();

        $archiveIds = [];

        foreach ($objGlossaries as $objGlossary)
        {
            if ($objGlossary->protected)
            {
                if (!$blnFeUserLoggedIn || !\is_array($user->groups))
                {
                    continue;
                }

                $groups = StringUtil::deserialize($objGlossary->groups);

                if (empty($groups) || !\is_array($groups) || [] === array_intersect($groups, $user->groups))
                {
                    continue;
                }
            }

            $archiveIds[] = $objGlossary->id;
        }

        return $archiveIds;
    }

    /**
     * Valid relative URL.
     *
     * @deprecated Will be removed when Contao 4.13 support ends
     *
     * @internal
     */
    private function isRelativeUrl(mixed $varValue): bool
    {
        return Validator::isUrl($varValue) && !preg_match('(^([0-9a-z+.-]+:|#|/|\{\{))i', (string) $varValue);
    }
}
