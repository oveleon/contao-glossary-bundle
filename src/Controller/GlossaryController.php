<?php

/**
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
use Contao\StringUtil;
use Oveleon\ContaoGlossaryBundle\Glossary;
use Oveleon\ContaoGlossaryBundle\GlossaryModel;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ContentApiController provides all routes.
 *
 * @Route(defaults={"_scope" = "frontend"})
 */
class GlossaryController extends AbstractController
{
	/**
	 * @var ContaoFramework
	 */
	private $framework;

	public function __construct(ContaoFramework $framework)
	{
		$this->framework = $framework;
	}

	/**
	 * Runs the command scheduler. (prepare)
	 *
	 * @Route("/api/glossary/glossarizer", name="glossary_table")
	 *
	 * @param Request $request
	 *
	 * @return JsonResponse|string
	 */
	public function showGlossarizer(Request $request)
	{
		$this->framework->initialize();

		$objGlossaryItems = GlossaryItemModel::findAll();

		$arrResponse = array();

		if ($objGlossaryItems === null)
		{
			return new JsonResponse($arrResponse);
		}

		while ($objGlossaryItems->next())
		{
			$strTerm = $objGlossaryItems->keyword;

			$arrKeywords = StringUtil::deserialize($objGlossaryItems->keywords, true);

			foreach($arrKeywords as $strKeyword)
			{
				if (!empty($strKeyword))
				{
					$strTerm .= ', ' . $strKeyword;
				}
			}

			$arrResponse[] = array
			(
			  'term' => $strTerm,
			  'description' => strip_tags($objGlossaryItems->teaser)
			);
		}

		return new JsonResponse($arrResponse);
	}

	/**
	 * Runs the command scheduler. (prepare)
	 *
	 * @Route("/api/glossary/item/{id}/json", name="glossary_item_json")
	 *
	 * @param Request $request
	 * @param $id
	 *
	 * @return JsonResponse|string
	 */
	public function getGlossaryItem(Request $request, $id)
	{
		$this->framework->initialize();

		$objGlossaryItem = GlossaryItemModel::findPublishedById($id);

		if(null === $objGlossaryItem) {
			return $this->error('No result found', 404);
		}

		$arrResponse = array(
		  	'title' 	=> $objGlossaryItem->keyword,
			'url'		=> Glossary::generateUrl($objGlossaryItem, true),
			'teaser'	=> $objGlossaryItem->teaser,
			'class'		=> $objGlossaryItem->cssClass
		);

		$objContentElements = ContentModel::findPublishedByPidAndTable($id,'tl_glossary_item');

		if ($objContentElements === null)
		{
			return new JsonResponse($arrResponse);
		}

		$arrContent = [];

		while ($objContentElements->next())
		{
			$arrContent[] =
			[
			  'content' => Controller::getContentElement($objContentElements->id)
			];
		}

		$arrResponse['items'] = $arrContent;

		return new JsonResponse($arrResponse);
	}

	/**
	 * Runs the command scheduler. (prepare)
	 *
	 * @Route("/api/glossary/item/{id}/html", name="glossary_item_html")
	 *
	 * @param Request $request
	 * @param $id
	 *
	 * @return Response|string
	 */
	public function getGlossaryItemContent(Request $request, $id)
	{
		$this->framework->initialize();

		$objGlossaryItem = GlossaryItemModel::findPublishedById($id);
		$objGlossaryArchive = GlossaryModel::findByPk($objGlossaryItem->pid);

		if(null === $objGlossaryItem) {
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
