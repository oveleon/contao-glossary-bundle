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

namespace Oveleon\ContaoGlossaryBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\FrontendUser;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;
use Oveleon\ContaoGlossaryBundle\Utils\GlossaryTrait;

/**
 * Parent class for glossary modules.
 */
abstract class AbstractGlossaryController extends AbstractFrontendModuleController
{
    use GlossaryTrait;

    /**
     * Sort out protected glossaries.
     */
    protected function sortOutProtected(array $glossaryIds): array
    {
        if ([] === $glossaryIds)
        {
            return [];
        }

        /** @var FrontendUser $user */
        $user = System::getContainer()->get('security.helper')?->getUser();

        if ($user instanceof FrontendUser)
        {
            return [];
        }

        if (null === $objGlossaries = GlossaryModel::findMultipleByIds($glossaryIds))
        {
            return [];
        }

        $blnFeUserLoggedIn = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();

        $archiveIds = [];

        foreach ($objGlossaries as $objGlossary)
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

            $archiveIds[] = $objGlossary->id;
        }

        return $archiveIds;
    }
}
