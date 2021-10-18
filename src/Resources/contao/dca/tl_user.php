<?php

/**
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @package     contao-glossary-bundle
 * @license     AGPL-3.0
 * @author      Fabian Ekert        <https://github.com/eki89>
 * @author      Sebastian Zoglowek  <https://github.com/zoglo>
 * @copyright   Oveleon             <https://www.oveleon.de/>
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Extend the default palettes
PaletteManipulator::create()
	->addLegend('glossary_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField(array('glossarys', 'glossaryp'), 'glossary_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('extend', 'tl_user')
	->applyToPalette('custom', 'tl_user')
;

// Add fields to tl_user
$GLOBALS['TL_DCA']['tl_user']['fields']['glossarys'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_user']['glossarys'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_glossary.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user']['fields']['glossaryp'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_user']['glossaryp'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);
