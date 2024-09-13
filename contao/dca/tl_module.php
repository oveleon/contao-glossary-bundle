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

use Contao\Backend;
use Contao\BackendUser;
use Contao\Controller;
use Contao\Database;
use Contao\User;

// Add a palette selector
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'glossary_singleGroup';

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['glossary'] = '{title_legend},name,headline,type;{config_legend},glossary_archives,glossary_readerModule,glossary_hideEmptyGroups,glossary_singleGroup,glossary_utf8Transliteration;{template_legend},glossary_quickLinks,glossary_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['glossaryreader'] = '{title_legend},name,headline,type;{config_legend},glossary_archives;{template_legend:hide},glossary_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Add subpalettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['glossary_singleGroup'] = 'glossary_letter';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_archives'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['glossary_archives'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => ['tl_module_glossary', 'getGlossaries'],
    'eval' => ['mandatory' => true, 'multiple' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_readerModule'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['glossary_readerModule'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_glossary', 'getReaderModules'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module'],
    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_hideEmptyGroups'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['glossary_hideEmptyGroups'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_singleGroup'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['glossary_singleGroup'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_letter'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['glossary_letter'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => range('A', 'Z'),
    'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default 'A'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_utf8Transliteration'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['glossary_utf8Transliteration'],
    'default' => true,
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_quickLinks'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['glossary_quickLinks'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_template'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['glossary_template'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static fn () => Controller::getTemplateGroup('glossary_'),
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50 clr'],
    'sql' => "varchar(64) NOT NULL default ''",
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_module extends Backend
{
    /**
     * Get all glossaries and return them as array.
     *
     * @return array
     */
    public function getGlossaries()
    {
        $db = Database::getInstance();

        /** @var BackendUser|User $user */
        $user = BackendUser::getInstance();

        if (!$user->isAdmin && !is_array($user->glossaries))
        {
            return [];
        }

        $arrGlossary = [];
        $objGlossary = $db->execute('SELECT id, title FROM tl_glossary ORDER BY title');

        while ($objGlossary->next())
        {
            if ($user->hasAccess($objGlossary->id, 'glossarys')) /** @phpstan-ignore-line */
            {
                $arrGlossary[$objGlossary->id] = $objGlossary->title;
            }
        }

        return $arrGlossary;
    }

    /**
     * Get all glossary reader modules and return them as array.
     *
     * @return array
     */
    public function getReaderModules()
    {
        $db = Database::getInstance();

        $arrModules = [];
        $objModules = $db->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='glossaryreader' ORDER BY t.name, m.name");

        while ($objModules->next())
        {
            $arrModules[$objModules->theme][$objModules->id] = $objModules->name.' (ID '.$objModules->id.')';
        }

        return $arrModules;
    }
}
