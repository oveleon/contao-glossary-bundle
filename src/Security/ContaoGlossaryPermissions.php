<?php

declare(strict_types=1);

namespace Oveleon\ContaoGlossaryBundle\Security;

final class ContaoGlossaryPermissions
{
    public const USER_CAN_EDIT_ARCHIVE = 'contao_user.glossarys';
    public const USER_CAN_CREATE_ARCHIVES = 'contao_user.glossaryp.create';
    public const USER_CAN_DELETE_ARCHIVES = 'contao_user.glossaryp.delete';
}
