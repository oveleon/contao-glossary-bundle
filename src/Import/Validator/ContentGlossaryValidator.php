<?php

namespace Oveleon\ContaoGlossaryBundle\Import\Validator;

use Contao\ContentModel;
use Contao\Controller;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ProductInstaller\Import\TableImport;

/**
 * Validator class for validating the content records within glossary items during and after import.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class ContentGlossaryValidator
{
    static public function getTrigger(): string
    {
        return ContentModel::getTable() . '.' . GlossaryItemModel::getTable();
    }

    static public function getModel(): string
    {
        return ContentModel::class;
    }

    /**
     * Handles the relationship with the parent element.
     */
    static function setGlossaryItemConnection(array &$row, TableImport $importer): ?array
    {
        $translator = Controller::getContainer()->get('translator');

        $glossaryStructure = $importer->getArchiveContentByFilename(GlossaryItemModel::getTable(), [
            'value' => $row['pid'],
            'field' => 'id'
        ]);

        return $importer->useParentConnectionLogic($row, ContentModel::getTable(), GlossaryItemModel::getTable(), [
            'label'       => $translator->trans('setup.prompt.content.glossary.label', [], 'setup'),
            'description' => $translator->trans('setup.prompt.content.glossary.description', [], 'setup'),
            'explanation' => [
                'type'        => 'TABLE',
                'description' => $translator->trans('setup.prompt.content.glossary.explanation', [], 'setup'),
                'content'     => $glossaryStructure ?? []
            ]
        ]);
    }
}
