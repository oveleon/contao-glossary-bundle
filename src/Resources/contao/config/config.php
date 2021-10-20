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

// Back end modules
array_insert($GLOBALS['BE_MOD']['content'], 5, [
    'glossary' => [
        'tables' => ['tl_glossary', 'tl_glossary_item', 'tl_content'],
    ],
]);

// Front end modules
array_insert($GLOBALS['FE_MOD'], 3, [
    'glossaries' => [
        'glossary' => 'Oveleon\ContaoGlossaryBundle\ModuleGlossaryList',
        'glossaryreader' => 'Oveleon\ContaoGlossaryBundle\ModuleGlossaryReader',
    ],
]);

// Models
$GLOBALS['TL_MODELS']['tl_glossary'] = 'Oveleon\ContaoGlossaryBundle\GlossaryModel';
$GLOBALS['TL_MODELS']['tl_glossary_item'] = 'Oveleon\ContaoGlossaryBundle\GlossaryItemModel';

// Register hooks
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = ['Oveleon\ContaoGlossaryBundle\Glossary', 'getSearchablePages'];

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'glossarys';
$GLOBALS['TL_PERMISSIONS'][] = 'glossaryp';
