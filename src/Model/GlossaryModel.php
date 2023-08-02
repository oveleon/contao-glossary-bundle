<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @package     contao-glossary-bundle
 * @license     AGPL-3.0
 * @author      Fabian Ekert        <https://github.com/eki89>
 * @author      Sebastian Zoglowek  <https://github.com/zoglo>
 * @copyright   Oveleon             <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoGlossaryBundle\Model;

use Contao\Model;
use Contao\Model\Collection;

/**
 * Reads and writes glossaries.
 *
 * @property int    $id
 * @property int    $tstamp
 * @property string $title
 * @property int    $jumpTo
 * @property string $glossaryHoverCardTemplate
 * @property string $hoverCardImgSize
 * @property bool   $protected
 * @property string $groups
 *
 * @method static GlossaryModel|null findById($id, array $opt=array())
 * @method static GlossaryModel|null findByPk($id, array $opt=array())
 * @method static GlossaryModel|null findOneBy($col, $val, array $opt=array())
 * @method static GlossaryModel|null findOneByTstamp($val, array $opt=array())
 * @method static GlossaryModel|null findOneByTitle($val, array $opt=array())
 * @method static GlossaryModel|null findOneByJumpTo($val, array $opt=array())
 * @method static GlossaryModel|null findOneByGlossaryHoverCardTemplate($val, array $opt=array())
 * @method static GlossaryModel|null findOneByHoverCardImgSize($val, array $opt=array())
 * @method static GlossaryModel|null findOneByProtected($val, array $opt=array())
 * @method static GlossaryModel|null findOneByGroups($val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findByTitle($val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findByGlossaryHoverCardTemplate($val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findByHoverCardImgSize($val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findByProtected($val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findByGroups($val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|GlossaryModel[]|GlossaryModel|null findAll(array $opt=array())
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 *
 * @author Fabian Ekert <https://github.com/eki89>
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
class GlossaryModel extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_glossary';
}

class_alias(GlossaryModel::class, 'GlossaryModel');
