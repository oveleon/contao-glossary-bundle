<?php
/**
 * This file is part of Oveleon Glossary bundle.
 *
 * @license     MIT
 * @author      Sebastian Zoglowek <https://github.com/zoglo>
 * @copyright   Oveleon <https://www.oveleon.de/>
 */

// Palettes
$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][] = 'activateGlossaryTooltips';

$GLOBALS['TL_DCA']['tl_page']['subpalettes']['activateGlossaryTooltips'] = 'glossaryArchives,glossaryConfigTemplate';

// Fields
$GLOBALS['TL_DCA']['tl_page']['fields']['activateGlossaryTooltips'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_page']['activateGlossaryTooltips'],
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50', 'submitOnChange'=>true),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['glossaryArchives'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_page']['glossaryArchives'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options_callback'        => array('tl_page_glossary', 'getGlossaries'),
	'eval'                    => array('mandatory'=>true, 'multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['glossaryConfigTemplate'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_page']['glossaryConfigTemplate'],
	'inputType'               => 'select',
	'options_callback' => static function ()
	{
		return Contao\Controller::getTemplateGroup('config_glossary_');
	},
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['excludeGlossaryTooltips'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_page']['excludeGlossaryTooltips'],
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);

// Extend the root palettes
$objPaletteManipulator = Contao\CoreBundle\DataContainer\PaletteManipulator::create()
	->addLegend('glossary_legend', 'global_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER, true)
	->addField(array('activateGlossaryTooltips'), 'glossary_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
	->applyToPalette('root', 'tl_page')
;

if (array_key_exists('rootfallback', $GLOBALS['TL_DCA']['tl_page']['palettes'])) {
	$objPaletteManipulator->applyToPalette('rootfallback', 'tl_page');
}

// Extend regular palette
$objPaletteManipulator = Contao\CoreBundle\DataContainer\PaletteManipulator::create()
	->addField(array('excludeGlossaryTooltips'), 'expert_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
	->applyToPalette('regular', 'tl_page');

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */

class tl_page_glossary extends Contao\Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('Contao\BackendUser', 'User');
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
}
