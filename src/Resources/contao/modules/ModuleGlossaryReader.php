<?php

/**
 * This file is part of Oveleon Contao Glossary Bundle.
 *
 * @package     contao-glossary-bundle
 * @license     AGPL-3.0
 * @author      Fabian Ekert        <https://github.com/eki89>
 * @author      Sebastian Zoglowek  <https://github.com/zoglo>
 * @copyright   Oveleon             <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoGlossaryBundle;

use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\System;
use FOS\HttpCache\ResponseTagger;
use Patchwork\Utf8;

/**
 * Front end module "glossary reader".
 *
 * @property array  $glossary_archives
 *
 * @author Fabian Ekert <https://github.com/eki89>
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */

class ModuleGlossaryReader extends ModuleGlossary
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_glossaryreader';

    /**
     * Display a wildcard in the back end
     *
     * @throws InternalServerErrorException
     *
     * @return string
     */
    public function generate()
    {
	    $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
	        $objTemplate = new BackendTemplate('be_wildcard');
	        $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['glossaryreader'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && isset($_GET['auto_item']) && \Config::get('useAutoItem'))
        {
            \Input::setGet('items', \Input::get('auto_item'));
        }

        // Return an empty string if "items" is not set
        if (!\Input::get('items'))
        {
            return '';
        }

        $this->glossary_archives = $this->sortOutProtected(\StringUtil::deserialize($this->glossary_archives));

        // Return if there are no glossaries
        if (empty($this->glossary_archives) || !\is_array($this->glossary_archives))
        {
            throw new InternalServerErrorException('The glossary reader ID ' . $this->id . ' has no archives specified.');
        }

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile()
    {
        /** @var \PageModel $objPage */
        global $objPage;

	    $this->Template->glossaryitem = '';
        $this->Template->referer = 'javascript:history.go(-1)';
        $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

        // Get the glossary item
        $objGlossaryItem = GlossaryItemModel::findPublishedByParentAndIdOrAlias(\Input::get('items'), $this->glossary_archives);

        if (null === $objGlossaryItem)
        {
            throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));
        }

		// Set the default template
	    if (!$this->glossary_template)
	    {
		    $this->glossary_template = 'glossary_full';
	    }

	    $arrGlossaryItem = $this->parseGlossaryItem($objGlossaryItem);
		$this->Template->glossaryentry = $arrGlossaryItem;

        // Overwrite the page title (see #2853, #4955 and #87)
        if ($objGlossaryItem->pageTitle)
        {
            $objPage->pageTitle = $objGlossaryItem->pageTitle;
        }
        elseif ($objGlossaryItem->keyword)
        {
            $objPage->pageTitle = strip_tags(\StringUtil::stripInsertTags($objGlossaryItem->keyword));
        }

        // Overwrite the page description
        if ($objGlossaryItem->description)
        {
            $objPage->description = $objGlossaryItem->description;
        }
        elseif ($objGlossaryItem->teaser)
        {
            $objPage->description = $this->prepareMetaDescription($objGlossaryItem->teaser);
        }
    }
}
