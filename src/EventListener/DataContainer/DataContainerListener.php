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

namespace Oveleon\ContaoGlossaryBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Oveleon\ContaoGlossaryBundle\Security\ContaoGlossaryPermissions;
use Symfony\Bundle\SecurityBundle\Security;

class DataContainerListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Security $security,
    ) {
    }

    /**
     * @throws Exception
     */
    #[AsCallback(table: 'tl_page', target: 'fields.glossaryArchives.options')]
    #[AsCallback(table: 'tl_module', target: 'fields.glossary_archives.options')]
    public function getGlossaries(): array
    {
        $user = $this->security->getUser();

        if (
            !$user instanceof BackendUser
            || (!$user->isAdmin && !\is_array($user->glossarys))
        ) {
            return [];
        }

        $return = [];

        $glossaries = $this->connection->fetchAllAssociative(
            'SELECT id, title FROM tl_glossary ORDER BY title',
        );

        foreach ($glossaries as $glossary)
        {
            if ($user->isAdmin || $this->security->isGranted(ContaoGlossaryPermissions::USER_CAN_EDIT_ARCHIVE, $glossary['pid']))
            {
                $return[$glossary['id']] = $glossary['title'];
            }
        }

        return $return;
    }

    /**
     * @throws Exception
     */
    #[AsCallback(table: 'tl_module', target: 'fields.glossary_readerModule.options')]
    public function getReaderModules(): array
    {
        $return = [];

        $modules = $this->connection->fetchAllAssociative(
            'SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type=:type ORDER BY t.name, m.name',
            ['type' => 'glossaryreader'],
        );

        foreach ($modules as $module)
        {
            $return[$module['theme']][$module['id']] = $module['name'].' (ID '.$module['id'].')';
        }

        return $return;
    }
}
