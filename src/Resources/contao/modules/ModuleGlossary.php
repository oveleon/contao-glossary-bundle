<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\StringUtil;
use Contao\System;

/**
 * Parent class for glossary modules.
 *
 * @property string $glossary_template
 * @property mixed $glossary_metaFields
 *
 * @author Fabian Ekert <https://github.com/eki89>
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
abstract class ModuleGlossary extends \Module
{
	/**
	 * Sort out protected glossaries
	 *
	 * @param array $arrGlossaries
	 *
	 * @return array
	 */
	protected function sortOutProtected($arrGlossaries)
	{
		if (empty($arrGlossaries) || !\is_array($arrGlossaries))
		{
			return $arrGlossaries;
		}

		$this->import(FrontendUser::class, 'User');
		$objGlossary = GlossaryModel::findMultipleByIds($arrGlossaries);
		$arrGlossaries = array();

		if ($objGlossary !== null)
		{
			$blnFeUserLoggedIn = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();

			while ($objGlossary->next())
			{
				if ($objGlossary->protected)
				{
					if (!$blnFeUserLoggedIn || !\is_array($this->User->groups))
					{
						continue;
					}

					$groups = StringUtil::deserialize($objGlossary->groups);

					if (empty($groups) || !\is_array($groups) || !\count(array_intersect($groups, $this->User->groups)))
					{
						continue;
					}
				}

				$arrGlossaries[] = $objGlossary->id;
			}
		}

		return $arrGlossaries;
	}

	/*protected function parseGlossaryItem($objGlossaryItem, $objGlossary, $strClass='', $intCount=0)
	{
		@var FrontendTemplate|object $objTemplate
		$objTemplate = new FrontendTemplate($this->glossary_template);
		$objTemplate->setData($objGlossaryItem->row());

		if ($objGlossaryItem->cssClass != '')
		{
			$strClass = ' ' . $objGlossaryItem->cssClass . $strClass;
		}

		$objTemplate->class = $strClass;

		//ToDo: Outsource template creation to parseGlossaryItem
	}*/
}
