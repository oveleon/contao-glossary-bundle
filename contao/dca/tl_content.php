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

use Contao\Input;
use Oveleon\ContaoGlossaryBundle\EventListener\DataContainer\ContentElementListener;

// Dynamically add the permission check and parent table
if ('glossary' === Input::get('do'))
{
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_glossary_item';
    array_unshift($GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'], [ContentElementListener::class, 'checkPermission']);
}
