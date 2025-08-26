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
use Contao\System;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;
use Oveleon\ContaoGlossaryBundle\Utils\GlossaryTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route(path: '/api/glossary', defaults: ['_scope' => 'frontend'])]
class GlossaryRouteController extends AbstractController
{
    use GlossaryTrait;

    public function __construct(private readonly ContaoFramework $framework)
    {
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

        foreach ($objGlossaryItems as $objGlossaryItem)
        {
            $arrResponse[$objGlossaryItem->id] = [
                strip_tags($objGlossaryItem->teaser),
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
            'url' => $this->generateDetailUrl($objGlossaryItem, true),
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

        return new Response($this->parseItem($objGlossaryItem, $objGlossaryArchive->glossaryHoverCardTemplate, $objGlossaryArchive->hoverCardImgSize));
    }

    private function error(string $msg, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $msg, 'status' => $status], $status);
    }
}
