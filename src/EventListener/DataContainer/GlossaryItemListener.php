<?php

namespace Oveleon\ContaoGlossaryBundle\EventListener\DataContainer;

use Contao\Automator;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

class GlossaryItemListener
{
    public function __construct(
        protected ContaoFramework $framework,
        protected Connection $connection,
        protected Security $security
    ){}

    public function checkPermission(DataContainer $dc): void
    {
        $objUser = Controller::getContainer()->get('security.helper')->getUser();

        if ($objUser->isAdmin)
        {
            return;
        }

        // Set the root IDs
        if (empty($objUser->glossarys) || !is_array($objUser->glossarys))
        {
            $root = [0];
        }
        else
        {
            $root = $objUser->glossarys;
        }

        $id = strlen(Input::get('id')) ? Input::get('id') : $dc->currentPid;
        $db = Database::getInstance();

        // Check current action
        switch (Input::get('act'))
        {
            case 'paste':
            case 'select':
            // Check currentPid
            if (!in_array($dc->currentPid, $root))
            {
                throw new AccessDeniedException('Not enough permissions to access glossary ID ' . $id . '.');
            }
            break;

            case 'create':
                if (!Input::get('pid') || !in_array(Input::get('pid'), $root))
                {
                    throw new AccessDeniedException('Not enough permissions to create glossary items in glossary ID ' . Input::get('pid') . '.');
                }
                break;

            case 'cut':
            case 'copy':
                if (Input::get('act') == 'cut' && Input::get('mode') == 1)
                {
                    $objGlossary = $db->prepare("SELECT pid FROM tl_glossary_item WHERE id=?")
                        ->limit(1)
                        ->execute(Input::get('pid'))
                    ;

                    if ($objGlossary->numRows < 1)
                    {
                        throw new AccessDeniedException('Invalid glossary item ID '.Input::get('pid').'.');
                    }

                    $pid = $objGlossary->pid;
                }
                else
                {
                    $pid = Input::get('pid');
                }

                if (!in_array($pid, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' glossary item ID '.$id.' to glossary ID '.$pid.'.');
                }
            // no break

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
                $objGlossary = $db->prepare("SELECT pid FROM tl_glossary_item WHERE id=?")
                    ->limit(1)
                    ->execute($id)
                ;

                if ($objGlossary->numRows < 1)
                {
                    throw new AccessDeniedException('Invalid glossary item ID '.$id.'.');
                }

                if (!in_array($objGlossary->pid, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' glossary item ID '.$id.' of glossary  ID '.$objGlossary->pid.'.');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!in_array($id, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to access glossary ID '.$id.'.');
                }

                $objGlossary = $db->prepare("SELECT id FROM tl_glossary_item WHERE pid=?")
                    ->execute($id);

                $objSession = System::getContainer()->get('request_stack')->getSession();

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objGlossary->fetchEach('id'));
                $objSession->replace($session);
                break;

            default:
                if (Input::get('act'))
                {
                    throw new AccessDeniedException('Invalid command "'.Input::get('act').'".');
                }

                if (!in_array($id, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to access glossary ID '.$id.'.');
                }
                break;
        }
    }

    /**
     * Check for modified glossary items and update the XML files if necessary.
     */
    public function generateSitemap(): void
    {
        /** @var SessionInterface $objSession */
        $objSession = System::getContainer()->get('request_stack')->getSession();

        $session = $objSession->get('glossaryitems_updater');

        if (empty($session) || !is_array($session))
        {
            return;
        }

        $automator = new Automator();
        $automator->generateSitemap();

        $objSession->set('glossaryitems_updater', null);
    }


    /**
     * Schedule a glossary item update.
     *
     * This method is triggered when a single glossary item or multiple glossary items
     * are modified (edit/editAll), moved (cut/cutAll) or deleted (delete/deleteAll).
     * Since duplicated items are unpublished by default, it is not necessary to
     * schedule updates on copyAll as well.
     */
    public function scheduleUpdate(DataContainer $dc): void
    {
        // Return if there is no ID
        if (!$dc->activeRecord || !$dc->activeRecord->pid || Input::get('act') == 'copy')
        {
            return;
        }

        /** @var SessionInterface $objSession */
        $objSession = System::getContainer()->get('request_stack')->getSession();

        // Store the ID in the session
        $session = $objSession->get('glossaryitems_updater');
        $session[] = $dc->activeRecord->pid;
        $objSession->set('glossaryitems_updater', array_unique($session));
    }

    public function addSitemapCacheInvalidationTag(DataContainer $dc, array $tags): array
    {
        $archiveModel = GlossaryModel::findByPk($dc->activeRecord->pid);
        $pageModel = PageModel::findWithDetails($archiveModel->jumpTo);

        if (null === $pageModel)
        {
            return $tags;
        }

        return array_merge($tags, ['contao.sitemap.'.$pageModel->rootId]);
    }

    /**
     * Set group by keyword.
     */
    public function setGlossaryItemGroup(DataContainer $dc): void
    {
        $newGroup = mb_strtoupper(mb_substr($dc->activeRecord->keyword, 0, 1, 'UTF-8'));

        if ($dc->activeRecord->letter !== $newGroup)
        {
            $db = Database::getInstance();
            $db->prepare("UPDATE tl_glossary_item SET letter=? WHERE id=?")
               ->execute($newGroup, $dc->id);
        }
    }

    public function listItems(array $arrRow): string
    {
        return '<div class="tl_content_left">'.$arrRow['keyword'].'</div>';
    }
}
