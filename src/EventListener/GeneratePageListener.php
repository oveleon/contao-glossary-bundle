<?php

namespace Oveleon\ContaoGlossaryBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendTemplate;
use Contao\PageRegular;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Oveleon\ContaoGlossaryBundle\Glossary;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\GlossaryModel;

/**
 * @internal
 */
class GeneratePageListener
{
	/**
	 * @var ContaoFramework
	 */
	private $framework;

	public function __construct(ContaoFramework $framework)
	{
		$this->framework = $framework;
	}

	public function __invoke(PageModel $pageModel, LayoutModel $layoutModel, PageRegular $pageRegular): void
	{
		//$this->framework->initialize();

		if ($pageModel->excludeGlossaryHoverCards)
		{
			return;
		}

		// Get Rootpage Settings
		$objRootPage = PageModel::findByPk($pageModel->rootId);

		if(null === $objRootPage || !$objRootPage->activateGlossaryHoverCards)
		{
			return;
		}

		$glossaryArchives = StringUtil::deserialize($objRootPage->glossaryArchives);

		if (null === $glossaryArchives)
		{
			return;
		}

		$objGlossaryArchives = GlossaryModel::findMultipleByIds($glossaryArchives);

		if (null === $objGlossaryArchives) {
			return;
		}

		$arrArchiveFallbackTemplates = [];

		foreach ($objGlossaryArchives as $objGlossaryArchive)
		{
			$arrArchiveFallbackTemplates[ $objGlossaryArchive->id ] = $objGlossaryArchive->glossaryHoverCardTemplate;
		}

		$objGlossaryItems = GlossaryItemModel::findPublishedByPids($glossaryArchives);
		$arrGlossaryItems = [];

		foreach ($objGlossaryItems as $objGlossaryItem)
		{
			if(array_filter($arrKeywords = StringUtil::deserialize($objGlossaryItem->keywords, true)))
			{
				$arrGlossaryItems[] = [
					'id'        => $objGlossaryItem->id,
					'keywords'  => $arrKeywords,
					'url'       => Glossary::generateUrl($objGlossaryItem),

					// Case-sensitive search
					'cs'        => $objGlossaryItem->sensitiveSearch ? 1 : 0
				];
			}
		}

		// Load glossary configuration template
		$objTemplate = new FrontendTemplate($objRootPage->glossaryConfigTemplate ?: 'config_glossary_default');

		//ToDo: Add new setting to tl_page to exclude automatic markup or linking : return null
		$objTemplate->glossaryConfig = json_encode($arrGlossaryItems);

		$GLOBALS['TL_BODY'][] = $objTemplate->parse();
	}
}
