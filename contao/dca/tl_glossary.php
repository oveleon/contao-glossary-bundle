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
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\Security\ContaoGlossaryPermissions;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

$GLOBALS['TL_DCA']['tl_glossary'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_glossary_item'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'markAsCopy' => 'title',
        'onload_callback' => [
            ['tl_glossary', 'checkPermission'],
        ],
        'oncreate_callback' => [
            ['tl_glossary', 'adjustPermissions'],
        ],
        'oncopy_callback' => [
            ['tl_glossary', 'adjustPermissions'],
        ],
        'oninvalidate_cache_tags_callback' => [
            ['tl_glossary', 'addSitemapCacheInvalidationTag'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['title'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'table=tl_glossary_item',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'href' => 'act=edit',
                'icon' => 'header.svg',
                'button_callback' => ['tl_glossary', 'editHeader'],
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
                'button_callback' => ['tl_glossary', 'copyArchive'],
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
                'button_callback' => ['tl_glossary', 'deleteArchive'],
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['protected'],
        'default' => '{title_legend},title,jumpTo;{template_legend},glossaryHoverCardTemplate;{image_legend},hoverCardImgSize;{protected_legend:hide},protected',
    ],

    // Subpalettes
    'subpalettes' => [
        'protected' => 'groups',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary']['tstamp'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'jumpTo' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary']['jumpTo'],
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'glossaryHoverCardTemplate' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary']['glossaryHoverCardTemplate'],
            'default' => 'hovercard_glossary_default',
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => static fn () => Controller::getTemplateGroup('hovercard_glossary_'),
            'eval' => ['mandatory' => true, 'chosen' => true, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(64) NOT NULL default 'hovercard_glossary_default'",
        ],
        'hoverCardImgSize' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary']['hoverCardImgSize'],
            'exclude' => true,
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'options_callback' => static fn () => System::getContainer()->get('contao.image.image_sizes')->getOptionsForUser(BackendUser::getInstance()),
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'protected' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary']['protected'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'groups' => [
            'label' => &$GLOBALS['TL_LANG']['tl_glossary']['groups'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_member_group.name',
            'eval' => ['mandatory' => true, 'multiple' => true],
            'sql' => 'blob NULL',
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
    ],
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class tl_glossary extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import(BackendUser::class, 'User');
    }

    /**
     * Check permissions to edit table tl_glossary.
     *
     * @throws AccessDeniedException
     */
    public function checkPermission(): void
    {
        if ($this->User->isAdmin)
        {
            return;
        }

        // Set root IDs
        if (empty($this->User->glossarys) || !is_array($this->User->glossarys))
        {
            $root = [0];
        }
        else
        {
            $root = $this->User->glossarys;
        }

        $GLOBALS['TL_DCA']['tl_glossary']['list']['sorting']['root'] = $root;

        // Check permissions to add archives
        if (!$this->User->hasAccess('create', 'glossaryp'))
        {
            $GLOBALS['TL_DCA']['tl_glossary']['config']['closed'] = true;
        }

        /** @var SessionInterface $objSession */
        $objSession = System::getContainer()->get('session');

        // Check current action
        switch (Input::get('act'))
        {
            case 'select':
                // Allow
                break;

            case 'create':
                if (!$this->User->hasAccess('create', 'glossaryp'))
                {
                    throw new AccessDeniedException('Not enough permissions to create glossaries.');
                }
                break;

            case 'edit':
            case 'copy':
            case 'delete':
            case 'show':
                if (!in_array(Input::get('id'), $root) || ('delete' === Input::get('act') && !$this->User->hasAccess('delete', 'glossaryp')))
                {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' glossary ID '.Input::get('id').'.');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'copyAll':
                $session = $objSession->all();

                if ('deleteAll' === Input::get('act') && !$this->User->hasAccess('delete', 'glossaryp'))
                {
                    $session['CURRENT']['IDS'] = [];
                }
                else
                {
                    $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $root);
                }
                $objSession->replace($session);
                break;

            default:
                if (Input::get('act'))
                {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' glossary.');
                }
                break;
        }
    }

    /**
     * Add the glossary to the permissions.
     *
     * @param $insertId
     */
    public function adjustPermissions($insertId): void
    {
        // The oncreate_callback passes $insertId as second argument
        if (4 === func_num_args())
        {
            $insertId = func_get_arg(1);
        }

        if ($this->User->isAdmin)
        {
            return;
        }

        // Set root IDs
        if (empty($this->User->glossarys) || !is_array($this->User->glossarys))
        {
            $root = [0];
        }
        else
        {
            $root = $this->User->glossarys;
        }

        // The glossary is enabled already
        if (in_array($insertId, $root))
        {
            return;
        }

        /** @var AttributeBagInterface $objSessionBag */
        $objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');

        $arrNew = $objSessionBag->get('new_records');

        if (is_array($arrNew['tl_glossary']) && in_array($insertId, $arrNew['tl_glossary']))
        {
            // Add the permissions on group level
            if ('custom' !== $this->User->inherit)
            {
                $objGroup = $this->Database->execute('SELECT id, glossarys, glossaryp FROM tl_user_group WHERE id IN('.implode(',', array_map('\intval', $this->User->groups)).')');

                while ($objGroup->next())
                {
                    $arrGlossaryp = StringUtil::deserialize($objGroup->glossaryp);

                    if (is_array($arrGlossaryp) && in_array('create', $arrGlossaryp))
                    {
                        $arrGlossarys = StringUtil::deserialize($objGroup->glossarys, true);
                        $arrGlossarys[] = $insertId;

                        $this->Database->prepare('UPDATE tl_user_group SET glossarys=? WHERE id=?')
                            ->execute(serialize($arrGlossarys), $objGroup->id)
                        ;
                    }
                }
            }

            // Add the permissions on user level
            if ('group' !== $this->User->inherit)
            {
                $objUser = $this->Database->prepare('SELECT glossarys, glossaryp FROM tl_user WHERE id=?')
                    ->limit(1)
                    ->execute($this->User->id)
                ;

                $arrGlossaryp = StringUtil::deserialize($objUser->glossaryp);

                if (is_array($arrGlossaryp) && in_array('create', $arrGlossaryp))
                {
                    $arrGlossarys = StringUtil::deserialize($objUser->glossarys, true);
                    $arrGlossarys[] = $insertId;

                    $this->Database->prepare('UPDATE tl_user SET glossarys=? WHERE id=?')
                        ->execute(serialize($arrGlossarys), $this->User->id)
                    ;
                }
            }

            // Add the new element to the user object
            $root[] = $insertId;
            $this->User->glossarys = $root;
        }
    }

    /**
     * Return the edit header button.
     */
    public function editHeader(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_glossary') ? '<a href="' . Backend::addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
    }

    /**
     * Return the copy archive button.
     */
    public function copyArchive(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return System::getContainer()->get('security.helper')->isGranted(ContaoGlossaryPermissions::USER_CAN_CREATE_ARCHIVES) ? '<a href="' . Backend::addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
    }

    /**
     * Return the delete archive button.
     */
    public function deleteArchive(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return System::getContainer()->get('security.helper')->isGranted(ContaoGlossaryPermissions::USER_CAN_DELETE_ARCHIVES) ? '<a href="' . Backend::addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
    }

    /**
     * @param DataContainer $dc
     *
     * @return array
     */
    public function addSitemapCacheInvalidationTag($dc, array $tags)
    {
        $pageModel = PageModel::findWithDetails($dc->activeRecord->jumpTo);

        if (null === $pageModel)
        {
            return $tags;
        }

        return array_merge($tags, ['contao.sitemap.'.$pageModel->rootId]);
    }
}
