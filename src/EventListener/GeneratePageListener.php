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

		// ToDo: Rename
		if ($pageModel->excludeGlossaryTooltips)
		{
			return;
		}

		// Get Rootpage Settings
		$objRootPage = PageModel::findByPk($pageModel->rootId);

		if(null === $objRootPage || !$objRootPage->activateGlossaryTooltips)
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
			$arrArchiveFallbackTemplates[ $objGlossaryArchive->id ] = $objGlossaryArchive->glossaryTooltipTemplate;
		}

		$objGlossaryItems = GlossaryItemModel::findPublishedByPids($glossaryArchives);
		$arrGlossaryItems = [];

		// Helper
		/*$getTemplate = function ($item) use ($arrArchiveFallbackTemplates) {
			if($item->glossaryTooltipTemplate)
			{
				return $item->glossaryTooltipTemplate;
			}

			return $arrArchiveFallbackTemplates[ $item->pid ];
		};*/

		foreach ($objGlossaryItems as $objGlossaryItem)
		{
			if(array_filter($arrKeywords = StringUtil::deserialize($objGlossaryItem->keywords, true)))
			{
				$arrGlossaryItems[] = [
					'id'        => $objGlossaryItem->id,
					'keywords'  => $arrKeywords,
					//'template'  => $getTemplate($objGlossaryItem),
					'url'       => Glossary::generateUrl($objGlossaryItem)
				];
			}
		}

		// Load glossary configuration template
		$objTemplate = new FrontendTemplate($objRootPage->glossaryConfigTemplate ?: 'config_glossary_default');
		$objTemplate->glossaryConfig = json_encode($arrGlossaryItems);

		$GLOBALS['TL_BODY'][] = $objTemplate->parse();
	}
}
