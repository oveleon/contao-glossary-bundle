<?php

namespace Oveleon\ContaoGlossaryBundle\Import\Validator;

use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;
use Oveleon\ProductInstaller\Import\Validator\ValidatorInterface;

/**
 * Validator class for validating the glossary item records during and after import.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class GlossaryItemValidator implements ValidatorInterface
{
    static public function getTrigger(): string
    {
        return GlossaryItemModel::getTable();
    }

    static public function getModel(): string
    {
        return GlossaryItemModel::class;
    }
}
