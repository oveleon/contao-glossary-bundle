<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

// Extend the default palette
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
	->addLegend('glossary_legend', 'amg_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE)
	->addField(array('glossarys', 'glossaryp'), 'glossary_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_user_group')
;

// Add fields to tl_user_group
$GLOBALS['TL_DCA']['tl_user_group']['fields']['glossarys'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['glossarys'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_glossary.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['glossaryp'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_user']['glossaryp'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options'                 => array('create', 'delete'),
    'reference'               => &$GLOBALS['TL_LANG']['MSC'],
    'eval'                    => array('multiple'=>true),
    'sql'                     => "blob NULL"
);
