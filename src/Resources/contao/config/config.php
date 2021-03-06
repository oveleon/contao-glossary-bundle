<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

// Back end modules
array_insert($GLOBALS['BE_MOD']['content'], 5, array
(
    'glossary' => array
    (
        'tables'      => array('tl_glossary', 'tl_glossary_item', 'tl_content')
    )
));

// Front end modules
array_insert($GLOBALS['FE_MOD'], 3, array
(
    'glossary' => array
    (
        'glossary'        => 'Oveleon\ContaoGlossaryBundle\ModuleGlossaryList',
        'glossaryreader'  => 'Oveleon\ContaoGlossaryBundle\ModuleGlossaryReader',
    )
));

// Models
$GLOBALS['TL_MODELS']['tl_glossary']      = 'Oveleon\ContaoGlossaryBundle\GlossaryModel';
$GLOBALS['TL_MODELS']['tl_glossary_item'] = 'Oveleon\ContaoGlossaryBundle\GlossaryItemModel';

// Register hooks
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('Oveleon\ContaoGlossaryBundle\Glossary', 'getSearchablePages');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('contao_glossary.listener.insert_tags', 'onReplaceInsertTags');

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'glossarys';
$GLOBALS['TL_PERMISSIONS'][] = 'glossaryp';

