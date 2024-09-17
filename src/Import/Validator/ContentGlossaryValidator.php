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

use Contao\ContentModel;
use Contao\Controller;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ProductInstaller\Import\TableImport;

/**
 * Validator class for validating the content records within glossary items during and after import.
 */
class ContentGlossaryValidator
{
    public static function getTrigger(): string
    {
        return ContentModel::getTable().'.'.GlossaryItemModel::getTable();
    }

    public static function getModel(): string
    {
        return ContentModel::class;
    }

    /**
     * Handles the relationship with the parent element.
     */
    public static function setGlossaryItemConnection(array &$row, TableImport $importer): array|null
    {
        $translator = Controller::getContainer()->get('translator');

        $glossaryStructure = $importer->getArchiveContentByFilename(GlossaryItemModel::getTable(), [
            'value' => $row['pid'],
            'field' => 'id',
        ]);

        return $importer->useParentConnectionLogic(
            $row,
            ContentModel::getTable(),
            GlossaryItemModel::getTable(),
            [
                'label' => $translator->trans('setup.prompt.content.glossary.label', [], 'setup'),
                'description' => $translator->trans('setup.prompt.content.glossary.description', [], 'setup'),
                'explanation' => [
                    'type' => 'TABLE',
                    'description' => $translator->trans('setup.prompt.content.glossary.explanation', [], 'setup'),
                    'content' => $glossaryStructure ?? [],
                ],
            ],
        );
    }
}
