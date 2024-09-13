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

namespace Oveleon\ContaoGlossaryBundle\Security;

final class ContaoGlossaryPermissions
{
    public const USER_CAN_EDIT_ARCHIVE = 'contao_user.glossarys';

    public const USER_CAN_CREATE_ARCHIVES = 'contao_user.glossaryp.create';

    public const USER_CAN_DELETE_ARCHIVES = 'contao_user.glossaryp.delete';
}
