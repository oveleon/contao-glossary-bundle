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

namespace Oveleon\ContaoGlossaryBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendTemplate;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
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

        if ($pageModel->disableGlossary)
        {
            return;
        }

        // Get Rootpage Settings
        $objRootPage = PageModel::findByPk($pageModel->rootId);

        if (null === $objRootPage || !$objRootPage->activateGlossary)
        {
            return;
        }

        $glossaryArchives = StringUtil::deserialize($objRootPage->glossaryArchives);

        if (null === $glossaryArchives)
        {
            return;
        }

        $objGlossaryArchives = GlossaryModel::findMultipleByIds($glossaryArchives);

        if (null === $objGlossaryArchives)
        {
            return;
        }

        // Load glossary configuration template
        $objTemplate = new FrontendTemplate($objRootPage->glossaryConfigTemplate ?: 'config_glossary_default');

        $objGlossaryItems = GlossaryItemModel::findPublishedByPids($glossaryArchives);

        $glossaryConfig = null;

        if (null !== $objGlossaryItems)
        {
            $arrGlossaryItems = [];

            foreach ($objGlossaryItems as $objGlossaryItem)
            {
                // Check if keywords exist
                if (array_filter($arrKeywords = StringUtil::deserialize($objGlossaryItem->keywords, true)))
                {
                    $arrGlossaryItems[] = [
                        'id' => $objGlossaryItem->id,

                        // Catch wrongly entered empty keywords and filter them out
                        'keywords' => array_values(array_filter($arrKeywords)),
                        'url' => Glossary::generateUrl($objGlossaryItem),

                        // Case-sensitive search
                        'cs' => $objGlossaryItem->sensitiveSearch ? 1 : 0,
                    ];
                }
            }

            if (!empty($arrGlossaryItems))
            {
                $glossaryConfig = json_encode($arrGlossaryItems);
            }
        }

        switch ($objRootPage->glossaryHoverCard) {
            case 'enabled':
                $objTemplate->hoverCardMode = true;
                break;

            default:
                $objTemplate->hoverCardMode = false;
                break;
        }

        // Disable glossary cache in contao debug mode
        $objTemplate->cacheStatus = !Config::get('debugMode');

        $objTemplate->glossaryConfig = $glossaryConfig;

        $GLOBALS['TL_BODY'][] = $objTemplate->parse();
    }
}
