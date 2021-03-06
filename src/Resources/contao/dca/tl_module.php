<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

// Add a palette selector
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'glossary_singleGroup';

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['glossary']       = '{title_legend},name,headline,type;{config_legend},glossary_archives,glossary_readerModule,glossary_hideEmptyGroups,glossary_singleGroup;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['glossaryreader'] = '{title_legend},name,headline,type;{config_legend},glossary_archives;{template_legend:hide},glossary_template,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Add subpalettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['glossary_singleGroup'] = 'glossary_letter';


// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_archives'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['glossary_archives'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options_callback'        => array('tl_module_glossary', 'getGlossaries'),
	'eval'                    => array('mandatory'=>true, 'multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_readerModule'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['glossary_readerModule'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_glossary', 'getReaderModules'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
    'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_hideEmptyGroups'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['glossary_hideEmptyGroups'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'clr'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_singleGroup'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['glossary_singleGroup'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_letter'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['glossary_letter'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => range('A', 'Z'),
    'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default 'A'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_template'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['glossary_template'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_glossary', 'getGlossaryTemplates'),
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "varchar(64) NOT NULL default 'glossary_default'"
);


/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class tl_module_glossary extends Contao\Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Get all glossaries and return them as array
	 *
	 * @return array
	 */
	public function getGlossaries()
	{
		if (!$this->User->isAdmin && !is_array($this->User->glossaries))
		{
			return array();
		}

		$arrGlossary = array();
		$objGlossary = $this->Database->execute("SELECT id, title FROM tl_glossary ORDER BY title");

		while ($objGlossary->next())
		{
			if ($this->User->hasAccess($objGlossary->id, 'glossarys'))
			{
				$arrGlossary[$objGlossary->id] = $objGlossary->title;
			}
		}

		return $arrGlossary;
	}

    /**
     * Get all glossary reader modules and return them as array
     *
     * @return array
     */
    public function getReaderModules()
    {
        $arrModules = array();
        $objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='glossaryreader' ORDER BY t.name, m.name");

        while ($objModules->next())
        {
            $arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
        }

        return $arrModules;
    }

    /**
     * Return all glossary templates as array
     *
     * @return array
     */
    public function getGlossaryTemplates()
    {
        return $this->getTemplateGroup('glossary_');
    }
}
