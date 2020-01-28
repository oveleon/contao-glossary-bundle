<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['glossarylist']   = '{title_legend},name,headline,type;{config_legend},glossary_archives,numberOfItems,perPage,skipFirst;{template_legend:hide},glossary_template,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['glossaryreader'] = '{title_legend},name,headline,type;{config_legend},glossary_archives;{template_legend:hide},glossary_template,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

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

$GLOBALS['TL_DCA']['tl_module']['fields']['glossary_template'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['glossary_template'],
    'default'                 => 'glossary_short',
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_glossary', 'getGlossaryTemplates'),
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);


/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class tl_module_glossary extends \Backend
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
	 * Get all calendars and return them as array
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
			if ($this->User->hasAccess($objGlossary->id, 'glossaries'))
			{
				$arrGlossary[$objGlossary->id] = $objGlossary->title;
			}
		}

		return $arrGlossary;
	}

	/**
	 * Return all calendar templates as array
	 *
	 * @return array
	 */
	public function getGlossaryTemplates()
	{
		return $this->getTemplateGroup('glossary_');
	}
}
