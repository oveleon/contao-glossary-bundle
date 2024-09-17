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

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Palettes
$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][] = 'activateGlossary';

$GLOBALS['TL_DCA']['tl_page']['subpalettes']['activateGlossary'] = 'glossaryArchives,glossaryHoverCard,glossaryConfigTemplate';

// Fields
$GLOBALS['TL_DCA']['tl_page']['fields']['activateGlossary'] = [
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50', 'submitOnChange' => true],
    'sql' => ['type' => 'string', 'length' => 1, 'default' => '', 'fixed' => true],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['glossaryArchives'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['mandatory' => true, 'multiple' => true],
    'sql' => ['type' => 'blob', 'notnull' => false, 'length' => 65535],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['glossaryHoverCard'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => [
        'disabled' => &$GLOBALS['TL_LANG']['tl_page']['hoverCardDisabled'],
        'enabled' => &$GLOBALS['TL_LANG']['tl_page']['hoverCardEnabled'],
    ],
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 32, 'default' => 'disabled'],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['glossaryConfigTemplate'] = [
    'inputType' => 'select',
    'options_callback' => static fn () => Controller::getTemplateGroup('config_glossary_'),
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['disableGlossary'] = [
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 1, 'default' => '', 'fixed' => true],
];

// Extend the root palettes
$objPaletteManipulator = PaletteManipulator::create()
    ->addLegend('glossary_legend', 'global_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField(['activateGlossary'], 'glossary_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('root', 'tl_page')
;

if (array_key_exists('rootfallback', $GLOBALS['TL_DCA']['tl_page']['palettes']))
{
    $objPaletteManipulator->applyToPalette('rootfallback', 'tl_page');
}

// Extend regular palette
PaletteManipulator::create()
    ->addField(['disableGlossary'], 'expert_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('regular', 'tl_page')
;
