<?php

namespace Oveleon\ContaoGlossaryBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Oveleon\ContaoGlossaryBundle\Security\ContaoGlossaryPermissions;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Security\Core\Security;

class GlossaryListener
{
    public function __construct(
        protected ContaoFramework $framework,
        protected Connection $connection,
        protected Security $security
    ){}

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
     * Add the glossary to the permissions.
     *
     * @param $insertId
     */
    public function adjustPermissions(int|string $insertId): void
    {
        // The oncreate_callback passes $insertId as second argument
        if (func_num_args() == 4)
        {
            $insertId = func_get_arg(1);
        }

        $objUser = Controller::getContainer()->get('security.helper')->getUser();

        if ($objUser->isAdmin)
        {
            return;
        }

        // Set root IDs
        if (empty($objUser->glossarys) || !is_array($objUser->glossarys))
        {
            $root = [0];
        }
        else
        {
            $root = $objUser->glossarys;
        }

        // The glossary is enabled already
        if (in_array($insertId, $root))
        {
            return;
        }

        /** @var AttributeBagInterface $objSessionBag */
        $objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

        $arrNew = $objSessionBag->get('new_records');

        if (is_array($arrNew['tl_glossary']) && in_array($insertId, $arrNew['tl_glossary']))
        {
            $db = Database::getInstance();

            // Add the permissions on group level
            if ($objUser->inherit != 'custom')
            {
                $objGroup = $db->execute("SELECT id, glossarys, glossaryp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $objUser->groups)) . ")");

                while ($objGroup->next())
                {
                    $arrGlossaryp = StringUtil::deserialize($objGroup->glossaryp);

                    if (is_array($arrGlossaryp) && in_array('create', $arrGlossaryp))
                    {
                        $arrGlossarys = StringUtil::deserialize($objGroup->glossarys, true);
                        $arrGlossarys[] = $insertId;

                        $db->prepare("UPDATE tl_user_group SET glossarys=? WHERE id=?")
                            ->execute(serialize($arrGlossarys), $objGroup->id);
                    }
                }
            }

            // Add the permissions on user level
            if ($objUser->inherit != 'group')
            {
                $objUser = $db->prepare("SELECT glossarys, glossaryp FROM tl_user WHERE id=?")
                    ->limit(1)
                    ->execute($objUser->id);

                $arrGlossaryp = StringUtil::deserialize($objUser->glossaryp);

                if (is_array($arrGlossaryp) && in_array('create', $arrGlossaryp))
                {
                    $arrGlossarys = StringUtil::deserialize($objUser->glossarys, true);
                    $arrGlossarys[] = $insertId;

                    $db->prepare("UPDATE tl_user SET glossarys=? WHERE id=?")
                        ->execute(serialize($arrGlossarys), $objUser->id);
                }
            }

            // Add the new element to the user object
            $root[] = $insertId;
            $objUser->recommendations = $root;
        }
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
