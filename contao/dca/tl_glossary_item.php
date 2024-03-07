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

use Contao\Backend;
use Contao\BackendUser;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\EventListener\DataContainer\GlossaryItemListener;
use Oveleon\ContaoGlossaryBundle\Glossary;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;

System::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_glossary_item'] = [
    // Config
    'config' => [
        'dataContainer'               => DC_Table::class,
        'ptable'                      => 'tl_glossary',
        'ctable'                      => ['tl_content'],
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'markAsCopy'                  => 'keyword',
        'onload_callback' => [
            [GlossaryItemListener::class, 'checkPermission'],
            [GlossaryItemListener::class, 'generateSitemap'],
        ],
        'oncut_callback' => [
            [GlossaryItemListener::class, 'scheduleUpdate'],
        ],
        'ondelete_callback' => [
            [GlossaryItemListener::class, 'scheduleUpdate'],
        ],
        'onsubmit_callback' => [
            [GlossaryItemListener::class, 'setGlossaryItemGroup'],
            [GlossaryItemListener::class, 'scheduleUpdate'],
        ],
        'oninvalidate_cache_tags_callback' => [
            [GlossaryItemListener::class, 'addSitemapCacheInvalidationTag'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'index',
                'pid,published' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode'                    => DataContainer::MODE_PARENT,
            'fields'                  => ['keyword'],
            'headerFields'            => ['title', 'jumpTo', 'tstamp', 'protected'],
            'panelLayout'             => 'filter;sort,search,limit',
            'child_record_callback'   => [GlossaryItemListener::class, 'listItems'],
            'child_record_class'      => 'no_padding'
        ],
        'global_operations' => [
            'all' => [
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href'                => 'table=tl_content',
                'icon'                => 'edit.svg',
            ],
            'editheader' => [
                'href'                => 'act=edit',
                'icon'                => 'header.svg',
            ],
            'copy' => [
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.svg',
            ],
            'cut' => [
                'href'                => 'act=paste&amp;mode=cut',
                'icon'                => 'cut.svg',
            ],
            'delete' => [
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'href'                => 'act=toggle&amp;field=published',
                'icon'                => 'visible.svg',
                'showInHeader'        => true,
            ],
            'show' => [
                'href'                => 'act=show',
                'icon'                => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__'                => ['source', 'addImage', 'overwriteMeta'],
        'default'                     => '{title_legend},keyword,alias;{keyword_legend:hide},keywords,sensitiveSearch;{source_legend:hide},source;{meta_legend},pageTitle,robots,description,serpPreview;{teaser_legend},subheadline,teaser;{image_legend},addImage;{expert_legend:hide},cssClass;{publish_legend},published',
    ],

    // Subpalettes
    'subpalettes' => [
        'source_internal'             => 'jumpTo',
        'source_article'              => 'articleId',
        'source_external'             => 'url,target',
        'addImage'                    => 'singleSRC,size,floating,imagemargin,fullsize,overwriteMeta',
        'overwriteMeta'               => 'alt,imageTitle,imageUrl,caption',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql'                     => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey'              => 'tl_glossary.title',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['tstamp'],
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
        ],
        'letter' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['letter'],
            'sql'                     => "char(1) NOT NULL default ''",
        ],
        'keyword' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['keyword'],
            'exclude'                 => true,
            'search'                  => true,
            'sorting'                 => true,
            'flag'                    => 1,
            'inputType'               => 'text',
            'eval'                    => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql'                     => "varchar(64) NOT NULL default ''",
        ],
        'alias' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['alias'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50 clr'],
            'save_callback' => [
                ['tl_glossary_item', 'generateAlias'],
            ],
            'sql'                     => "varchar(255) BINARY NOT NULL default ''",
        ],
        'keywords' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['keywords'],
            'exclude'                 => true,
            'inputType'               => 'listWizard',
            'eval'                    => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql'                     => 'blob NULL',
        ],
        'sensitiveSearch' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['sensitiveSearch'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => ['tl_class' => 'w50 clr m12'],
            'sql'                     => "char(1) NOT NULL default ''",
        ],
        'pageTitle' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['pageTitle'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''",
        ],
        'robots' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['robots'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'select',
            'options'                 => ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'],
            'eval'                    => ['tl_class' => 'w50', 'includeBlankOption' => true],
            'sql'                     => "varchar(32) NOT NULL default ''",
        ],
        'description' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['description'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'textarea',
            'eval'                    => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'clr'],
            'sql'                     => 'text NULL',
        ],
        'serpPreview' => [
            'label'                   => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
            'exclude'                 => true,
            'inputType'               => 'serpPreview',
            'eval'                    => ['url_callback' => ['tl_glossary_item', 'getSerpUrl'], 'title_tag_callback' => ['tl_glossary_item', 'getTitleTag'], 'titleFields' => ['pageTitle', 'keyword'], 'descriptionFields' => ['description', 'teaser']],
            'sql'                     => null,
        ],
        'subheadline' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['subheadline'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['maxlength' => 255, 'tl_class' => 'long'],
            'sql'                     => "varchar(255) NOT NULL default ''",
        ],
        'teaser' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['teaser'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'textarea',
            'eval'                    => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'                     => 'text NULL',
        ],
        'addImage' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['addImage'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => ['submitOnChange' => true],
            'sql'                     => "char(1) NOT NULL default ''",
        ],
        'overwriteMeta' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql'                     => "char(1) NOT NULL default ''",
        ],
        'singleSRC' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
            'exclude'                 => true,
            'inputType'               => 'fileTree',
            'eval'                    => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => Config::get('validImageTypes'), 'mandatory' => true],
            'sql'                     => 'binary(16) NULL',
        ],
        'alt' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['alt'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''",
        ],
        'imageTitle' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''",
        ],
        'size' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['size'],
            'exclude'                 => true,
            'inputType'               => 'imageSize',
            'reference'               => &$GLOBALS['TL_LANG']['MSC'],
            'eval'                    => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'options_callback'        => static fn () => System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance()),
            'sql'                     => "varchar(64) NOT NULL default ''",
        ],
        'imagemargin' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imagemargin'],
            'exclude'                 => true,
            'inputType'               => 'trbl',
            'options'                 => ['px', '%', 'em', 'rem'],
            'eval'                    => ['includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql'                     => "varchar(128) NOT NULL default ''",
        ],
        'imageUrl' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'addWizardClass' => false, 'tl_class' => 'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''",
        ],
        'fullsize' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => ['tl_class' => 'w50 m12'],
            'sql'                     => "char(1) NOT NULL default ''",
        ],
        'caption' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['caption'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['maxlength' => 255, 'allowHtml' => true, 'tl_class' => 'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''",
        ],
        'floating' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['floating'],
            'exclude'                 => true,
            'inputType'               => 'radioTable',
            'options'                 => ['above', 'left', 'right', 'below'],
            'eval'                    => ['cols' => 4, 'tl_class' => 'w50'],
            'reference'               => &$GLOBALS['TL_LANG']['MSC'],
            'sql'                     => "varchar(12) NOT NULL default 'above'",
        ],
        'source' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['source'],
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'radio',
            'options_callback'        => ['tl_glossary_item', 'getSourceOptions'],
            'reference'               => &$GLOBALS['TL_LANG']['tl_glossary_item'],
            'eval'                    => ['submitOnChange' => true, 'helpwizard' => true],
            'sql'                     => "varchar(32) NOT NULL default 'default'",
        ],
        'jumpTo' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['jumpTo'],
            'exclude'                 => true,
            'inputType'               => 'pageTree',
            'foreignKey'              => 'tl_page.title',
            'eval'                    => ['mandatory' => true, 'fieldType' => 'radio'],
            'sql'                     => 'int(10) unsigned NOT NULL default 0',
            'relation'                => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'articleId' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['articleId'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'options_callback'        => ['tl_glossary_item', 'getArticleAlias'],
            'eval'                    => ['chosen' => true, 'mandatory' => true, 'tl_class' => 'w50'],
            'sql'                     => 'int(10) unsigned NOT NULL default 0',
            'relation'                => ['table' => 'tl_article', 'type' => 'hasOne', 'load' => 'lazy'],
        ],
        'url' => [
            'label'                   => &$GLOBALS['TL_LANG']['MSC']['url'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['mandatory' => true, 'rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'addWizardClass' => false, 'tl_class' => 'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''",
        ],
        'target' => [
            'label'                   => &$GLOBALS['TL_LANG']['MSC']['target'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => ['tl_class' => 'w50 m12'],
            'sql'                     => "char(1) NOT NULL default ''",
        ],
        'cssClass' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['cssClass'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['tl_class' => 'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''",
        ],
        'published' => [
            'toggle'                  => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['published'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'eval'                    => ['doNotCopy' => true],
            'sql'                     => "char(1) NOT NULL default ''",
        ],
    ],
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 * @autho Sebastian Zoglowek <https://github.com/zoglo>
 */
class tl_glossary_item extends Backend
{
    /**
     * Auto-generate the glossary item alias if it has not been set yet.
     *
     * @param mixed $varValue
     *
     * @throws Exception
     *
     * @return string
     */
    public function generateAlias($varValue, DataContainer $dc)
    {
        $aliasExists = function (string $alias) use ($dc): bool {
            $result = Database::getInstance()
                ->prepare("SELECT id FROM tl_glossary_item WHERE alias=? AND id!=?")
                ->execute($alias, $dc->id);

            return $result->numRows > 0;
        };

        // Generate alias if there is none
        if (!$varValue)
        {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->keyword, GlossaryModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
        }
        elseif (preg_match('/^[1-9]\d*$/', $varValue))
        {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        }
        elseif ($aliasExists($varValue))
        {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    /**
     * Return the SERP URL.
     *
     * @return string
     */
    public function getSerpUrl(GlossaryItemModel $model)
    {
        return Glossary::generateUrl($model, true);
    }

    /**
     * Return the title tag from the associated page layout.
     *
     * @return string
     */
    public function getTitleTag(GlossaryItemModel $model)
    {
        if (!$glossary = GlossaryModel::findByPk($model->pid))
        {
            return '';
        }

        if (!$page = PageModel::findByPk($glossary->jumpTo))
        {
            return '';
        }

        $page->loadDetails();

        if (!$layout = LayoutModel::findByPk($page->layout))
        {
            return '';
        }

        $origObjPage = $GLOBALS['objPage'] ?? null;

        // Override the global page object, so we can replace the insert tags
        $GLOBALS['objPage'] = $page;

        $title = implode(
            '%s',
            array_map(
                static function ($strVal) {
                    return str_replace('%', '%%', System::getContainer()->get('contao.insert_tag.parser')->replaceInline($strVal));
                },
                explode('{{page::pageTitle}}', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}', 2)
            )
        );

        $GLOBALS['objPage'] = $origObjPage;

        return $title;
    }

    /**
     * Get all articles and return them as array.
     *
     * @return array
     */
    public function getArticleAlias(DataContainer $dc)
    {
        $arrPids = [];
        $arrAlias = [];

        $db = Database::getInstance();
        $user = BackendUser::getInstance();

        if (!$user->isAdmin)
        {
            foreach ($user->pagemounts as $id)
            {
                $arrPids[] = [$id];
                $arrPids[] = $db->getChildRecords($id, 'tl_page');
            }

            if (!empty($arrPids))
            {
                $arrPids = array_merge(...$arrPids);
            }
            else
            {
                return $arrAlias;
            }

            $objAlias = $db->execute("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN(" . implode(',', array_map('\intval', array_unique($arrPids))) . ") ORDER BY parent, a.sorting");
        }
        else
        {
            $objAlias = $db->execute("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting");
        }

        if ($objAlias->numRows)
        {
            System::loadLanguageFile('tl_article');

            while ($objAlias->next())
            {
                $arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title . ' (' . ($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?? $objAlias->inColumn) . ', ID ' . $objAlias->id . ')';
            }
        }

        return $arrAlias;
    }

    /**
     * Add the source options depending on the allowed fields.
     *
     * @return array
     */
    public function getSourceOptions(DataContainer $dc)
    {
        $user = BackendUser::getInstance();

        if ($user->isAdmin)
        {
            return ['default', 'internal', 'article', 'external'];
        }

        $arrOptions = ['default'];

        // Add the "internal" option
        if ($user->hasAccess('tl_glossary_item::jumpTo', 'alexf'))
        {
            $arrOptions[] = 'internal';
        }

        // Add the "article" option
        if ($user->hasAccess('tl_glossary_item::articleId', 'alexf'))
        {
            $arrOptions[] = 'article';
        }

        // Add the "external" option
        if ($user->hasAccess('tl_glossary_item::url', 'alexf'))
        {
            $arrOptions[] = 'external';
        }

        // Add the option currently set
        if ($dc->activeRecord && $dc->activeRecord->source)
        {
            $arrOptions[] = $dc->activeRecord->source;
            $arrOptions = array_unique($arrOptions);
        }

        return $arrOptions;
    }
}
