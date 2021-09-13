<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Extend the default palette
PaletteManipulator::create()
	->addLegend('glossary_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField(array('glossarys', 'glossaryp'), 'glossary_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_user_group')
;

// Add fields to tl_user_group
$GLOBALS['TL_DCA']['tl_user_group']['fields']['glossarys'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_user_group']['glossarys'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_glossary.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['glossaryp'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_user_group']['glossaryp'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options'                 => array('create', 'delete'),
    'reference'               => &$GLOBALS['TL_LANG']['MSC'],
    'eval'                    => array('multiple'=>true),
    'sql'                     => "blob NULL"
);
