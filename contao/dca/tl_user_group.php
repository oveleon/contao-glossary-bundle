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

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Add fields to tl_user_group
$GLOBALS['TL_DCA']['tl_user_group']['fields']['glossarys'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_glossary.title',
    'eval' => ['multiple' => true],
    'sql' => ['type' => 'blob', 'notnull' => false, 'length' => 65535],
];

$GLOBALS['TL_DCA']['tl_user_group']['fields']['glossaryp'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => ['create', 'delete'],
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'eval' => ['multiple' => true],
    'sql' => ['type' => 'blob', 'notnull' => false, 'length' => 65535],
];

// Extend the default palette
PaletteManipulator::create()
    ->addLegend('glossary_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['glossarys', 'glossaryp'], 'glossary_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;
