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
}
