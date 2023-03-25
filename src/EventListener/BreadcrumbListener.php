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
use Contao\Input;
use Contao\StringUtil;
use Oveleon\ContaoGlossaryBundle\Glossary;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;

/**
 * @internal
 */
class BreadcrumbListener
{
    public function __invoke(array $items): array
    {
        $alias = Input::get('items');

        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && isset($_GET['auto_item']) && Config::get('useAutoItem')) {
            $alias = Input::get('auto_item');
        }

        if ($alias && ($glossaryItem = GlossaryItemModel::findPublishedByIdOrAlias($alias)) !== null) {
            // Mark the last item as inactive
            $items[\count($items) - 1]['href'] = $GLOBALS['objPage']->getFrontendUrl();
            $items[\count($items) - 1]['isActive'] = false;

            // Add the new item
            $items[] = [
                'isRoot' => false,
                'isActive' => true,
                'href' => Glossary::generateUrl($glossaryItem),
                'title' => StringUtil::specialchars($glossaryItem->keyword),
                'link' => $glossaryItem->keyword,
                'data' => $glossaryItem->row(),
                'class' => '',
            ];
        }

        return $items;
    }
}
