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

use Contao\Config;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\Environment;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\Model\Collection;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;

/**
 * Parent class for glossary modules.
 *
 * @property string $glossary_template
 * @property mixed  $glossary_metaFields
 */
abstract class ModuleGlossary extends Module
{
    /**
     * Checks weather auto_item should be used to provide BC.
     *
     * @deprecated - To be removed when contao 4.13 support ends
     *
     * @internal
     */
    public static function useAutoItem(): bool
    {
        return str_starts_with(ContaoCoreBundle::getVersion(), '5.') ? true : Config::get('useAutoItem');
    }

    /**
     * Sort out protected glossaries.
     */
    protected function sortOutProtected(array $arrGlossaries): array
    {
        if ([] === $arrGlossaries)
        {
            return $arrGlossaries;
        }

        $this->import(FrontendUser::class, 'User');
        $objGlossary = GlossaryModel::findMultipleByIds($arrGlossaries);
        $arrGlossaries = [];

        if (null !== $objGlossary)
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

                    if (empty($groups) || !\is_array($groups) || [] === array_intersect($groups, $this->User->groups))
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
     * Parse a glossary item and return it as string.
     */
    protected function parseGlossaryItem(GlossaryItemModel $objGlossaryItem, string $strClass = ''): string
    {
        if ($objGlossaryItem->cssClass)
        {
            $strClass = ' '.$objGlossaryItem->cssClass.$strClass;
        }

        return Glossary::parseGlossaryItem($objGlossaryItem, $this->glossary_template ?: 'glossary_latest', $this->imgSize, $strClass);
    }

    /**
     * Parse glossary groups and injects them to the template.
     *
     * @param Collection<GlossaryItemModel>|GlossaryItemModel|array<GlossaryItemModel>|null $objGlossaryItems
     */
    protected function parseGlossaryGroups(Collection|GlossaryItemModel|array|null $objGlossaryItems, FrontendTemplate &$objTemplate, bool $blnSingleGroup = false, bool $blnHideEmptyGroups = false, bool $blnTransliteration = true, bool $blnQuickLinks = false): void
    {
        $availableGroups = [];
        $arrQuickLinks = [];

        if (!$blnHideEmptyGroups)
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

        if ($blnSingleGroup)
        {
            // Fetch all glossary items to generate pagination links
            $objAvailableGlossaryItems = GlossaryItemModel::findPublishedByPids($this->glossary_archives);

            foreach ($objAvailableGlossaryItems as $item)
            {
                // Transliterate letters to valid Ascii
                $itemGroup = $blnTransliteration ? Glossary::transliterateAscii($item->letter) : $item->letter;

                $availableGroups[$itemGroup] = [
                    'item' => $this->generateGroupAnchorLink($itemGroup, $blnSingleGroup),
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

        // Preload all images in one query so they are loaded into the model registry
        FilesModel::findMultipleByUuids($uuids);

        foreach ($objGlossaryItems as $objGlossaryItem)
        {
            // Transliterate letters to valid Ascii
            $itemGroup = $blnTransliteration ? Glossary::transliterateAscii($objGlossaryItem->letter) : $objGlossaryItem->letter;

            $arrGlossaryGroups[$itemGroup]['id'] = 'group'.$this->id.'_'.$itemGroup;
            $arrGlossaryGroups[$itemGroup]['items'][] = $this->parseGlossaryItem($objGlossaryItem);

            $availableGroups[$itemGroup] = [
                'item' => $this->generateGroupAnchorLink($itemGroup, $blnSingleGroup),
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
     * Returns a glossary group link.
     */
    protected function generateGroupAnchorLink(string $letter, bool $blnPageUrl = false): string
    {
        if ($blnPageUrl)
        {
            return \sprintf('<a href="%s?page_g%s=%s">%s</a>', explode('?', (string) Environment::get('request'), 2)[0], $this->id, $letter, $letter);
        }

        return \sprintf('<a href="%s#group%s_%s">%s</a>', Environment::get('request'), $this->id, $letter, $letter);
    }

    /**
     * Generate an anchor link and return it as string.
     */
    protected function generateAnchorLink(string $strLink, GlossaryItemModel $objGlossaryItem): string
    {
        return \sprintf(
            '<a href="%s#g_entry_%s">%s</a>',
            Environment::get('request'),
            $objGlossaryItem->id,
            $strLink,
        );
    }
}
