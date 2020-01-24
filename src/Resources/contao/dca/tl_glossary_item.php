<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

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
		'onload_callback' => array
		(
			array('tl_glossary_item', 'checkPermission')
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
			'fields'                  => array('title'),
			'headerFields'            => array('title', 'jumpTo', 'tstamp', 'protected'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_glossary_item', 'listGlossaryItems'),
			'child_record_class'      => 'no_padding'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_glossary_item']['edit'],
				'href'                => 'table=tl_content',
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_glossary_item']['editmeta'],
				'href'                => 'act=edit',
				'icon'                => 'header.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_glossary_item']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_glossary_item']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_glossary_item']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_glossary_item']['toggle'],
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('tl_glossary_item', 'toggleIcon')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_glossary_item']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},headline,alias;{date_legend},date,time;{teaser_legend},subheadline,teaser;{image_legend},addImage;{enclosure_legend:hide},addEnclosure;{source_legend:hide},source;{expert_legend:hide},cssClass,noComments,featured;{publish_legend},published,start,stop'
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
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'keyword' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['keyword'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'alias' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_glossary_item']['alias'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>128, 'tl_class'=>'w50 clr'),
			'save_callback' => array
			(
				array('tl_glossary_item', 'generateAlias')
			),
			'sql'                     => "varchar(255) COLLATE utf8_bin NOT NULL default ''"
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
	 * Auto-generate the glossary item alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$autoAlias = false;

		// Generate alias if there is none
		if ($varValue == '')
		{
			$autoAlias = true;
			$varValue = StringUtil::generateAlias($dc->activeRecord->keyword);
		}

		$objAlias = $this->Database->prepare("SELECT id FROM tl_glossary WHERE alias=? AND id!=?")
								   ->execute($varValue, $dc->id);

		// Check whether the glossary item alias exists
		if ($objAlias->numRows)
		{
			if (!$autoAlias)
			{
				throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
			}

			$varValue .= '-' . $dc->id;
		}

		return $varValue;
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

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label, 'data-state="' . ($row['published'] ? 1 : 0) . '"') . '</a> ';
	}

	/**
	 * Disable/enable a user group
	 *
	 * @param integer       $intId
	 * @param boolean       $blnVisible
	 * @param DataContainer $dc
	 */
	public function toggleVisibility($intId, $blnVisible, DataContainer $dc=null)
	{
		// Set the ID and action
		Input::setGet('id', $intId);
		Input::setGet('act', 'toggle');

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
