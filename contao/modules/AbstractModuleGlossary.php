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

namespace Oveleon\ContaoGlossaryBundle;

use Contao\FrontendUser;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;
use Oveleon\ContaoGlossaryBundle\Utils\GlossaryTrait;

/**
 * Parent class for glossary modules.
 */
abstract class AbstractModuleGlossary extends Module
{
    use GlossaryTrait;

    /**
     * Sort out protected glossaries.
     */
    protected function sortOutProtected(array $arrGlossaries): array
    {
        if ([] === $arrGlossaries)
        {
            return $arrGlossaries;
        }

        $this->import(FrontendUser::class, 'User');
        $objGlossary = GlossaryModel::findMultipleByIds($arrGlossaries);
        $arrGlossaries = [];

        $user = $this->User;

        if (null !== $objGlossary)
        {
            $blnFeUserLoggedIn = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();

            while ($objGlossary->next())
            {
                if ($objGlossary->protected)
                {
                    if (!$blnFeUserLoggedIn || !\is_array($user->groups))
                    {
                        continue;
                    }

                    $groups = StringUtil::deserialize($objGlossary->groups);

                    if (empty($groups) || !\is_array($groups) || [] === array_intersect($groups, $user->groups))
                    {
                        continue;
                    }
                }

                $arrGlossaries[] = $objGlossary->id;
            }
        }

        return $arrGlossaries;
    }
}
