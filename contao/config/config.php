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

use Contao\ArrayUtil;
use Oveleon\ContaoGlossaryBundle\EventListener\Import\AddGlossaryValidatorListener;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryItemModel;
use Oveleon\ContaoGlossaryBundle\Model\GlossaryModel;

// Back end modules
ArrayUtil::arrayInsert($GLOBALS['BE_MOD']['content'], 5, [
    'glossary' => [
        'tables' => ['tl_glossary', 'tl_glossary_item', 'tl_content'],
    ],
]);

// Models
$GLOBALS['TL_MODELS']['tl_glossary'] = GlossaryModel::class;
$GLOBALS['TL_MODELS']['tl_glossary_item'] = GlossaryItemModel::class;

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'glossarys';
$GLOBALS['TL_PERMISSIONS'][] = 'glossaryp';

// Add product installer validators
$GLOBALS['PI_HOOKS']['addValidator'][] = [AddGlossaryValidatorListener::class, 'addValidators'];
$GLOBALS['PI_HOOKS']['setModuleValidatorArchiveConnections'][] = [AddGlossaryValidatorListener::class, 'setModuleArchiveConnections'];
$GLOBALS['PI_HOOKS']['setUserGroupValidatorArchiveConnections'][] = [AddGlossaryValidatorListener::class, 'setUserGroupArchiveConnections'];
