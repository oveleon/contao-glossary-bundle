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

namespace Oveleon\ContaoGlossaryBundle\Controller;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Exception;
use Oveleon\ContaoGlossaryBundle\Glossary;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\GlossaryModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/glossary", defaults={"_scope" = "frontend"})
 */
class GlossaryController extends AbstractController
{
    public function __construct(private ContaoFramework $framework) {}

    /**
     * @Route("/glossarizer", name="glossary_table")
     */
    public function showGlossarizer(Request $request): JsonResponse
    {
        $this->framework->initialize();

        $objGlossaryItems = GlossaryItemModel::findAll();

        $arrResponse = [];

        if (null === $objGlossaryItems)
        {
            return new JsonResponse($arrResponse);
        }

        while ($objGlossaryItems->next())
        {
            $strTerm = $objGlossaryItems->keyword;

            $arrKeywords = StringUtil::deserialize($objGlossaryItems->keywords, true);

            foreach ($arrKeywords as $strKeyword)
            {
                if (!empty($strKeyword))
                {
                    $strTerm .= ', '.$strKeyword;
                }
            }

            $arrResponse[] = [
                'term' => $strTerm,
                'description' => strip_tags($objGlossaryItems->teaser),
            ];
        }

        return new JsonResponse($arrResponse);
    }

    /**
     * @Route("/info", name="glossary_descriptions")
     */
    public function showGlossaryDescriptions(Request $request): JsonResponse
    {
        $this->framework->initialize();

        $objGlossaryItems = GlossaryItemModel::findAll();

        $arrResponse = [];

        if (null === $objGlossaryItems)
        {
            return new JsonResponse($arrResponse);
        }

        while ($objGlossaryItems->next())
        {
            $arrResponse[$objGlossaryItems->id] = [
                strip_tags($objGlossaryItems->teaser)
            ];
        }

        return new JsonResponse($arrResponse);
    }

    /**
     * @Route("/item/{id}/json", name="glossary_item_json")
     */
    public function getGlossaryItem(Request $request, $id): JsonResponse
    {
        $this->framework->initialize();

        $objGlossaryItem = GlossaryItemModel::findPublishedById($id);

        if (null === $objGlossaryItem)
        {
            return $this->error('No result found', 404);
        }

        $arrResponse = [
            'title' => $objGlossaryItem->keyword,
            'url' => Glossary::generateUrl($objGlossaryItem, true),
            'teaser' => Controller::replaceInsertTags($objGlossaryItem->teaser), // (see #13)
            'class' => $objGlossaryItem->cssClass,
        ];

        $objContentElements = ContentModel::findPublishedByPidAndTable($id, 'tl_glossary_item');

        if (null === $objContentElements)
        {
            return new JsonResponse($arrResponse);
        }

        $arrContent = [];

        while ($objContentElements->next())
        {
            $arrContent[] =
            [
                'content' => Controller::getContentElement($objContentElements->id),
            ];
        }

        $arrResponse['items'] = $arrContent;

        return new JsonResponse($arrResponse);
    }

    /**
     * @Route("/item/{id}/html", name="glossary_item_html")
     *
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function getGlossaryItemContent(Request $request, $id)
    {
        $this->framework->initialize();

        $objGlossaryItem = GlossaryItemModel::findPublishedById($id);
        $objGlossaryArchive = GlossaryModel::findByPk($objGlossaryItem->pid);

        if (null === $objGlossaryItem)
        {
            return $this->error('No result found', 404);
        }

        return new Response(Glossary::parseGlossaryItem($objGlossaryItem, $objGlossaryArchive->glossaryHoverCardTemplate, $objGlossaryArchive->hoverCardImgSize));
    }

    /**
     * Return error
     */
    private function error(string $msg, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $msg, 'status' => $status], $status);
    }
}
