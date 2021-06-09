<?php
/**
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @author      Sebastian Zoglowek <https://github.com/zoglo>
 * @copyright   Oveleon <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoGlossaryBundle\Controller;

use Contao\StringUtil;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
	 * Return error
	 *
	 * @param $msg
	 *
	 * @return JsonResponse
	 */
	private function error($msg)
	{
		return new JsonResponse(['error' => 1, 'message' => $msg]);
	}
}
