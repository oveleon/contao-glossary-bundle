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

namespace Oveleon\ContaoGlossaryBundle\Model;

use Contao\Model;
use Contao\Model\Collection;

/**
 * Reads and writes glossary items.
 *
 * @property int    $id
 * @property int    $pid
 * @property int    $tstamp
 * @property string $keyword
 * @property bool   $sensitiveSearch
 * @property string $alias
 * @property string $pageTitle
 * @property string $description
 * @property string $subheadline
 * @property string $teaser
 * @property bool   $addImage
 * @property string $singleSRC
 * @property string $alt
 * @property string $size
 * @property string $imagemargin
 * @property string $imageUrl
 * @property bool   $fullsize
 * @property string $caption
 * @property string $floating
 * @property string $source
 * @property int    $jumpTo
 * @property int    $articleId
 * @property string $url
 * @property bool   $target
 * @property string $cssClass
 * @property bool   $published
 *
 * @method static GlossaryItemModel|null findById($id, array $opt=array())
 * @method static GlossaryItemModel|null findByPk($id, array $opt=array())
 * @method static GlossaryItemModel|null findByIdOrAlias($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneBy($col, $val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByPid($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByTstamp($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByKeyword($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneBySensitiveSearch($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByAlias($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByPageTitle($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByDescription($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneBySubheadline($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByTeaser($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByAddImage($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneBySingleSRC($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByAlt($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneBySize($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByImagemargin($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByImageUrl($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByFullsize($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByCaption($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByFloating($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneBySource($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByJumpTo($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByArticleId($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByUrl($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByTarget($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByCssClass($val, array $opt=array())
 * @method static GlossaryItemModel|null findOneByPublished($val, array $opt=array())
 *
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByPid($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByTstamp($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByKeyword($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findBySensitiveSearch($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByAlias($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByPageTitle($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByDescription($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findBySubheadline($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByTeaser($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByAddImage($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findBySingleSRC($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByAlt($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findBySize($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByImagemargin($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByImageUrl($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByFullsize($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByCaption($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByFloating($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findBySource($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByArticleId($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByUrl($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByTarget($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByCssClass($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findByPublished($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findBy($col, $val, array $opt=array())
 * @method static Collection<GlossaryItemModel>|GlossaryItemModel[]|GlossaryItemModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByKeyword($val, array $opt=array())
 * @method static integer countBySensitiveSearch($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByPageTitle($val, array $opt=array())
 * @method static integer countByDescription($val, array $opt=array())
 * @method static integer countBySubheadline($val, array $opt=array())
 * @method static integer countByTeaser($val, array $opt=array())
 * @method static integer countByAddImage($val, array $opt=array())
 * @method static integer countBySingleSRC($val, array $opt=array())
 * @method static integer countByAlt($val, array $opt=array())
 * @method static integer countBySize($val, array $opt=array())
 * @method static integer countByImagemargin($val, array $opt=array())
 * @method static integer countByImageUrl($val, array $opt=array())
 * @method static integer countByFullsize($val, array $opt=array())
 * @method static integer countByCaption($val, array $opt=array())
 * @method static integer countByFloating($val, array $opt=array())
 * @method static integer countBySource($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByArticleId($val, array $opt=array())
 * @method static integer countByUrl($val, array $opt=array())
 * @method static integer countByTarget($val, array $opt=array())
 * @method static integer countByCssClass($val, array $opt=array())
 * @method static integer countByPublished($val, array $opt=array())
 */
class GlossaryItemModel extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_glossary_item';

    /**
     * Find a published glossary item by its ID.
     */
    public static function findPublishedById(int $intId, array $arrOptions = []): self|null
    {
        $t = static::$strTable;
        $arrColumns = ["$t.id=?"];

        if (!static::isPreviewMode($arrOptions))
        {
            $arrColumns[] = "$t.published='1'";
        }

        return static::findOneBy($arrColumns, $intId, $arrOptions);
    }

    /**
     * Find a published glossary item by its ID or alias.
     */
    public static function findPublishedByIdOrAlias(mixed $varId, array $arrOptions = []): self|null
    {
        $t = static::$strTable;
        $arrColumns = preg_match('/^[1-9]\d*$/', (string) $varId) ? ["$t.id=?"] : ["$t.alias=?"];

        if (!static::isPreviewMode($arrOptions))
        {
            $arrColumns[] = "$t.published='1'";
        }

        return static::findOneBy($arrColumns, $varId, $arrOptions);
    }

    /**
     * Find a published glossary item from one or more glossaries by its ID or alias.
     */
    public static function findPublishedByParentAndIdOrAlias(mixed $varId, array $arrPids, array $arrOptions = []): self|null
    {
        if ([] === $arrPids)
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = preg_match('/^[1-9]\d*$/', (string) $varId) ? ["$t.id=?"] : ["$t.alias=?"];
        $arrColumns[] = "$t.pid IN(".implode(',', array_map('\intval', $arrPids)).')';

        if (!static::isPreviewMode($arrOptions))
        {
            $arrColumns[] = "$t.published='1'";
        }

        return static::findOneBy($arrColumns, $varId, $arrOptions);
    }

    /**
     * Find published glossary items with the default redirect target by their parent ID.
     *
     * @return Collection<GlossaryItemModel>|GlossaryItemModel|array|null
     */
    public static function findPublishedDefaultByPid(int $intPid, array $arrOptions = []): Collection|self|array|null
    {
        $t = static::$strTable;
        $arrColumns = ["$t.pid=? AND $t.source='default'"];

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
     * Find published glossary items by their parent ID.
     *
     * @return Collection<GlossaryItemModel>|GlossaryItemModel|array|null
     */
    public static function findPublishedByPids(array $arrPids, array $arrOptions = []): Collection|self|array|null
    {
        if ([] === $arrPids)
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = ["$t.pid IN(".implode(',', array_map('\intval', $arrPids)).')'];

        if (!static::isPreviewMode($arrOptions))
        {
            $arrColumns[] = "$t.published='1'";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order'] = "$t.keyword ASC";
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Find published glossary items by letter and their parent ID.
     *
     * @return Collection<GlossaryItemModel>|GlossaryItemModel|array|null
     */
    public static function findPublishedByLetterAndPids(string $strLetter, array $arrPids, array $arrOptions = []): Collection|self|array|null
    {
        if ([] === $arrPids)
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = ["$t.letter=?"];
        $arrColumns[] = "$t.pid IN(".implode(',', array_map('\intval', $arrPids)).')';
        $arrValues = [$strLetter];

        if (!static::isPreviewMode($arrOptions))
        {
            $arrColumns[] = "$t.published='1'";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order'] = "$t.keyword ASC";
        }

        return static::findBy($arrColumns, $arrValues, $arrOptions);
    }
}

class_alias(GlossaryItemModel::class, 'GlossaryItemModel');
