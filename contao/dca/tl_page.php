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
use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Palettes
$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][] = 'activateGlossary';

$GLOBALS['TL_DCA']['tl_page']['subpalettes']['activateGlossary'] = 'glossaryArchives,glossaryHoverCard,glossaryConfigTemplate';

// Fields
$GLOBALS['TL_DCA']['tl_page']['fields']['activateGlossary'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['activateGlossary'],
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50', 'submitOnChange' => true],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_page']['fields']['glossaryArchives'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['glossaryArchives'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => ['tl_page_glossary', 'getGlossaries'],
    'eval' => ['mandatory' => true, 'multiple' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_page']['fields']['glossaryHoverCard'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['activateGlossaryHoverCards'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => [
        'disabled' => &$GLOBALS['TL_LANG']['tl_page']['hoverCardDisabled'],
        'enabled' => &$GLOBALS['TL_LANG']['tl_page']['hoverCardEnabled'],
    ],
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => "varchar(32) NOT NULL default 'disabled'",
];

$GLOBALS['TL_DCA']['tl_page']['fields']['glossaryConfigTemplate'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['glossaryConfigTemplate'],
    'inputType' => 'select',
    'options_callback' => static fn () => Controller::getTemplateGroup('config_glossary_'),
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_page']['fields']['disableGlossary'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['disableGlossary'],
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default ''",
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

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
class tl_page_glossary extends Backend
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
     * Get all glossaries and return them as array.
     *
     * @return array
     */
    public function getGlossaries()
    {
        if (!$this->User->isAdmin && !is_array($this->User->glossaries))
        {
            return [];
        }

        $arrGlossary = [];
        $objGlossary = $this->Database->execute('SELECT id, title FROM tl_glossary ORDER BY title');

        while ($objGlossary->next())
        {
            if ($this->User->hasAccess($objGlossary->id, 'glossarys'))
            {
                $arrGlossary[$objGlossary->id] = $objGlossary->title;
            }
        }

        return $arrGlossary;
    }
}
