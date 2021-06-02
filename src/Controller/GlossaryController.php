<?php
/**
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @author      Sebastian Zoglowek <https://github.com/zoglo>
 * @copyright   Oveleon <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoGlossaryBundle\Controller;

use Contao\FrontendTemplate;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
	 * @Route("/api/glossary", name="glossary_table")
	 *
	 * @param Request $request
	 *
	 * @return JsonResponse|string
	 */
	public function show(Request $request)
	{
		$this->framework->initialize();

		$objGlossaryItems = GlossaryItemModel::findAll();

		$arrResult = $objGlossaryItems->fetchAll();

		//return new JsonResponse(['type' => $module, 'status' => 'OK']);
		//return new JsonResponse(['type' => 'hello world']);
		return new JsonResponse($arrResult);
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