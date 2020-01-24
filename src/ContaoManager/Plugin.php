<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Oveleon\ContaoGlossaryBundle\ContaoGlossaryBundle;

/**
 * Plugin for the Contao Manager.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(ContaoGlossaryBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['glossary']),
        ];
    }
}
