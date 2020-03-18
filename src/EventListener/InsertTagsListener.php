<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Oveleon\ContaoGlossaryBundle\GlossaryItem;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;

/**
 * @internal
 */
class InsertTagsListener
{
    private const SUPPORTED_TAGS = [
        'glossaryitem',
        'glossaryitem_open',
        'glossaryitem_url',
        'glossaryitem_keyword',
        'glossaryitem_teaser',
    ];

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @return string|false
     */
    public function onReplaceInsertTags(string $tag, bool $useCache, $cacheValue, array $flags)
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if (\in_array($key, self::SUPPORTED_TAGS, true)) {
            return $this->replaceGlossaryInsertTags($key, $elements[1], $flags);
        }

        return false;
    }

    private function replaceGlossaryInsertTags(string $insertTag, string $idOrAlias, array $flags): string
    {
        $this->framework->initialize();

        /** @var GlossaryItemModel $adapter */
        $adapter = $this->framework->getAdapter(GlossaryItemModel::class);

        if (null === ($model = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        /** @var GlossaryItem $glossaryItem */
        $glossaryItem = $this->framework->getAdapter(GlossaryItem::class);

        switch ($insertTag) {
            case 'glossaryitem':
                return sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $glossaryItem->generateUrl($model, \in_array('absolute', $flags, true)),
                    \StringUtil::specialchars($model->keyword),
                    $model->keyword
                );

            case 'glossaryitem_open':
                return sprintf(
                    '<a href="%s" title="%s">',
                    $glossaryItem->generateUrl($model, \in_array('absolute', $flags, true)),
                    \StringUtil::specialchars($model->keyword)
                );

            case 'glossaryitem_url':
                return $glossaryItem->generateUrl($model, \in_array('absolute', $flags, true));

            case 'glossaryitem_keyword':
                return \StringUtil::specialchars($model->keyword);

            case 'glossaryitem_teaser':
                return \StringUtil::toHtml5($model->teaser);
        }

        return '';
    }
}
