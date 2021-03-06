<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\Model;
use Contao\Model\Collection;

/**
 * Reads and writes glossary items
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $tstamp
 * @property string  $keyword
 * @property string  $alias
 * @property string  $pageTitle
 * @property string  $description
 * @property string  $teaser
 * @property string  $source
 * @property integer $jumpTo
 * @property integer $articleId
 * @property string  $url
 * @property boolean $target
 * @property string  $cssClass
 * @property boolean $published
 *
 * @method static GlossaryItemModel|null findById($id, array $opt=array())
 * @method static GlossaryItemModel|null findByPk($id, array $opt=array())
 * @method static GlossaryItemModel|null findByIdOrAlias($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneBy($col, $val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByPid($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByTstamp($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByKeyword($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByAlias($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByPageTitle($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByDescription($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByTeaser($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneBySource($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByJumpTo($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByArticleId($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByUrl($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByTarget($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByCssClass($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByPublished($val, array $opt=array())
 *
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByPid($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByKeyword($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByAlias($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByPageTitle($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByDescription($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByTeaser($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findBySource($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByArticleId($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByUrl($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByTarget($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByCssClass($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findByPublished($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|GlossaryItemModel[]|GlossaryItemModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByKeyword($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByPageTitle($val, array $opt=array())
 * @method static integer countByDescription($val, array $opt=array())
 * @method static integer countByTeaser($val, array $opt=array())
 * @method static integer countBySource($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByArticleId($val, array $opt=array())
 * @method static integer countByUrl($val, array $opt=array())
 * @method static integer countByTarget($val, array $opt=array())
 * @method static integer countByCssClass($val, array $opt=array())
 * @method static integer countByPublished($val, array $opt=array())
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class GlossaryItemModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_glossary_item';

    /**
     * Find a published glossary item from one or more glossaries by its ID or alias
     *
     * @param mixed $varId      The numeric ID or alias name
     * @param array $arrPids    An array of parent IDs
     * @param array $arrOptions An optional options array
     *
     * @return GlossaryItemModel|null The model or null if there are no glossary items
     */
    public static function findPublishedByParentAndIdOrAlias($varId, $arrPids, array $arrOptions=array())
    {
        if (empty($arrPids) || !\is_array($arrPids))
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? array("$t.alias=?") : array("$t.id=?");
        $arrColumns[] = "$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")";

        if (!static::isPreviewMode($arrOptions))
        {
            $arrColumns[] = "$t.published='1'";
        }

        return static::findOneBy($arrColumns, $varId, $arrOptions);
    }

    /**
     * Find published glossary items with the default redirect target by their parent ID
     *
     * @param integer $intPid     The glossary ID
     * @param array   $arrOptions An optional options array
     *
     * @return Collection|GlossaryItemModel[]|GlossaryItemModel|null A collection of models or null if there are no news
     */
    public static function findPublishedDefaultByPid($intPid, array $arrOptions=array())
    {
        $t = static::$strTable;
        $arrColumns = array("$t.pid=? AND $t.source='default'");

        if (!static::isPreviewMode($arrOptions))
        {
            $arrColumns[] = "$t.published='1'";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order'] = "$t.keyword ASC";
        }

        return static::findBy($arrColumns, $intPid, $arrOptions);
    }

    /**
     * Find published glossary items by their parent ID
     *
     * @param array   $arrPids     An array of glossary IDs
     * @param array   $arrOptions  An optional options array
     *
     * @return Collection|GlossaryItemModel[]|GlossaryItemModel|null A collection of models or null if there are no glossaries
     */
    public static function findPublishedByPids($arrPids, array $arrOptions=array())
    {
        if (empty($arrPids) || !\is_array($arrPids))
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")");

        // Never return unpublished elements in the back end
        if (!BE_USER_LOGGED_IN || TL_MODE == 'BE')
        {
            $arrColumns[] = "$t.published='1'";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order']  = "$t.keyword ASC";
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Find published glossary items by letter and their parent ID
     *
     * @param string  $strLetter   First glossary item letter
     * @param array   $arrPids     An array of glossary IDs
     * @param array   $arrOptions  An optional options array
     *
     * @return Collection|GlossaryItemModel[]|GlossaryItemModel|null A collection of models or null if there are no glossaries
     */
    public static function findPublishedByLetterAndPids($strLetter, $arrPids, array $arrOptions=array())
    {
        if (empty($arrPids) || !\is_array($arrPids))
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns   = array("$t.letter=?");
        $arrColumns[] = "$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")";
        $arrValues    = array($strLetter);

        // Never return unpublished elements in the back end
        if (!BE_USER_LOGGED_IN || TL_MODE == 'BE')
        {
            $arrColumns[] = "$t.published='1'";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order']  = "$t.keyword ASC";
        }

        return static::findBy($arrColumns, $arrValues, $arrOptions);
    }
}
