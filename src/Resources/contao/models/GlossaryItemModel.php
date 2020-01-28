<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

/**
 * Reads and writes glossary items
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $tstamp
 * @property string  $keyword
 * @property string  $alias
 * @property string  $teaser
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
 * @method static GlossaryItemModel|null findOneByTeaser($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByPublished($val, array $opt=array())
 *
 * @method static \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null findByPid($val, array $opt=array())
 * @method static \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null findByTstamp($val, array $opt=array())
 * @method static \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null findByKeyword($val, array $opt=array())
 * @method static \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null findByAlias($val, array $opt=array())
 * @method static \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null findByTeaser($val, array $opt=array())
 * @method static \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null findByPublished($val, array $opt=array())
 * @method static \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null findMultipleByIds($val, array $opt=array())
 * @method static \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null findBy($col, $val, array $opt=array())
 * @method static \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByKeyword($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByTeaser($val, array $opt=array())
 * @method static integer countByPublished($val, array $opt=array())
 *
 * @author Fabian Ekert <https://github.com/leofeyer>
 */
class GlossaryItemModel extends \Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_glossary_item';

    /**
     * Find published news items by their parent ID
     *
     * @param array   $arrPids     An array of glossary archive IDs
     * @param integer $intLimit    An optional limit
     * @param integer $intOffset   An optional offset
     * @param array   $arrOptions  An optional options array
     *
     * @return \Model\Collection|GlossaryItemModel[]|GlossaryItemModel|null A collection of models or null if there are no glossaries
     */
    public static function findPublishedByPids($arrPids, $intLimit=0, $intOffset=0, array $arrOptions=array())
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

        $arrOptions['limit']  = $intLimit;
        $arrOptions['offset'] = $intOffset;

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Count published glossary items by their parent ID
     *
     * @param array   $arrPids     An array of glossary archive IDs
     * @param array   $arrOptions  An optional options array
     *
     * @return integer The number of news items
     */
    public static function countPublishedByPids($arrPids, array $arrOptions=array())
    {
        if (empty($arrPids) || !\is_array($arrPids))
        {
            return 0;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")");

        if (!static::isPreviewMode($arrOptions))
        {
            $arrColumns[] = "$t.published='1'";
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }
}
