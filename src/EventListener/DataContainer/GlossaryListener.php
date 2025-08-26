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

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\Security\ContaoGlossaryPermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class GlossaryListener
{
    public function __construct(private readonly Security $security)
    {}

    #[AsCallback(table: 'tl_glossary', target: 'list.operations.edit.button')]
    public function edit(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->renderButton($this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_glossary'), $row, $href, $label, $title, $icon, $attributes);
    }

    #[AsCallback(table: 'tl_glossary', target: 'list.operations.copy.button')]
    public function copyArchive(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->renderButton($this->security->isGranted(ContaoGlossaryPermissions::USER_CAN_CREATE_ARCHIVES), $row, $href, $label, $title, $icon, $attributes);
    }

    #[AsCallback(table: 'tl_glossary', target: 'list.operations.delete.button')]
    public function deleteArchive(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->renderButton($this->security->isGranted(ContaoGlossaryPermissions::USER_CAN_DELETE_ARCHIVES), $row, $href, $label, $title, $icon, $attributes);
    }

    #[AsCallback(table: 'tl_glossary', target: 'config.config.oncreate')]
    #[AsCallback(table: 'tl_glossary', target: 'config.config.oncopy')]
    public function adjustPermissions(int|string $insertId): void
    {
        // The oncreate_callback passes $insertId as second argument
        if (4 === \func_num_args())
        {
            $insertId = func_get_arg(1);
        }

        $objUser = $this->security->getUser();

        if ($objUser->isAdmin)
        {
            return;
        }

        // Set root IDs
        if (empty($objUser->glossarys) || !\is_array($objUser->glossarys))
        {
            $root = [0];
        }
        else
        {
            $root = $objUser->glossarys;
        }

        // The glossary is enabled already
        if (\in_array($insertId, $root, true))
        {
            return;
        }

        /** @var AttributeBagInterface $objSessionBag */
        $objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

        $arrNew = $objSessionBag->get('new_records');

        if (\is_array($arrNew['tl_glossary']) && \in_array($insertId, $arrNew['tl_glossary'], true))
        {
            $db = Database::getInstance();

            // Add the permissions on group level
            if ('custom' !== $objUser->inherit)
            {
                $objGroup = $db->execute('SELECT id, glossarys, glossaryp FROM tl_user_group WHERE id IN('.implode(',', array_map('\intval', $objUser->groups)).')');

                while ($objGroup->next())
                {
                    $arrGlossaryp = StringUtil::deserialize($objGroup->glossaryp);

                    if (\is_array($arrGlossaryp) && \in_array('create', $arrGlossaryp, true))
                    {
                        $arrGlossarys = StringUtil::deserialize($objGroup->glossarys, true);
                        $arrGlossarys[] = $insertId;

                        $db->prepare('UPDATE tl_user_group SET glossarys=? WHERE id=?')
                            ->execute(serialize($arrGlossarys), $objGroup->id)
                        ;
                    }
                }
            }

            // Add the permissions on user level
            if ('group' !== $objUser->inherit)
            {
                $objUser = $db->prepare('SELECT glossarys, glossaryp FROM tl_user WHERE id=?')
                    ->limit(1)
                    ->execute($objUser->id)
                ;

                $arrGlossaryp = StringUtil::deserialize($objUser->glossaryp);

                if (\is_array($arrGlossaryp) && \in_array('create', $arrGlossaryp, true))
                {
                    $arrGlossarys = StringUtil::deserialize($objUser->glossarys, true);
                    $arrGlossarys[] = $insertId;

                    $db->prepare('UPDATE tl_user SET glossarys=? WHERE id=?')
                        ->execute(serialize($arrGlossarys), $objUser->id)
                    ;
                }
            }

            // Add the new element to the user object
            $root[] = $insertId;
            $objUser->glossarys = $root;
        }
    }

    #[AsCallback(table: 'tl_glossary', target: 'config.oninvalidate_cache_tags')]
    public function addSitemapCacheInvalidationTag(DataContainer $dc, array $tags): array
    {
        $pageModel = PageModel::findWithDetails($dc->activeRecord->jumpTo);

        if (null === $pageModel)
        {
            return $tags;
        }

        return array_merge($tags, ['contao.sitemap.'.$pageModel->rootId]);
    }

    private function renderButton(bool $granted, array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        if (!$granted)
        {
            return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
        }

        return '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }
}
