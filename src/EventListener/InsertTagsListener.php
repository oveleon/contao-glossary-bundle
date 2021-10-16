<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Oveleon\ContaoGlossaryBundle\Glossary;
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
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @return string|false
     */
    public function __invoke(string $tag, bool $useCache, $cacheValue, array $flags)
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if (\in_array($key, self::SUPPORTED_TAGS, true)) {
	        return $this->replaceGlossaryInsertTags($key, $elements[1], array_merge($flags, \array_slice($elements, 2)));
        }

        return false;
    }

	private function replaceGlossaryInsertTags(string $insertTag, string $idOrAlias, array $arguments): string
    {
        $this->framework->initialize();

        /** @var GlossaryItemModel $adapter */
        $adapter = $this->framework->getAdapter(GlossaryItemModel::class);

        if (null === ($model = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        /** @var Glossary $glossaryItem */
        $glossaryItem = $this->framework->getAdapter(Glossary::class);

        switch ($insertTag) {
            case 'glossaryitem':
                return sprintf(
                    '<a href="%s" title="%s" data-glossary-id="%s">%s</a>',
                    $glossaryItem->generateUrl($model, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialcharsAttribute($model->keyword),
	                $model->id,
                    $model->keyword
                );

            case 'glossaryitem_open':
                return sprintf(
                    '<a href="%s" title="%s" data-glossary-id="%s">',
                    $glossaryItem->generateUrl($model, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialcharsAttribute($model->keyword),
	                $model->id
                );

            case 'glossaryitem_url':
                return $glossaryItem->generateUrl($model, \in_array('absolute', $arguments, true)) ?: './';

            case 'glossaryitem_keyword':
                return StringUtil::specialcharsAttribute($model->keyword);

            case 'glossaryitem_teaser':
                return StringUtil::toHtml5($model->teaser);
        }

        return '';
    }
}
