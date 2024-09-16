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

// Add a palette selector
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'glossary_singleGroup';

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['glossary'] = '{title_legend},name,headline,type;{config_legend},glossary_archives,glossary_readerModule,glossary_hideEmptyGroups,glossary_singleGroup,glossary_utf8Transliteration;{template_legend},glossary_quickLinks,glossary_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['glossaryreader'] = '{title_legend},name,headline,type;{config_legend},glossary_archives;{template_legend:hide},glossary_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Add subpalettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['glossary_singleGroup'] = 'glossary_letter';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_archives'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['mandatory' => true, 'multiple' => true],
    'sql' => ['type' => 'blob', 'notnull' => false, 'length' => 65535],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_readerModule'] = [
    'exclude' => true,
    'inputType' => 'select',
    'reference' => &$GLOBALS['TL_LANG']['tl_module'],
    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_hideEmptyGroups'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'string', 'length' => 1, 'default' => '', 'fixed' => true],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_singleGroup'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'string', 'length' => 1, 'default' => '', 'fixed' => true],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_letter'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => range('A', 'Z'),
    'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 1, 'default' => 'A', 'fixed' => true],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_utf8Transliteration'] = [
    'default' => true,
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 1, 'default' => '1', 'fixed' => true],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_quickLinks'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 1, 'default' => '', 'fixed' => true],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_template'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static fn () => Controller::getTemplateGroup('glossary_'),
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
];
