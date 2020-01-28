<?php

/*
 * This file is part of Oveleon glossary bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoGlossaryBundle;

/**
 * Parent class for glossary modules.
 *
 * @author Fabian Ekert <https://github.com/eki89>
 */
abstract class ModuleGlossary extends \Module
{
	/**
	 * Sort out protected glossaries
	 *
	 * @param array $arrGloassary
	 *
	 * @return array
	 */
	protected function sortOutProtected($arrGloassary)
	{
		if (empty($arrGloassary) || !\is_array($arrGloassary))
		{
			return $arrGloassary;
		}

		$this->import('FrontendUser', 'User');
		$objGlossary = GlossaryModel::findMultipleByIds($arrGloassary);
		$arrGloassary = array();

		if ($objGlossary !== null)
		{
			while ($objGlossary->next())
			{
				if ($objGlossary->protected)
				{
					if (!FE_USER_LOGGED_IN)
					{
						continue;
					}

					$groups = \StringUtil::deserialize($objGlossary->groups);

					if (empty($groups) || !\is_array($groups) || !\count(array_intersect($groups, $this->User->groups)))
					{
						continue;
					}
				}

				$arrGloassary[] = $objGlossary->id;
			}
		}

		return $arrGloassary;
	}

    /**
     * Parse an glossary item and return it as string
     *
     * @param GlossaryItemModel $objArticle
     * @param string            $strClass
     * @param integer           $intCount
     *
     * @return string
     */
    protected function parseArticle($objArticle, $strClass='', $intCount=0)
    {
        /** @var \FrontendTemplate|object $objTemplate */
        $objTemplate = new \FrontendTemplate($this->glossary_template);
        $objTemplate->setData($objArticle->row());

        if ($objArticle->cssClass != '')
        {
            $strClass = ' ' . $objArticle->cssClass . $strClass;
        }

        $objTemplate->class = $strClass;
        $objTemplate->headline = $objArticle->keyword;
        $objTemplate->linkHeadline = $this->generateLink($objArticle->keyword, $objArticle);
        $objTemplate->count = $intCount;
        $objTemplate->text = '';
        $objTemplate->hasText = false;
        $objTemplate->hasTeaser = false;

        // Clean the RTE output
        if ($objArticle->teaser != '')
        {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = \StringUtil::toHtml5($objArticle->teaser);
            $objTemplate->teaser = \StringUtil::encodeEmail($objTemplate->teaser);
        }

        // Display the "read more" button for external/article links
        if ($objArticle->source != 'default')
        {
            $objTemplate->text = true;
            $objTemplate->hasText = true;
        }

        // Compile the news text
        else
        {
            $id = $objArticle->id;

            $objTemplate->text = function () use ($id)
            {
                $strText = '';
                $objElement = \ContentModel::findPublishedByPidAndTable($id, 'tl_glossary_item');

                if ($objElement !== null)
                {
                    while ($objElement->next())
                    {
                        $strText .= $this->getContentElement($objElement->current());
                    }
                }

                return $strText;
            };

            $objTemplate->hasText = function () use ($objArticle)
            {
                return \ContentModel::countPublishedByPidAndTable($objArticle->id, 'tl_news') > 0;
            };
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['parseArticles']) && \is_array($GLOBALS['TL_HOOKS']['parseArticles']))
        {
            foreach ($GLOBALS['TL_HOOKS']['parseArticles'] as $callback)
            {
                $this->import($callback[0]);
                $this->{$callback[0]}->{$callback[1]}($objTemplate, $objArticle->row(), $this);
            }
        }

        return $objTemplate->parse();
    }

    /**
     * Parse one or more glossary items and return them as array
     *
     * @param \Model\Collection $objArticles
     *
     * @return array
     */
    protected function parseArticles($objArticles)
    {
        $limit = $objArticles->count();

        if ($limit < 1)
        {
            return array();
        }

        $count = 0;
        $arrArticles = array();

        while ($objArticles->next())
        {
            /** @var GlossaryItemModel $objArticle */
            $objArticle = $objArticles->current();

            $arrArticles[] = $this->parseArticle($objArticle, ((++$count == 1) ? ' first' : '') . (($count == $limit) ? ' last' : '') . ((($count % 2) == 0) ? ' odd' : ' even'), $count);
        }

        return $arrArticles;
    }

    /**
     * Generate a link and return it as string
     *
     * @param string            $strLink
     * @param GlossaryItemModel $objArticle
     *
     * @return string
     */
    protected function generateLink($strLink, $objArticle)
    {
        return sprintf(
            '<a href="%s" title="%s" itemprop="url">%s</a>',
            $this->generateGlossaryItemUrl($objArticle),
            \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objArticle->keyword), true),
            $strLink
        );
    }

    /**
     * Generate a URL and return it as string
     *
     * @param GlossaryItemModel $objItem
     *
     * @return string
     */
    public function generateGlossaryItemUrl($objItem)
    {
        $strCacheKey = 'id_' . $objItem->id;

        $objPage = \PageModel::findByPk($objItem->getRelated('pid')->jumpTo);

        return ampersand($objPage->getFrontendUrl((\Config::get('useAutoItem') ? '/' : '/items/') . ($objItem->alias ?: $objItem->id)));
    }
}
