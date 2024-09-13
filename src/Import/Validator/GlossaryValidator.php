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

use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;

/**
 * Validator class for validating the glossary records during and after import.
 */
class GlossaryValidator
{
    public static function getTrigger(): string
    {
        return GlossaryModel::getTable();
    }

    public static function getModel(): string
    {
        return GlossaryModel::class;
    }
}
