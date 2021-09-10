<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */
	
use Oveleon\ContaoGlossaryBundle\GlossaryItem;
use Oveleon\ContaoGlossaryBundle\GlossaryItemModel;
use Patchwork\Utf8;

System::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_glossary_item'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_glossary',
		'ctable'                      => array('tl_content'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
        'markAsCopy'                  => 'keyword',
		'onload_callback' => array
		(
			array('tl_glossary_item', 'checkPermission')
		),
        'oncut_callback' => array
        (
            array('tl_glossary_item', 'updateGlossaryIndex')
        ),
        'ondelete_callback' => array
        (
            array('tl_glossary_item', 'updateGlossaryIndex')
        ),
        'onsubmit_callback' => array
        (
            array('tl_glossary_item', 'setGlossaryItemGroup'),
            array('tl_glossary_item', 'updateGlossaryIndex')
        ),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'alias' => 'index',
				'pid,published' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('keyword'),
			'headerFields'            => array('title', 'jumpTo', 'tstamp', 'protected'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_glossary_item', 'listGlossaryItems'),
			'child_record_class'      => 'no_padding'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'href'                => 'table=tl_content',
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'header.svg'
			),
			'copy' => array
			(
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('tl_glossary_item', 'toggleIcon'),
                'showInHeader'        => true
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
        '__selector__'                => array('source', 'addImage', 'overwriteMeta'),
		'default'                     => '{title_legend},keyword,alias;{keyword_legend:hide},keywords;{source_legend:hide},source;{meta_legend},pageTitle,robots,description,serpPreview;{teaser_legend},subheadline,teaser;{image_legend},addImage;{expert_legend:hide},cssClass;{publish_legend},published'
	),

    // Subpalettes
    'subpalettes' => array
    (
        'source_internal'             => 'jumpTo',
        'source_article'              => 'articleId',
        'source_external'             => 'url,target',
	    'addImage'                    => 'singleSRC,size,floating,imagemargin,fullsize,overwriteMeta',
	    'overwriteMeta'               => 'alt,imageTitle,imageUrl,caption'
    ),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_glossary.title',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['tstamp'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
        'letter' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['letter'],
            'sql'                     => "char(1) NOT NULL default ''"
        ),
		'keyword' => array
		(
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['keyword'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'alias' => array
		(
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['alias'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50 clr'),
			'save_callback' => array
			(
				array('tl_glossary_item', 'generateAlias')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
        'keywords' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['keywords'],
            'exclude'                 => true,
            'inputType'               => 'listWizard',
            'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
            'sql'                     => "blob NULL"
        ),
        'pageTitle' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['pageTitle'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
		'robots' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['robots'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'select',
			'options'                 => array('index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'),
			'eval'                    => array('tl_class'=>'w50', 'includeBlankOption' => true),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
        'description' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['description'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'textarea',
            'eval'                    => array('style'=>'height:60px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
            'sql'                     => "text NULL"
        ),
		'serpPreview' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
			'exclude'                 => true,
			'inputType'               => 'serpPreview',
			'eval'                    => array('url_callback'=>array('tl_glossary_item', 'getSerpUrl'), 'title_tag_callback'=>array('tl_glossary_item', 'getTitleTag'), 'titleFields'=>array('pageTitle', 'keyword'), 'descriptionFields'=>array('description', 'teaser')),
			'sql'                     => null
		),
		'subheadline' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['subheadline'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'teaser' => array
		(
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['teaser'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'addImage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['addImage'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'overwriteMeta' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'extensions'=>Contao\Config::get('validImageTypes'), 'mandatory'=>true),
			'sql'                     => "binary(16) NULL"
		),
		'alt' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['alt'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageTitle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['size'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'options_callback' => static function ()
			{
				return Contao\System::getContainer()->get('contao.image.image_sizes')->getOptionsForUser(Contao\BackendUser::getInstance());
			},
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'imagemargin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imagemargin'],
			'exclude'                 => true,
			'inputType'               => 'trbl',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'imageUrl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'addWizardClass'=>false, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'fullsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'caption' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['caption'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'floating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['floating'],
			'exclude'                 => true,
			'inputType'               => 'radioTable',
			'options'                 => array('above', 'left', 'right', 'below'),
			'eval'                    => array('cols'=>4, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => "varchar(12) NOT NULL default 'above'"
		),
        'source' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['source'],
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'radio',
            'options_callback'        => array('tl_glossary_item', 'getSourceOptions'),
            'reference'               => &$GLOBALS['TL_LANG']['tl_glossary_item'],
            'eval'                    => array('submitOnChange'=>true, 'helpwizard'=>true),
            'sql'                     => "varchar(32) NOT NULL default 'default'"
        ),
        'jumpTo' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['jumpTo'],
            'exclude'                 => true,
            'inputType'               => 'pageTree',
            'foreignKey'              => 'tl_page.title',
            'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio'),
            'sql'                     => "int(10) unsigned NOT NULL default 0",
            'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
        ),
        'articleId' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['articleId'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'options_callback'        => array('tl_glossary_item', 'getArticleAlias'),
            'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default 0",
            'relation'                => array('table'=>'tl_article', 'type'=>'hasOne', 'load'=>'lazy'),
        ),
        'url' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['MSC']['url'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'addWizardClass'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'target' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['MSC']['target'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'cssClass' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['cssClass'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
		'published' => array
		(
            'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['published'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
class tl_glossary_item extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Check permissions to edit table tl_glossary_item
	 *
	 * @throws Contao\CoreBundle\Exception\AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Set the root IDs
		if (empty($this->User->glossarys) || !is_array($this->User->glossarys))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->glossarys;
		}

		$id = strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

		// Check current action
		switch (Input::get('act'))
		{
			case 'paste':
			case 'select':
				// Check CURRENT_ID here (see #247)
				if (!in_array(CURRENT_ID, $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access glossary ID ' . $id . '.');
				}
				break;

			case 'create':
				if (!strlen(Input::get('pid')) || !in_array(Input::get('pid'), $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to create glossary items in glossary ID ' . Input::get('pid') . '.');
				}
				break;

			case 'cut':
			case 'copy':
				if (Input::get('act') == 'cut' && Input::get('mode') == 1)
				{
					$objGlossary = $this->Database->prepare("SELECT pid FROM tl_glossary_item WHERE id=?")
												 ->limit(1)
												 ->execute(Input::get('pid'));

					if ($objGlossary->numRows < 1)
					{
						throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid glossary item ID ' . Input::get('pid') . '.');
					}

					$pid = $objGlossary->pid;
				}
				else
				{
					$pid = Input::get('pid');
				}

				if (!in_array($pid, $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' glossary item ID ' . $id . ' to glossary ID ' . $pid . '.');
				}
				// no break

			case 'edit':
			case 'show':
			case 'delete':
			case 'toggle':
				$objGlossary = $this->Database->prepare("SELECT pid FROM tl_glossary_item WHERE id=?")
											 ->limit(1)
											 ->execute($id);

				if ($objGlossary->numRows < 1)
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid glossary item ID ' . $id . '.');
				}

				if (!in_array($objGlossary->pid, $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' glossary item ID ' . $id . ' of glossary  ID ' . $objGlossary->pid . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				if (!in_array($id, $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access glossary ID ' . $id . '.');
				}

				$objGlossary = $this->Database->prepare("SELECT id FROM tl_glossary_item WHERE pid=?")
											 ->execute($id);

				/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objGlossary->fetchEach('id'));
				$objSession->replace($session);
				break;

			default:
				if (strlen(Input::get('act')))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid command "' . Input::get('act') . '".');
				}

				if (!in_array($id, $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access glossary ID ' . $id . '.');
				}
				break;
		}
	}

    /**
     * Set group by keyword
     *
     * @param Contao\DataContainer $dc
     */
    public function setGlossaryItemGroup(Contao\DataContainer $dc)
    {
        $newGroup = Utf8::strtoupper(mb_substr($dc->activeRecord->keyword, 0, 1, 'UTF-8'));

        if ($dc->activeRecord->letter != $newGroup)
        {
            $this->Database->prepare("UPDATE tl_glossary_item SET letter=? WHERE id=?")
                ->execute($newGroup, $dc->id);
        }
    }

	/**
	 * Auto-generate the glossary item alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param Contao\DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, Contao\DataContainer $dc)
	{
        $aliasExists = function (string $alias) use ($dc): bool
        {
            return $this->Database->prepare("SELECT id FROM tl_glossary_item WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
        };

        // Generate alias if there is none
        if ($varValue == '')
        {
            $varValue = Contao\System::getContainer()->get('contao.slug')->generate($dc->activeRecord->keyword, \Oveleon\ContaoGlossaryBundle\GlossaryModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
        }
        elseif ($aliasExists($varValue))
        {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
	}
	
	/**
	 * Return the SERP URL
	 *
	 * @param GlossaryItemModel $model
	 *
	 * @return string
	 */
	public function getSerpUrl(GlossaryItemModel $model)
	{
		return GlossaryItem::generateUrl($model, true);
	}
	
	/**
	 * Return the title tag from the associated page layout
	 *
	 * @param GlossaryItemModel $model
	 *
	 * @return string
	 */
	public function getTitleTag(GlossaryItemModel $model)
	{
		/** @var \Oveleon\ContaoGlossaryBundle\GlossaryModel $glossary */
		if (!$glossary = $model->getRelated('pid'))
		{
			return '';
		}
		
		/** @var Contao\PageModel $page */
		if (!$page = $glossary->getRelated('jumpTo'))
		{
			return '';
		}
		
		$page->loadDetails();
		
		/** @var Contao\LayoutModel $layout */
		if (!$layout = $page->getRelated('layout'))
		{
			return '';
		}
		
		global $objPage;
		
		// Set the global page object so we can replace the insert tags
		$objPage = $page;
		
		return self::replaceInsertTags(str_replace('{{page::pageTitle}}', '%s', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}'));
	}

	/**
	 * List a glossary item
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function listGlossaryItems($arrRow)
	{
		return '<div class="tl_content_left">' . $arrRow['keyword'] . '</div>';
	}

    /**
     * Get all articles and return them as array
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    public function getArticleAlias(Contao\DataContainer $dc)
    {
        $arrPids = array();
        $arrAlias = array();

        if (!$this->User->isAdmin)
        {
            foreach ($this->User->pagemounts as $id)
            {
                $arrPids[] = array($id);
                $arrPids[] = $this->Database->getChildRecords($id, 'tl_page');
            }

            if (!empty($arrPids))
            {
                $arrPids = array_merge(...$arrPids);
            }
            else
            {
                return $arrAlias;
            }

            $objAlias = $this->Database->prepare("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN(" . implode(',', array_map('\intval', array_unique($arrPids))) . ") ORDER BY parent, a.sorting")
                ->execute($dc->id);
        }
        else
        {
            $objAlias = $this->Database->prepare("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting")
                ->execute($dc->id);
        }

        if ($objAlias->numRows)
        {
            Contao\System::loadLanguageFile('tl_article');

            while ($objAlias->next())
            {
                $arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title . ' (' . ($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?: $objAlias->inColumn) . ', ID ' . $objAlias->id . ')';
            }
        }

        return $arrAlias;
    }

    /**
     * Add the source options depending on the allowed fields
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    public function getSourceOptions(Contao\DataContainer $dc)
    {
        if ($this->User->isAdmin)
        {
            return array('default', 'internal', 'article', 'external');
        }

        $arrOptions = array('default');

        // Add the "internal" option
        if ($this->User->hasAccess('tl_glossary_item::jumpTo', 'alexf'))
        {
            $arrOptions[] = 'internal';
        }

        // Add the "article" option
        if ($this->User->hasAccess('tl_glossary_item::articleId', 'alexf'))
        {
            $arrOptions[] = 'article';
        }

        // Add the "external" option
        if ($this->User->hasAccess('tl_glossary_item::url', 'alexf'))
        {
            $arrOptions[] = 'external';
        }

        // Add the option currently set
        if ($dc->activeRecord && $dc->activeRecord->source != '')
        {
            $arrOptions[] = $dc->activeRecord->source;
            $arrOptions = array_unique($arrOptions);
        }

        return $arrOptions;
    }

    /**
     * Update the glossary index update
     *
     * @param Contao\DataContainer $dc
     */
    public function updateGlossaryIndex(Contao\DataContainer $dc)
    {
        // Return if there is no ID
        if (!$dc->activeRecord || !$dc->activeRecord->id)
        {
            return;
        }

        $arrKeywords = \StringUtil::deserialize($dc->activeRecord->keywords, true);

        foreach ($arrKeywords as $i => $keyword)
        {
            if (empty($keyword))
            {
                unset($arrKeywords[$i]);
            }
        }

        array_unshift($arrKeywords, $dc->activeRecord->keyword);

        $objIndex = $this->Database->prepare("SELECT id, word FROM tl_glossary_index WHERE pid=?")
            ->execute($dc->activeRecord->id);

        while ($objIndex->next())
        {
            if (($index = array_search($objIndex->word, $arrKeywords)) !== false)
            {
                unset($arrKeywords[$index]);
            }
            else
            {
                $this->Database->prepare("DELETE FROM tl_glossary_index WHERE id=?")
                    ->execute($objIndex->id);
            }

            if (\Input::get('act') === 'delete')
            {
                $this->Database->prepare("DELETE FROM tl_glossary_index WHERE id=?")
                    ->execute($objIndex->id);
            }
        }

        foreach ($arrKeywords as $keyword)
        {
            $arrSet = array
            (
                'pid'  => $dc->activeRecord->id,
                'word' => $keyword
            );

            $this->Database->prepare("INSERT INTO tl_glossary_index %s")->set($arrSet)->execute();
        }
    }

    /**
     * Add a link to the list items import wizard
     *
     * @return string
     */
    public function listImportWizard()
    {
        return ' <a href="' . $this->addToUrl('key=list') . '" title="' . Contao\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['lw_import'][1]) . '" onclick="Backend.getScrollOffset()">' . Contao\Image::getHtml('tablewizard.svg', $GLOBALS['TL_LANG']['MSC']['tw_import'][0]) . '</a>';
    }

	/**
	 * Return the "toggle visibility" button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
		if (strlen(Input::get('tid')))
		{
			$this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1), (@func_get_arg(12) ?: null));
			$this->redirect($this->getReferer());
		}

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->hasAccess('tl_glossary_item::published', 'alexf'))
		{
			return '';
		}

		$href .= '&amp;tid=' . $row['id'] . '&amp;state=' . ($row['published'] ? '' : 1);

		if (!$row['published'])
		{
			$icon = 'invisible.svg';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . Contao\StringUtil::specialchars($title) . '"' . $attributes . '>' . Contao\Image::getHtml($icon, $label, 'data-state="' . ($row['published'] ? 1 : 0) . '"') . '</a> ';
	}

	/**
	 * Disable/enable a user group
	 *
	 * @param integer       $intId
	 * @param boolean       $blnVisible
	 * @param Contao\DataContainer $dc
	 */
	public function toggleVisibility($intId, $blnVisible, Contao\DataContainer $dc=null)
	{
		// Set the ID and action
		Contao\Input::setGet('id', $intId);
		Contao\Input::setGet('act', 'toggle');

		if ($dc)
		{
			$dc->id = $intId; // see #8043
		}

		// Trigger the onload_callback
		if (is_array($GLOBALS['TL_DCA']['tl_glossary_item']['config']['onload_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_glossary_item']['config']['onload_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($dc);
				}
				elseif (is_callable($callback))
				{
					$callback($dc);
				}
			}
		}

		// Check the field access
		if (!$this->User->hasAccess('tl_glossary_item::published', 'alexf'))
		{
			throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to publish/unpublish glossary item ID ' . $intId . '.');
		}

		// Set the current record
		if ($dc)
		{
			$objRow = $this->Database->prepare("SELECT * FROM tl_glossary_item WHERE id=?")
									 ->limit(1)
									 ->execute($intId);

			if ($objRow->numRows)
			{
				$dc->activeRecord = $objRow;
			}
		}

		$objVersions = new Versions('tl_glossary_item', $intId);
		$objVersions->initialize();

		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_glossary_item']['fields']['published']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_glossary_item']['fields']['published']['save_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
				}
				elseif (is_callable($callback))
				{
					$blnVisible = $callback($blnVisible, $dc);
				}
			}
		}

		$time = time();

		// Update the database
		$this->Database->prepare("UPDATE tl_glossary_item SET tstamp=$time, published='" . ($blnVisible ? '1' : '') . "' WHERE id=?")
					   ->execute($intId);

		if ($dc)
		{
			$dc->activeRecord->tstamp = $time;
			$dc->activeRecord->published = ($blnVisible ? '1' : '');
		}

		// Trigger the onsubmit_callback
		if (is_array($GLOBALS['TL_DCA']['tl_glossary_item']['config']['onsubmit_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_glossary_item']['config']['onsubmit_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($dc);
				}
				elseif (is_callable($callback))
				{
					$callback($dc);
				}
			}
		}

		$objVersions->create();
	}
}
