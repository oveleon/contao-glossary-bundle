<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

/**
 * Reads and writes glossary archives
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $title
 * @property integer $jumpTo
 * @property boolean $protected
 * @property string  $groups
 *
 * @method static GlossaryModel|null findById($id, array $opt=array())
 * @method static GlossaryModel|null findByPk($id, array $opt=array())
 * @method static GlossaryModel|null findOneBy($col, $val, array $opt=array())
 * @method static GlossaryModel|null findOneByTstamp($val, array $opt=array())
 * @method static GlossaryModel|null findOneByTitle($val, array $opt=array())
 * @method static GlossaryModel|null findOneByJumpTo($val, array $opt=array())
 * @method static GlossaryModel|null findOneByProtected($val, array $opt=array())
 * @method static GlossaryModel|null findOneByGroups($val, array $opt=array())
 *
 * @method static \Model\Collection|GlossaryModel[]|GlossaryModel|null findByTstamp($val, array $opt=array())
 * @method static \Model\Collection|GlossaryModel[]|GlossaryModel|null findByTitle($val, array $opt=array())
 * @method static \Model\Collection|GlossaryModel[]|GlossaryModel|null findByJumpTo($val, array $opt=array())
 * @method static \Model\Collection|GlossaryModel[]|GlossaryModel|null findByProtected($val, array $opt=array())
 * @method static \Model\Collection|GlossaryModel[]|GlossaryModel|null findByGroups($val, array $opt=array())
 * @method static \Model\Collection|GlossaryModel[]|GlossaryModel|null findMultipleByIds($val, array $opt=array())
 * @method static \Model\Collection|GlossaryModel[]|GlossaryModel|null findBy($col, $val, array $opt=array())
 * @method static \Model\Collection|GlossaryModel[]|GlossaryModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class GlossaryModel extends \Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_glossary';
}
