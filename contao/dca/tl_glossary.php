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

use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;

$GLOBALS['TL_DCA']['tl_glossary'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_glossary_item'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'markAsCopy' => 'title',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
                'jumpTo' => 'index'
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
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
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'children' => [
                'href' => 'table=tl_glossary_item',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['protected'],
        'default' => '{title_legend},title,jumpTo;{template_legend},glossaryHoverCardTemplate;{image_legend},hoverCardImgSize;{protected_legend:hide},protected',
    ],

    // Subpalettes
    'subpalettes' => [
        'protected' => 'groups',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'title' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'default' => ''],
        ],
        'jumpTo' => [
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type'=>'hasOne', 'load'=>'lazy'],
        ],
        'glossaryHoverCardTemplate' => [
            'default' => 'hovercard_glossary_default',
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => static fn () => Controller::getTemplateGroup('hovercard_glossary_'),
            'eval' => ['mandatory' => true, 'chosen' => true, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => 'hovercard_glossary_default'],
        ],
        'hoverCardImgSize' => [
            'exclude' => true,
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'options_callback' => static fn () => System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance()),
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'protected' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            // ToDo -> Use boolean fields migration when Contao 4.13 support ends
            'sql' => ['type' => 'string', 'length' => 1, 'default' => '', 'fixed' => true],
        ],
        'groups' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_member_group.name',
            'eval' => ['mandatory' => true, 'multiple' => true],
            'sql' => ['type' => 'blob', 'notnull' => false, 'length' => 65535],
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
    ],
];

// Backwards compatibility for old icons and position
$version = ContaoCoreBundle::getVersion();

if (version_compare($version, '5', '<'))
{
    $GLOBALS['TL_DCA']['tl_glossary']['list']['operations']['edit']['icon'] = 'header.svg';
    $GLOBALS['TL_DCA']['tl_glossary']['list']['operations']['children']['icon'] = 'edit.svg';

    // Swap places for backwards compatibility
    [
        $GLOBALS['TL_DCA']['tl_glossary']['list']['operations']['children'],
        $GLOBALS['TL_DCA']['tl_glossary']['list']['operations']['edit']
    ] = [
        $GLOBALS['TL_DCA']['tl_glossary']['list']['operations']['edit'],
        $GLOBALS['TL_DCA']['tl_glossary']['list']['operations']['children'],
    ];
}
