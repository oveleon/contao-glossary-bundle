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

use Contao\BackendUser;
use Contao\Controller;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\EventListener\DataContainer\GlossaryListener;

$GLOBALS['TL_DCA']['tl_glossary'] = [
    // Config
    'config' => [
        'dataContainer'               => DC_Table::class,
        'ctable'                      => ['tl_glossary_item'],
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'markAsCopy'                  => 'title',
        'oncreate_callback' => [
            [GlossaryListener::class, 'adjustPermissions'],
        ],
        'oncopy_callback' => [
            [GlossaryListener::class, 'adjustPermissions'],
        ],
        'oninvalidate_cache_tags_callback' => [
            [GlossaryListener::class, 'addSitemapCacheInvalidationTag'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode'                    => DataContainer::MODE_SORTED,
            'fields'                  => ['title'],
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout'             => 'filter;search,limit',
        ],
        'label' => [
            'fields'                  => ['title'],
            'format'                  => '%s',
        ],
        'global_operations' => [
            'all' => [
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href'                => 'table=tl_glossary_item',
                'icon'                => 'edit.svg',
            ],
            'editheader' => [
                'href'                => 'act=edit',
                'icon'                => 'header.svg',
                'button_callback'     => [GlossaryListener::class, 'editHeader'],
            ],
            'copy' => [
                'href'                => 'act=copy',
                'icon'                => 'copy.svg',
                'button_callback'     => [GlossaryListener::class, 'copyArchive'],
            ],
            'delete' => [
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
                'button_callback'     => [GlossaryListener::class, 'deleteArchive'],
            ],
            'show' => [
                'href'                => 'act=show',
                'icon'                => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__'                => ['protected'],
        'default'                     => '{title_legend},title,jumpTo;{template_legend},glossaryHoverCardTemplate;{image_legend},hoverCardImgSize;{protected_legend:hide},protected',
    ],

    // Subpalettes
    'subpalettes' => [
        'protected'                   => 'groups',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql'                     => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary']['tstamp'],
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
        ],
        'title' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary']['title'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''",
        ],
        'jumpTo' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary']['jumpTo'],
            'exclude'                 => true,
            'inputType'               => 'pageTree',
            'foreignKey'              => 'tl_page.title',
            'eval'                    => ['fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'glossaryHoverCardTemplate' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary']['glossaryHoverCardTemplate'],
            'default'                 => 'hovercard_glossary_default',
            'exclude'                 => true,
            'inputType'               => 'select',
            'options_callback'        => static fn () => Controller::getTemplateGroup('hovercard_glossary_'),
            'eval'                    => ['mandatory' => true, 'chosen' => true, 'tl_class' => 'w50 clr'],
            'sql'                     => "varchar(64) NOT NULL default 'hovercard_glossary_default'",
        ],
        'hoverCardImgSize' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary']['hoverCardImgSize'],
            'exclude'                 => true,
            'inputType'               => 'imageSize',
            'reference'               => &$GLOBALS['TL_LANG']['MSC'],
            'eval'                    => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'options_callback'        => static fn () => System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance()),
            'sql'                     => "varchar(64) NOT NULL default ''",
        ],
        'protected' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary']['protected'],
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'checkbox',
            'eval'                    => ['submitOnChange' => true],
            'sql'                     => "char(1) NOT NULL default ''",
        ],
        'groups' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary']['groups'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'foreignKey'              => 'tl_member_group.name',
            'eval'                    => ['mandatory' => true, 'multiple' => true],
            'sql'                     => 'blob NULL',
            'relation'                => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
    ],
];
