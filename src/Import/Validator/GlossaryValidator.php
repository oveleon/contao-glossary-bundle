<?php

namespace Oveleon\ContaoGlossaryBundle\Import\Validator;

use Oveleon\ContaoGlossaryBundle\GlossaryModel;
use Oveleon\ProductInstaller\Import\Validator\ValidatorInterface;

/**
 * Validator class for validating the glossary records during and after import.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class GlossaryValidator implements ValidatorInterface
{
    static public function getTrigger(): string
    {
        return GlossaryModel::getTable();
    }

    static public function getModel(): string
    {
        return GlossaryModel::class;
    }
}
