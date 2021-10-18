<?php

declare(strict_types=1);

/**
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @package     contao-glossary-bundle
 * @license     AGPL-3.0
 * @author      Fabian Ekert        <https://github.com/eki89>
 * @author      Sebastian Zoglowek  <https://github.com/zoglo>
 * @copyright   Oveleon             <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoGlossaryBundle\Picker;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Picker\AbstractInsertTagPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Knp\Menu\FactoryInterface;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\GlossaryModel;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class GlossaryPickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var Security
     */
    private $security;

    /**
     * @internal Do not inherit from this class; decorate the "contao_glossary.picker.glossary_provider" service instead
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, ?TranslatorInterface $translator, Security $security)
    {
        parent::__construct($menuFactory, $router, $translator);

        $this->security = $security;
    }

    public function getName(): string
    {
        return 'glossaryPicker';
    }

    public function supportsContext($context): bool
    {
        return 'link' === $context && $this->security->isGranted('contao_user.modules', 'glossarys');
    }

    public function supportsValue(PickerConfig $config): bool
    {
        return $this->isMatchingInsertTag($config);
    }

    public function getDcaTable(): string
    {
        return 'tl_glossary_item';
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($source = $config->getExtra('source')) {
            $attributes['preserveRecord'] = $source;
        }

        if ($this->supportsValue($config)) {
            $attributes['value'] = $this->getInsertTagValue($config);

            if ($flags = $this->getInsertTagFlags($config)) {
                $attributes['flags'] = $flags;
            }
        }

        return $attributes;
    }

    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return sprintf($this->getInsertTag($config), $value);
    }

    protected function getRouteParameters(PickerConfig $config = null): array
    {
        $params = ['do' => 'glossary'];

        if (null === $config || !$config->getValue() || !$this->supportsValue($config)) {
            return $params;
        }

        if (null !== ($glossaryId = $this->getGlossaryId($this->getInsertTagValue($config)))) {
            $params['table'] = 'tl_glossary_item';
            $params['id'] = $glossaryId;
        }

        return $params;
    }

    protected function getDefaultInsertTag(): string
    {
        return '{{glossaryitem_url::%s}}';
    }

    /**
     * @param int|string $id
     */
    private function getGlossaryId($id): ?int
    {
        /** @var GlossaryItemModel $glossaryAdapter */
        $glossaryAdapter = $this->framework->getAdapter(GlossaryItemModel::class);

        if (!($glossaryItemModel = $glossaryAdapter->findById($id)) instanceof GlossaryItemModel) {
            return null;
        }

        if (!($glossary = $glossaryItemModel->getRelated('pid')) instanceof GlossaryModel) {
            return null;
        }

        return (int) $glossary->id;
    }
}
