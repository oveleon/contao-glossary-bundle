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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use Oveleon\ContaoGlossaryBundle\Glossary;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\GlossaryModel;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

System::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_glossary_item'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_glossary',
        'ctable' => ['tl_content'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'markAsCopy' => 'keyword',
        'onload_callback' => [
            ['tl_glossary_item', 'checkPermission'],
            ['tl_glossary_item', 'generateSitemap'],
        ],
        'oncut_callback' => [
            ['tl_glossary_item', 'scheduleUpdate'],
        ],
        'ondelete_callback' => [
            ['tl_glossary_item', 'scheduleUpdate'],
        ],
        'onsubmit_callback' => [
            ['tl_glossary_item', 'setGlossaryItemGroup'],
            ['tl_glossary_item', 'scheduleUpdate'],
        ],
        'oninvalidate_cache_tags_callback' => [
            ['tl_glossary_item', 'addSitemapCacheInvalidationTag'],
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
            'mode' => 4,
            'fields' => ['keyword'],
            'headerFields' => ['title', 'jumpTo', 'tstamp', 'protected'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => ['tl_glossary_item', 'listGlossaryItems'],
            'child_record_class' => 'no_padding',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'table=tl_content',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'copy' => [
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg',
            ],
            'cut' => [
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'icon' => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => ['tl_glossary_item', 'toggleIcon'],
                'showInHeader' => true,
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['source', 'addImage', 'overwriteMeta'],
        'default' => '{title_legend},keyword,alias;{keyword_legend:hide},keywords,sensitiveSearch;{source_legend:hide},source;{meta_legend},pageTitle,robots,description,serpPreview;{teaser_legend},subheadline,teaser;{image_legend},addImage;{expert_legend:hide},cssClass;{publish_legend},published',
    ],

    // Subpalettes
    'subpalettes' => [
        'source_internal' => 'jumpTo',
        'source_article' => 'articleId',
        'source_external' => 'url,target',
        'addImage' => 'singleSRC,size,floating,imagemargin,fullsize,overwriteMeta',
        'overwriteMeta' => 'alt,imageTitle,imageUrl,caption',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_glossary.title',
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['tstamp'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'letter' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['letter'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'keyword' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['keyword'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['alias'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50 clr'],
            'save_callback' => [
                ['tl_glossary_item', 'generateAlias'],
            ],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'keywords' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['keywords'],
            'exclude' => true,
            'inputType' => 'listWizard',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => 'blob NULL',
        ],
        'sensitiveSearch' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['sensitiveSearch'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'pageTitle' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['pageTitle'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'robots' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['robots'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'options' => ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'],
            'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'description' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['description'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'serpPreview' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
            'exclude' => true,
            'inputType' => 'serpPreview',
            'eval' => ['url_callback' => ['tl_glossary_item', 'getSerpUrl'], 'title_tag_callback' => ['tl_glossary_item', 'getTitleTag'], 'titleFields' => ['pageTitle', 'keyword'], 'descriptionFields' => ['description', 'teaser']],
            'sql' => null,
        ],
        'subheadline' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['subheadline'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'long'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'teaser' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['teaser'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'addImage' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['addImage'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'overwriteMeta' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'singleSRC' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => Config::get('validImageTypes'), 'mandatory' => true],
            'sql' => 'binary(16) NULL',
        ],
        'alt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['alt'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'imageTitle' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'size' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['size'],
            'exclude' => true,
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'options_callback' => static fn () => System::getContainer()->get('contao.image.image_sizes')->getOptionsForUser(BackendUser::getInstance()),
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'imagemargin' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['imagemargin'],
            'exclude' => true,
            'inputType' => 'trbl',
            'options' => $GLOBALS['TL_CSS_UNITS'],
            'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'imageUrl' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'addWizardClass' => false, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'fullsize' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'caption' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['caption'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'allowHtml' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'floating' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['floating'],
            'exclude' => true,
            'inputType' => 'radioTable',
            'options' => ['above', 'left', 'right', 'below'],
            'eval' => ['cols' => 4, 'tl_class' => 'w50'],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'sql' => "varchar(12) NOT NULL default 'above'",
        ],
        'source' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['source'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'radio',
            'options_callback' => ['tl_glossary_item', 'getSourceOptions'],
            'reference' => &$GLOBALS['TL_LANG']['tl_glossary_item'],
            'eval' => ['submitOnChange' => true, 'helpwizard' => true],
            'sql' => "varchar(32) NOT NULL default 'default'",
        ],
        'jumpTo' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['jumpTo'],
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['mandatory' => true, 'fieldType' => 'radio'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'articleId' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['articleId'],
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_glossary_item', 'getArticleAlias'],
            'eval' => ['chosen' => true, 'mandatory' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['table' => 'tl_article', 'type' => 'hasOne', 'load' => 'lazy'],
        ],
        'url' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['url'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'addWizardClass' => false, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'target' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['target'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'cssClass' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['cssClass'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary_item']['published'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => "char(1) NOT NULL default ''",
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
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('Contao\BackendUser', 'User');
    }

    /**
     * Check permissions to edit table tl_glossary_item.
     *
     * @throws AccessDeniedException
     */
    public function checkPermission(): void
    {
        if ($this->User->isAdmin)
        {
            return;
        }

        // Set the root IDs
        if (empty($this->User->glossarys) || !is_array($this->User->glossarys))
        {
            $root = [0];
        }
        else
        {
            $root = $this->User->glossarys;
        }

        $id = strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

        // Check current action
        switch (Input::get('act'))
        {
            case 'paste':
            case 'select':
                // Check CURRENT_ID here (see #247)
                if (!in_array(CURRENT_ID, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to access glossary ID '.$id.'.');
                }
                break;

            case 'create':
                if (!Input::get('pid') || !in_array(Input::get('pid'), $root))
                {
                    throw new AccessDeniedException('Not enough permissions to create glossary items in glossary ID '.Input::get('pid').'.');
                }
                break;

            case 'cut':
            case 'copy':
                if ('cut' === Input::get('act') && 1 === (int) Input::get('mode'))
                {
                    $objGlossary = $this->Database->prepare('SELECT pid FROM tl_glossary_item WHERE id=?')
                        ->limit(1)
                        ->execute(Input::get('pid'))
                    ;

                    if ($objGlossary->numRows < 1)
                    {
                        throw new AccessDeniedException('Invalid glossary item ID '.Input::get('pid').'.');
                    }

                    $pid = $objGlossary->pid;
                }
                else
                {
                    $pid = Input::get('pid');
                }

                if (!in_array($pid, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' glossary item ID '.$id.' to glossary ID '.$pid.'.');
                }
                // no break

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
                $objGlossary = $this->Database->prepare('SELECT pid FROM tl_glossary_item WHERE id=?')
                    ->limit(1)
                    ->execute($id)
                ;

                if ($objGlossary->numRows < 1)
                {
                    throw new AccessDeniedException('Invalid glossary item ID '.$id.'.');
                }

                if (!in_array($objGlossary->pid, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' glossary item ID '.$id.' of glossary  ID '.$objGlossary->pid.'.');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!in_array($id, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to access glossary ID '.$id.'.');
                }

                $objGlossary = $this->Database->prepare('SELECT id FROM tl_glossary_item WHERE pid=?')
                    ->execute($id)
                ;

                /** @var SessionInterface $objSession */
                $objSession = System::getContainer()->get('session');

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objGlossary->fetchEach('id'));
                $objSession->replace($session);
                break;

            default:
                if (Input::get('act'))
                {
                    throw new AccessDeniedException('Invalid command "'.Input::get('act').'".');
                }

                if (!in_array($id, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to access glossary ID '.$id.'.');
                }
                break;
        }
    }

    /**
     * Set group by keyword.
     */
    public function setGlossaryItemGroup(DataContainer $dc): void
    {
        $newGroup = mb_strtoupper(mb_substr($dc->activeRecord->keyword, 0, 1, 'UTF-8'));

        if ($dc->activeRecord->letter !== $newGroup)
        {
            $this->Database->prepare('UPDATE tl_glossary_item SET letter=? WHERE id=?')
                ->execute($newGroup, $dc->id)
            ;
        }
    }

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
        $aliasExists = fn (string $alias): bool => $this->Database->prepare('SELECT id FROM tl_glossary_item WHERE alias=? AND id!=?')->execute($alias, $dc->id)->numRows > 0;

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
        /** @var GlossaryModel $glossary */
        if (!$glossary = $model->getRelated('pid'))
        {
            return '';
        }

        /** @var PageModel $page */
        if (!$page = $glossary->getRelated('jumpTo'))
        {
            return '';
        }

        $page->loadDetails();

        /** @var LayoutModel $layout */
        if (!$layout = $page->getRelated('layout'))
        {
            return '';
        }

        global $objPage;

        // Set the global page object so we can replace the insert tags
        $objPage = $page;

        return self::replaceInsertTags(str_replace('{{page::pageTitle}}', '%s', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}'));
    }

    /**
     * List a glossary item.
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function listGlossaryItems($arrRow)
    {
        return '<div class="tl_content_left">'.$arrRow['keyword'].'</div>';
    }

    /**
     * Check for modified glossary items and update the XML files if necessary.
     */
    public function generateSitemap(): void
    {
        /** @var SessionInterface $objSession */
        $objSession = System::getContainer()->get('session');

        $session = $objSession->get('glossaryitems_updater');

        if (empty($session) || !is_array($session))
        {
            return;
        }

        $this->import('Contao\Automator', 'Automator');
        $this->Automator->generateSitemap();

        $objSession->set('glossaryitems_updater', null);
    }

    /**
     * Schedule a glossary item update.
     *
     * This method is triggered when a single glossary item or multiple glossary items
     * are modified (edit/editAll), moved (cut/cutAll) or deleted (delete/deleteAll).
     * Since duplicated items are unpublished by default, it is not necessary to
     * schedule updates on copyAll as well.
     */
    public function scheduleUpdate(DataContainer $dc): void
    {
        // Return if there is no ID
        if (!$dc->activeRecord || !$dc->activeRecord->pid || 'copy' === Input::get('act'))
        {
            return;
        }

        /** @var SessionInterface $objSession */
        $objSession = System::getContainer()->get('session');

        // Store the ID in the session
        $session = $objSession->get('glossaryitems_updater');
        $session[] = $dc->activeRecord->pid;
        $objSession->set('glossaryitems_updater', array_unique($session));
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

        if (!$this->User->isAdmin)
        {
            foreach ($this->User->pagemounts as $id)
            {
                $arrPids[] = [$id];
                $arrPids[] = $this->Database->getChildRecords($id, 'tl_page');
            }

            if (!empty($arrPids))
            {
                $arrPids = array_merge(...$arrPids);
            }
            else
            {
                return $arrAlias;
            }

            $objAlias = $this->Database->prepare('SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN('.implode(',', array_map('\intval', array_unique($arrPids))).') ORDER BY parent, a.sorting')
                ->execute($dc->id)
            ;
        }
        else
        {
            $objAlias = $this->Database->prepare('SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting')
                ->execute($dc->id)
            ;
        }

        if ($objAlias->numRows)
        {
            System::loadLanguageFile('tl_article');

            while ($objAlias->next())
            {
                $arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title.' ('.($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?: $objAlias->inColumn).', ID '.$objAlias->id.')';
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
        if ($this->User->isAdmin)
        {
            return ['default', 'internal', 'article', 'external'];
        }

        $arrOptions = ['default'];

        // Add the "internal" option
        if ($this->User->hasAccess('tl_glossary_item::jumpTo', 'alexf'))
        {
            $arrOptions[] = 'internal';
        }

        // Add the "article" option
        if ($this->User->hasAccess('tl_glossary_item::articleId', 'alexf'))
        {
            $arrOptions[] = 'article';
        }

        // Add the "external" option
        if ($this->User->hasAccess('tl_glossary_item::url', 'alexf'))
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

    /**
     * Add a link to the list items import wizard.
     *
     * @return string
     */
    public function listImportWizard()
    {
        return ' <a href="'.$this->addToUrl('key=list').'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['lw_import'][1]).'" onclick="Backend.getScrollOffset()">'.Image::getHtml('tablewizard.svg', $GLOBALS['TL_LANG']['MSC']['tw_import'][0]).'</a>';
    }

    /**
     * Return the "toggle visibility" button.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (Input::get('tid'))
        {
            $this->toggleVisibility(Input::get('tid'), 1 === (int) Input::get('state'), (func_num_args() <= 12 ? null : func_get_arg(12)));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->User->hasAccess('tl_glossary_item::published', 'alexf'))
        {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published'])
        {
            $icon = 'invisible.svg';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="'.($row['published'] ? 1 : 0).'"').'</a> ';
    }

    /**
     * Disable/enable a user group.
     *
     * @param int           $intId
     * @param bool          $blnVisible
     * @param DataContainer $dc
     */
    public function toggleVisibility($intId, $blnVisible, DataContainer $dc = null): void
    {
        // Set the ID and action
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');

        if ($dc)
        {
            $dc->id = $intId;
        }

        // Trigger the onload_callback
        if (is_array($GLOBALS['TL_DCA']['tl_glossary_item']['config']['onload_callback'] ?? null))
        {
            foreach ($GLOBALS['TL_DCA']['tl_glossary_item']['config']['onload_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                }
                elseif (is_callable($callback))
                {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!$this->User->hasAccess('tl_glossary_item::published', 'alexf'))
        {
            throw new AccessDeniedException('Not enough permissions to publish/unpublish glossary item ID '.$intId.'.');
        }

        // Set the current record
        $objRow = $this->Database->prepare('SELECT * FROM tl_glossary_item WHERE id=?')
            ->limit(1)
            ->execute($intId)
        ;

        if ($objRow->numRows < 1)
        {
            throw new AccessDeniedException('Invalid glossary item ID '.$intId.'.');
        }

        if ($dc)
        {
            $dc->activeRecord = $objRow;
        }

        $objVersions = new Versions('tl_glossary_item', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_glossary_item']['fields']['published']['save_callback'] ?? null))
        {
            foreach ($GLOBALS['TL_DCA']['tl_glossary_item']['fields']['published']['save_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
                }
                elseif (is_callable($callback))
                {
                    $blnVisible = $callback($blnVisible, $dc);
                }
            }
        }

        $time = time();

        // Update the database
        $this->Database->prepare("UPDATE tl_glossary_item SET tstamp=$time, published='".($blnVisible ? '1' : '')."' WHERE id=?")
            ->execute($intId)
        ;

        if ($dc)
        {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (is_array($GLOBALS['TL_DCA']['tl_glossary_item']['config']['onsubmit_callback'] ?? null))
        {
            foreach ($GLOBALS['TL_DCA']['tl_glossary_item']['config']['onsubmit_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                }
                elseif (is_callable($callback))
                {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();

        if ($dc)
        {
            $dc->invalidateCacheTags();
        }
    }

    /**
     * @param DataContainer $dc
     *
     * @return array
     */
    public function addSitemapCacheInvalidationTag($dc, array $tags)
    {
        $archiveModel = GlossaryModel::findByPk($dc->activeRecord->pid);
        $pageModel = PageModel::findWithDetails($archiveModel->jumpTo);

        if (null === $pageModel)
        {
            return $tags;
        }

        return array_merge($tags, ['contao.sitemap.'.$pageModel->rootId]);
    }
}
