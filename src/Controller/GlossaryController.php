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

namespace Oveleon\ContaoGlossaryBundle\Controller;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\Glossary;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/glossary', defaults: ['_scope' => 'frontend'])]
class GlossaryController extends AbstractController
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    #[Route(path: '/glossarizer', name: 'glossary_table')]
    /**
     * @deprecated since version 2.3 - to be removed in the future
     */
    public function showGlossarizer(): JsonResponse
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

    #[Route(path: '/info', name: 'glossary_descriptions')]
    public function showGlossaryDescriptions(): JsonResponse
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
                strip_tags($objGlossaryItems->teaser),
            ];
        }

        return new JsonResponse($arrResponse);
    }

    #[Route(path: '/item/{id}/json', name: 'glossary_item_json')]
    public function getGlossaryItem(int $id): JsonResponse
    {
        $this->framework->initialize();

        $objGlossaryItem = GlossaryItemModel::findPublishedById($id);

        if (!$objGlossaryItem instanceof GlossaryItemModel)
        {
            return $this->error('No result found', 404);
        }

        $arrResponse = [
            'title' => $objGlossaryItem->keyword,
            'url' => Glossary::generateUrl($objGlossaryItem, true),
            'teaser' => System::getContainer()->get('contao.insert_tag.parser')->replace($objGlossaryItem->teaser), // (see #13)
            'class' => $objGlossaryItem->cssClass,
        ];

        $objContentElements = ContentModel::findPublishedByPidAndTable($id, 'tl_glossary_item');

        if (null === $objContentElements)
        {
            return new JsonResponse($arrResponse);
        }

        $arrContent = [];

        foreach ($objContentElements as $objContentElement)
        {
            $arrContent[] = ['content' => Controller::getContentElement($objContentElement->id)];
        }

        $arrResponse['items'] = $arrContent;

        return new JsonResponse($arrResponse);
    }

    #[Route(path: '/item/{id}/html', name: 'glossary_item_html')]
    public function getGlossaryItemContent(int $id): Response
    {
        $this->framework->initialize();

        $objGlossaryItem = GlossaryItemModel::findPublishedById($id);
        $objGlossaryArchive = GlossaryModel::findById($objGlossaryItem->pid);

        if (!$objGlossaryItem instanceof GlossaryItemModel)
        {
            return $this->error('No result found', 404);
        }

        return new Response(Glossary::parseGlossaryItem($objGlossaryItem, $objGlossaryArchive->glossaryHoverCardTemplate, $objGlossaryArchive->hoverCardImgSize));
    }

    /**
     * Return error.
     */
    private function error(string $msg, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $msg, 'status' => $status], $status);
    }
}
