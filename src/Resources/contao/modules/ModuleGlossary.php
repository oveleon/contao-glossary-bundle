<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\FrontendUser;

/**
 * Parent class for glossary modules.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
abstract class ModuleGlossary extends \Module
{
	/**
	 * Sort out protected glossaries
	 *
	 * @param array $arrGloassary
	 *
	 * @return array
	 */
	protected function sortOutProtected($arrGloassary)
	{
		if (empty($arrGloassary) || !\is_array($arrGloassary))
		{
			return $arrGloassary;
		}

		$this->import(FrontendUser::class, 'User');
		$objGlossary = GlossaryModel::findMultipleByIds($arrGloassary);
		$arrGloassary = array();

		if ($objGlossary !== null)
		{
			while ($objGlossary->next())
			{
				if ($objGlossary->protected)
				{
					if (!FE_USER_LOGGED_IN || !\is_array($this->User->groups))
					{
						continue;
					}

					$groups = \StringUtil::deserialize($objGlossary->groups);

					if (empty($groups) || !\is_array($groups) || !\count(array_intersect($groups, $this->User->groups)))
					{
						continue;
					}
				}

				$arrGloassary[] = $objGlossary->id;
			}
		}

		return $arrGloassary;
	}
}
