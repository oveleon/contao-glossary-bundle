<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @package     contao-glossary-bundle
 * @license     AGPL-3.0
 * @author      Daniele Sciannimanica  <https://github.com/doishub>
 * @copyright   Oveleon                <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoGlossaryBundle\EventListener\ProductInstaller;

use Oveleon\ContaoGlossaryBundle\Import\Validator\ContentGlossaryValidator;
use Oveleon\ContaoGlossaryBundle\Import\Validator\GlossaryValidator;
use Oveleon\ProductInstaller\Import\Validator;
use Oveleon\ProductInstaller\Import\Validator\ContentValidator;
use Oveleon\ProductInstaller\Import\Validator\ValidatorMode;

class AddGlossaryValidatorListener
{
    public function addValidators(): void
    {
        // Add parent connection for content elements
        Validator::addValidator(ContentGlossaryValidator::getTrigger(), [ContentGlossaryValidator::class, 'setGlossaryItemConnection']);

        // Connects jumpTo pages
        Validator::addValidatorCollection([GlossaryValidator::class], ['setJumpToPageConnection']);

        // Connects insert tags and file connections
        Validator::addValidatorCollection([
            ContentGlossaryValidator::class
        ], [
            [ContentValidator::class, 'setIncludes'],
            [ContentValidator::class, 'setSingleFileConnection'],
            [ContentValidator::class, 'setMultiFileConnection'],
            [ContentValidator::class, 'setPlayerConnection'],
            [ContentValidator::class, 'setContentIncludes', ValidatorMode::AFTER_IMPORT_ROW],

            'setCustomElementSingleFileConnections',
            'setInsertTagConnections'
        ]);
    }
}
