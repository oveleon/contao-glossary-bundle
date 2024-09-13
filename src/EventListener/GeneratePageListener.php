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

namespace Oveleon\ContaoGlossaryBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoGlossaryBundle\Glossary;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;

/**
 * @internal
 */
class GeneratePageListener
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(PageModel $pageModel, LayoutModel $layoutModel, PageRegular $pageRegular): void
    {
        $this->framework->initialize();

        if ($pageModel->disableGlossary)
        {
            return;
        }

        // Get Root page Settings
        $objRootPage = PageModel::findById($pageModel->rootId);

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
        $objGlossaryItems = GlossaryItemModel::findPublishedByPids($glossaryArchives, ['order' => 'LENGTH(tl_glossary_item.keyword) DESC']);
        $glossaryConfig = null;

        $pageId = $pageModel->id;
        $alias = Input::get('items');

        // Set the item from the auto_item parameter
        // ToDo: See #5983
        if (!isset($_GET['items']) && isset($_GET['auto_item']) && Config::get('useAutoItem'))
        {
            $alias = Input::get('auto_item');
        }

        if (null !== $objGlossaryItems)
        {
            $arrGlossaryItems = [];

            foreach ($objGlossaryItems as $objGlossaryItem)
            {
                // Check if keywords exist
                if (array_filter($arrKeywords = StringUtil::deserialize($objGlossaryItem->keywords, true)))
                {
                    switch ($objGlossaryItem->source)
                    {
                        case 'default':
                            if ($alias === $objGlossaryItem->alias)
                            {
                                continue 2; // Skip config entry if we are on the same page
                            }
                            break;

                        case 'internal':
                            if ($pageId === $objGlossaryItem->jumpTo)
                            {
                                continue 2; // Skip config entry if we are on the same page
                            }
                            break;
                    }

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

            if ([] !== $arrGlossaryItems)
            {
                $glossaryConfig = json_encode($arrGlossaryItems);
            }
        }

        $objTemplate->hoverCardMode = match ($objRootPage->glossaryHoverCard)
        {
            'enabled' => true,
            default => false,
        };

        // Adds a 'lang' attribute (#24)
        $objTemplate->language = $pageModel->rootLanguage;

        // Disable glossary cache in contao debug mode
        $blnDebug = System::getContainer()->getParameter('kernel.debug');
        $objTemplate->cacheStatus = !$blnDebug;

        $objTemplate->glossaryConfig = $glossaryConfig;

        $GLOBALS['TL_BODY'][] = $objTemplate->parse();
    }
}
