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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Utils\GlossaryTrait;

/**
 * @internal
 */
class InsertTagsListener
{
    use GlossaryTrait;

    private const SUPPORTED_TAGS = [
        'glossaryitem',
        'glossaryitem_open',
        'glossaryitem_url',
        'glossaryitem_keyword',
        'glossaryitem_teaser',
    ];

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(string $tag, bool $useCache, mixed $cacheValue, array $flags): string|false
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if (\in_array($key, self::SUPPORTED_TAGS, true))
        {
            return $this->replaceGlossaryInsertTags($key, $elements[1], array_merge($flags, \array_slice($elements, 2)));
        }

        return false;
    }

    private function replaceGlossaryInsertTags(string $insertTag, string $idOrAlias, array $arguments): string
    {
        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(GlossaryItemModel::class);

        if (!$model = $adapter->findByIdOrAlias($idOrAlias))
        {
            return '';
        }

        return match ($insertTag)
        {
            'glossaryitem' => vsprintf(
                '<a href="%s" title="%s" data-glossary-id="%s">%s</a>',
                [
                    $this->generateDetailUrl($model, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialcharsAttribute($model->keyword),
                    $model->id,
                    $model->keyword,
                ],
            ),
            'glossaryitem_open' => vsprintf(
                '<a href="%s" title="%s" data-glossary-id="%s">',
                [
                    $this->generateDetailUrl($model, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialcharsAttribute($model->keyword),
                    $model->id,
                ],
            ),
            'glossaryitem_url' => $this->generateDetailUrl($model, \in_array('absolute', $arguments, true)) ?: './',
            'glossaryitem_keyword' => StringUtil::specialcharsAttribute($model->keyword),
            'glossaryitem_teaser' => $model->teaser,
            default => '',
        };
    }
}
