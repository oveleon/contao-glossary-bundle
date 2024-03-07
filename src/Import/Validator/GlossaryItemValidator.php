<?php

namespace Oveleon\ContaoGlossaryBundle\Import\Validator;

use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;

/**
 * Validator class for validating the glossary item records during and after import.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class GlossaryItemValidator
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
