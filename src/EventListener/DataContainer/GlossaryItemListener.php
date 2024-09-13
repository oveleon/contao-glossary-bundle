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

use Contao\Automator;
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use Contao\User;
use Doctrine\DBAL\Connection;
use Oveleon\ContaoGlossaryBundle\Glossary;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;
use Oveleon\ContaoGlossaryBundle\Utils\AliasException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GlossaryItemListener
{
    public function __construct(
        protected ContaoFramework $framework,
        protected Connection $connection,
    ) {
    }

    /**
     * Return the SERP URL.
     */
    public function getSerpUrl(GlossaryItemModel $model): string
    {
        return Glossary::generateUrl($model, true);
    }

    /**
     * Return the title tag from the associated page layout.
     */
    public function getTitleTag(GlossaryItemModel $model): string
    {
        if (!$glossary = GlossaryModel::findById($model->pid))
        {
            return '';
        }

        if (!$page = PageModel::findById($glossary->jumpTo))
        {
            return '';
        }

        $page->loadDetails();

        if (!$layout = LayoutModel::findById($page->layout))
        {
            return '';
        }

        $origObjPage = $GLOBALS['objPage'] ?? null;

        // Override the global page object, so we can replace the insert tags
        $GLOBALS['objPage'] = $page;

        $title = implode(
            '%s',
            array_map(
                static fn ($strVal) => str_replace('%', '%%', System::getContainer()->get('contao.insert_tag.parser')->replaceInline($strVal)),
                explode('{{page::pageTitle}}', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}', 2),
            ),
        );

        $GLOBALS['objPage'] = $origObjPage;

        return $title;
    }

    /**
     * Auto-generate the glossary item alias if it has not been set yet.
     *
     * @throws AliasException
     */
    #[AsCallback(table: 'tl_glossary_item', target: 'fields.alias.save')]
    public function generateAlias(mixed $varValue, DataContainer $dc): string
    {
        $aliasExists = static function (string $alias) use ($dc): bool
        {
            $result = Database::getInstance()
                ->prepare('SELECT id FROM tl_glossary_item WHERE alias=? AND id!=?')
                ->execute($alias, $dc->id)
            ;

            return $result->numRows > 0;
        };

        // Generate alias if there is none
        if (!$varValue)
        {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->keyword, GlossaryModel::findById($dc->activeRecord->pid)->jumpTo, $aliasExists);
        }
        elseif (preg_match('/^[1-9]\d*$/', (string) $varValue))
        {
            throw new AliasException(\sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        }
        elseif ($aliasExists($varValue))
        {
            throw new AliasException(\sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    /**
     * Get all articles and return them as array.
     */
    #[AsCallback(table: 'tl_glossary_item', target: 'fields.articleId.options')]
    public function getArticleAlias(DataContainer $dc): array
    {
        $arrPids = [];
        $arrAlias = [];

        $db = Database::getInstance();
        $user = BackendUser::getInstance();

        if (!$user->isAdmin)
        {
            foreach ($user->pagemounts as $id)
            {
                $arrPids[] = [$id];
                $arrPids[] = $db->getChildRecords($id, 'tl_page');
            }

            if ([] !== $arrPids)
            {
                $arrPids = array_merge(...$arrPids);
            }
            else
            {
                return $arrAlias;
            }

            $objAlias = $db->execute('SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN('.implode(',', array_map('\intval', array_unique($arrPids))).') ORDER BY parent, a.sorting');
        }
        else
        {
            $objAlias = $db->execute('SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting');
        }

        if ($objAlias->numRows)
        {
            System::loadLanguageFile('tl_article');

            while ($objAlias->next())
            {
                $arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title.' ('.($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?? $objAlias->inColumn).', ID '.$objAlias->id.')';
            }
        }

        return $arrAlias;
    }

    /**
     * Add the source options depending on the allowed fields.
     */
    #[AsCallback(table: 'tl_glossary_item', target: 'fields.source.options')]
    public function getSourceOptions(DataContainer $dc): array
    {
        /** @var BackendUser|User $user */
        $user = BackendUser::getInstance();

        if ($user->isAdmin)
        {
            return ['default', 'internal', 'article', 'external'];
        }

        $arrOptions = ['default'];

        // Add the "internal" option
        if ($user->hasAccess('tl_glossary_item::jumpTo', 'alexf')) /** @phpstan-ignore-line */
        {
            $arrOptions[] = 'internal';
        }

        // Add the "article" option
        if ($user->hasAccess('tl_glossary_item::articleId', 'alexf')) /** @phpstan-ignore-line */
        {
            $arrOptions[] = 'article';
        }

        // Add the "external" option
        if ($user->hasAccess('tl_glossary_item::url', 'alexf')) /** @phpstan-ignore-line */
        {
            $arrOptions[] = 'external';
        }

        // Add the option currently set
        if ($dc->activeRecord?->source)
        {
            $arrOptions[] = $dc->activeRecord->source;
            $arrOptions = array_unique($arrOptions);
        }

        return $arrOptions;
    }

    public function checkPermission(DataContainer $dc): void
    {
        $objUser = Controller::getContainer()->get('security.helper')->getUser();

        if ($objUser->isAdmin)
        {
            return;
        }

        // Set the root IDs
        if (empty($objUser->glossarys) || !\is_array($objUser->glossarys))
        {
            $root = [0];
        }
        else
        {
            $root = $objUser->glossarys;
        }

        $id = 0 !== \strlen(Input::get('id')) ? Input::get('id') : $dc->currentPid;
        $db = Database::getInstance();

        // Check current action
        switch (Input::get('act'))
        {
            case 'paste':
            case 'select':
                // Check currentPid
                if (!\in_array($dc->currentPid, $root, true))
                {
                    throw new AccessDeniedException('Not enough permissions to access glossary ID '.$id.'.');
                }
                break;

            case 'create':
                if (!Input::get('pid') || !\in_array(Input::get('pid'), $root, true))
                {
                    throw new AccessDeniedException('Not enough permissions to create glossary items in glossary ID '.Input::get('pid').'.');
                }
                break;

            case 'cut':
            case 'copy':
                if ('cut' === Input::get('act') && 1 === (int) Input::get('mode'))
                {
                    $objGlossary = $db->prepare('SELECT pid FROM tl_glossary_item WHERE id=?')
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

                if (!\in_array($pid, $root, true))
                {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' glossary item ID '.$id.' to glossary ID '.$pid.'.');
                }
                // no break

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
                $objGlossary = $db->prepare('SELECT pid FROM tl_glossary_item WHERE id=?')
                    ->limit(1)
                    ->execute($id)
                ;

                if ($objGlossary->numRows < 1)
                {
                    throw new AccessDeniedException('Invalid glossary item ID '.$id.'.');
                }

                if (!\in_array($objGlossary->pid, $root, true))
                {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' glossary item ID '.$id.' of glossary  ID '.$objGlossary->pid.'.');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!\in_array($id, $root, true))
                {
                    throw new AccessDeniedException('Not enough permissions to access glossary ID '.$id.'.');
                }

                $objGlossary = $db->prepare('SELECT id FROM tl_glossary_item WHERE pid=?')
                    ->execute($id)
                ;

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

                if (!\in_array($id, $root, true))
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

        if (empty($session) || !\is_array($session))
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
        if (!$dc->activeRecord || !$dc->activeRecord->pid || 'copy' === Input::get('act'))
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
        $archiveModel = GlossaryModel::findById($dc->activeRecord->pid);
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
            $db->prepare('UPDATE tl_glossary_item SET letter=? WHERE id=?')
               ->execute($newGroup, $dc->id)
            ;
        }
    }

    public function listItems(array $arrRow): string
    {
        return '<div class="tl_content_left">'.$arrRow['keyword'].'</div>';
    }
}
