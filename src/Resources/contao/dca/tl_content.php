<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @package     contao-glossary-bundle
 * @license     AGPL-3.0
 * @author      Fabian Ekert        <https://github.com/eki89>
 * @author      Sebastian Zoglowek  <https://github.com/zoglo>
 * @copyright   Oveleon             <https://www.oveleon.de/>
 */

use Contao\Backend;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Input;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

// Dynamically add the permission check and parent table
if ('glossary' === Input::get('do'))
{
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_glossary_item';
    array_unshift($GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'], ['tl_content_glossary', 'checkPermission']);
}

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
class tl_content_glossary extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('Contao\BackendUser', 'User');
    }

    /**
     * Check permissions to edit table tl_content.
     */
    public function checkPermission(): void
    {
        if ($this->User->isAdmin)
        {
            return;
        }

        // Set the root IDs
        if (empty($this->User->glossarys) || !is_array($this->User->glossarys))
        {
            $root = [0];
        }
        else
        {
            $root = $this->User->glossarys;
        }

        // Check the current action
        switch (Input::get('act'))
        {
            case '': // empty
            case 'paste':
            case 'create':
            case 'select':
                // Check access to the glossary item
                $this->checkAccessToElement(CURRENT_ID, $root, true);
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                // Check access to the parent element if a content element is moved
                if (in_array(Input::get('act'), ['cutAll', 'copyAll'], true))
                {
                    $this->checkAccessToElement(Input::get('pid'), $root, 2 === Input::get('mode'));
                }

                $objCes = $this->Database->prepare("SELECT id FROM tl_content WHERE ptable='tl_glossary_item' AND pid=?")
                    ->execute(CURRENT_ID)
                ;

                /** @var SessionInterface $objSession */
                $objSession = System::getContainer()->get('session');

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCes->fetchEach('id'));
                $objSession->replace($session);
                break;

            case 'cut':
            case 'copy':
                // Check access to the parent element if a content element is moved
                $this->checkAccessToElement(Input::get('pid'), $root, 2 === Input::get('mode'));
            // no break

            default:
                // Check access to the content element
                $this->checkAccessToElement(Input::get('id'), $root);
                break;
        }
    }

    /**
     * Check access to a particular content element.
     *
     * @param int   $id
     * @param array $root
     * @param bool  $blnIsPid
     *
     * @throws AccessDeniedException
     */
    protected function checkAccessToElement($id, $root, $blnIsPid = false): void
    {
        if ($blnIsPid)
        {
            $objArchive = $this->Database->prepare('SELECT a.id, n.id AS nid FROM tl_glossary_item n, tl_glossary a WHERE n.id=? AND n.pid=a.id')
                ->limit(1)
                ->execute($id)
            ;
        }
        else
        {
            $objArchive = $this->Database->prepare('SELECT a.id, n.id AS nid FROM tl_content c, tl_glossary_item n, tl_glossary a WHERE c.id=? AND c.pid=n.id AND n.pid=a.id')
                ->limit(1)
                ->execute($id)
            ;
        }

        // Invalid ID
        if ($objArchive->numRows < 1)
        {
            throw new AccessDeniedException('Invalid glossary item content element ID '.$id.'.');
        }

        // The glossary is not mounted
        if (!in_array($objArchive->id, $root, true))
        {
            throw new AccessDeniedException('Not enough permissions to modify article ID '.$objArchive->nid.' in glossary ID '.$objArchive->id.'.');
        }
    }
}
