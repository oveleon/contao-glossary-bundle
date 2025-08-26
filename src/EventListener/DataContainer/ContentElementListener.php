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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentElementListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    /**
     * @throws Exception
     */
    public function checkPermission(DataContainer $dc): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser || $user->isAdmin)
        {
            return;
        }

        // Set the root IDs
        if (empty($user->glossarys) || !\is_array($user->glossarys))
        {
            $root = [0];
        }
        else
        {
            $root = $user->glossarys;

            foreach ($root as $k => $v)
            {
                $root[$k] = (int) $v;
            }
        }

        // Check current action
        switch (Input::get('act'))
        {
            case '': // empty
            case 'paste':
            case 'select':
                // Check access to the glossary item
                $this->checkAccessToElement($dc->currentPid, $root, true);
                break;

            case 'create':
                // Check access to the parent element if a content element is created
                $this->checkAccessToElement((int) Input::get('pid'), $root, 2 === (int) Input::get('mode'));
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                // Check access to the parent element if a content element is moved
                if (\in_array(Input::get('act'), ['cutAll', 'copyAll'], true))
                {
                    $this->checkAccessToElement((int) Input::get('pid'), $root, 2 === (int) Input::get('mode'));
                }

                // ToDo: Use connection or voters in the future...
                $objCes = Database::getInstance()->prepare("SELECT id FROM tl_content WHERE ptable='tl_glossary_item' AND pid=?")
                    ->execute($dc->currentPid)
                ;

                $objSession = $this->requestStack->getSession();

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCes->fetchEach('id'));
                $objSession->replace($session);
                break;

            case 'cut':
            case 'copy':
                // Check access to the parent element if a content element is moved
                $this->checkAccessToElement((int) Input::get('pid'), $root, 2 === (int) Input::get('mode'));
                // no break

            default:
                // Check access to the content element
                $this->checkAccessToElement((int) Input::get('id'), $root);
                break;
        }
    }

    /**
     * @throws Exception
     */
    private function checkAccessToElement(int $id, mixed $root, bool $isPid = false): void
    {
        if ($isPid)
        {
            $objArchive = (object) $this->connection->fetchAssociative(
                'SELECT a.id, n.id AS nid FROM tl_glossary_item n, tl_glossary a WHERE n.id=:id AND n.pid=a.id',
                ['id' => $id],
            );
        }
        else
        {
            $objArchive = (object) $this->connection->fetchAssociative(
                'SELECT a.id, n.id AS nid FROM tl_content c, tl_glossary_item n, tl_glossary a WHERE c.id=:id AND c.pid=n.id AND n.pid=a.id',
                ['id' => $id],
            );
        }

        // FetchOne returns false when nothing if no rows have been found
        if (!$objArchive)
        {
            throw new AccessDeniedException('Invalid glossary item content element ID '.$id.'.');
        }

        if (!\in_array($objArchive->id, $root, true))
        {
            throw new AccessDeniedException('Not enough permissions to modify article ID '.$objArchive->nid.' in glossary ID '.$objArchive->id.'.');
        }
    }
}
