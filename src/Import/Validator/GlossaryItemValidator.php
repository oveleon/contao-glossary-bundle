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

namespace Oveleon\ContaoGlossaryBundle\Import\Validator;

use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;

/**
 * Validator class for validating the glossary item records during and after import.
 */
class GlossaryItemValidator
{
    public static function getTrigger(): string
    {
        return GlossaryItemModel::getTable();
    }

    public static function getModel(): string
    {
        return GlossaryItemModel::class;
    }
}
