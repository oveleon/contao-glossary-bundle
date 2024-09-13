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

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\PageModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * @ServiceTag("kernel.event_listener", event=ContaoCoreEvents::SITEMAP)
 */
class SitemapListener
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(SitemapEvent $event): void
    {
        $arrRoot = $this->framework->createInstance(Database::class)->getChildRecords($event->getRootPageIds(), 'tl_page');

        // Early return here in the unlikely case that there are no pages
        if (empty($arrRoot))
        {
            return;
        }

        $arrPages = [];
        $time = time();

        // Get all glossaries
        $objGlossaries = $this->framework->getAdapter(GlossaryModel::class)->findByProtected('');

        if (null === $objGlossaries)
        {
            return;
        }

        // Walk through each glossary
        foreach ($objGlossaries as $objGlossary)
        {
            // Skip glossaries  without target page
            if (!$objGlossary->jumpTo)
            {
                continue;
            }

            // Skip glossaries outside the root nodes
            if (!\in_array($objGlossary->jumpTo, $arrRoot, true))
            {
                continue;
            }

            $objParent = $this->framework->getAdapter(PageModel::class)->findWithDetails($objGlossary->jumpTo);

            // The target page does not exist
            if (null === $objParent)
            {
                continue;
            }

            // The target page has not been published
            if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time))
            {
                continue;
            }

            // The target page is protected
            if ($objParent->protected)
            {
                continue;
            }

            // The target page is exempt from the sitemap
            if ('noindex,nofollow' === $objParent->robots)
            {
                continue;
            }

            // Get the items
            $objGlossaryItems = $this->framework->getAdapter(GlossaryItemModel::class)->findPublishedDefaultByPid($objGlossary->id);

            if (null === $objGlossaryItems)
            {
                continue;
            }

            foreach ($objGlossaryItems as $objGlossaryItem)
            {
                $arrPages[] = $objParent->getAbsoluteUrl('/'.($objGlossaryItem->alias ?: $objGlossaryItem->id));
            }
        }

        foreach ($arrPages as $strUrl)
        {
            $this->addUrlToDefaultUrlSet($strUrl, $event);
        }
    }

    private function addUrlToDefaultUrlSet(string $url, SitemapEvent $event): self
    {
        $sitemap = $event->getDocument();
        $urlSet = $sitemap->getElementsByTagNameNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset')->item(0);

        if (null === $urlSet)
        {
            return $this;
        }

        $loc = $sitemap->createElement('loc', $url);
        $urlEl = $sitemap->createElement('url');
        $urlEl->appendChild($loc);
        $urlSet->appendChild($urlEl);

        $sitemap->appendChild($urlSet);

        return $this;
    }
}
