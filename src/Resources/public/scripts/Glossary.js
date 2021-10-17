import { extend } from "./helper/extend";
import { createPopper } from '@popperjs/core';

export class Glossary
{
    constructor(options)
    {
        this.options = extend(true, {
            entrySelector: '.c_text, .ce_text', // Selectors for glossary-term search
            markup: 'a',                        // Markup attribute for parsed glossary terms (e.g. 'mark', 'span', 'a')
            markupAttr: {
                'class': null                   // Class for parsed glossary terms
            },
            hovercard: {
                interactive: true,              // Makes hovercards clickable
                showLoadingAnimation: true,     // Show empty hovercard until content is fetched
                maxWidth: 380,                  // Maximum width of hovercard
                //position: 'auto',             // Not yet implemented - PopperSettings
                showEvent: 'mouseenter',        // EventListener to build hover card
                hideEvent: 'mouseleave',        // EventListener to destroy hover card
                showThreshold: 300,             // Minimum time that showEvent has to be triggered to build a hovercard
                hideThreshold: 200              // Time that hovercard will stay visible after triggering hideEvent
            },
            includes: [                         // Allowed nodes for glossary term markup
                'body',
                'div,span,p',
                'main,section,article',
                'h1,h2,h3,h4,h5,h6,strong',
                'ol,ul,li',
                'table,tr,th,tbody,thead,td',
                'i,b,em',
                'mark,abbr',
                'sub,sup'
            ],
            route: '/api/glossary/item/',
            config: null
        }, options || {})

        this.showDelay = null
        this.hideTimeout = null

        // Only parse nodes when config exists
        if(null !== this.options.config)
        {
            this.contentNodes = document.querySelectorAll(this.options.entrySelector)
            this._parseNodes(this.contentNodes, 0)
        }

        // Bind events for hovercard creation
        this._bindEvents()
    }

    /**
     * Parse all nodes within an entry selector
     * @private
     */
    _parseNodes(_nodes)
    {
        const nodes = Array.from(_nodes);

        for(const node of nodes)
        {
            if(this._isValid(node))
            {
                switch (node.nodeType)
                {
                    case Node.TEXT_NODE:
                        this._replaceTerm(node)
                        break
                    case Node.ELEMENT_NODE:
                        this._parseNodes(node.childNodes)
                        break
                }
            }
        }
    }

    /**
     * Checks valid nodes for glossary term conversion
     * @private
     */
    _isValid(node)
    {
        return node.nodeType === Node.TEXT_NODE || (node.nodeType === Node.ELEMENT_NODE && !!node.matches(this.options.includes.join(',')));
    }

    /**
     * Replace all found terms with a markup
     * @private
     */
    _replaceTerm(node)
    {
        if(!node.textContent.trim())
            return

        let termCache = [];

        for (const term of this.options.config)
        {
            // Case-sensitive search for glossary term out of config
            const rgx = new RegExp("(?<=\\s|>|^)(" + term.keywords.join('|') + ")\\b", term.cs ? 'gu' : 'giu')
            const matches = node.textContent.match(rgx)

            if(null !== matches)
            {
                const filteredMatches = matches.filter((v, i, a) => a.indexOf(v) === i)

                for (let match of filteredMatches)
                {
                    if(termCache.includes(match))
                        continue

                    termCache.push(match)

                    const elementMarkup = this._createTermMarkup(match, term)
                    const matchRgx = new RegExp("(?<=\\s|>|^)(" + match + ")\\b", 'gu')

                    node.textContent = node.textContent.replace(matchRgx, elementMarkup)
                }
            }
        }

        // Conversion from text to element
        const wrap = document.createElement('span')
        wrap.innerHTML = node.textContent
        node.replaceWith(wrap)
        wrap.outerHTML = wrap.innerHTML
    }

    /**
     * Create the glossary item markup
     * @private
     */
    _createTermMarkup(text, term)
    {
        const el = document.createElement(this.options.markup)

        el.innerText = text

        if(null !== this.options.markupAttr.class)
            el.className = this.options.markupAttr.class

        el.dataset.glossaryId = term.id

        // Link markup
        if(this.options.markup === 'a')
        {
            el.title = text
            el.href = term.url
        }

        return el.outerHTML;
    }

    /**
     * Apply EventListeners to glossary terms
     * @private
     */
    _bindEvents()
    {
        const glossaryElements = document.querySelectorAll('[data-glossary-id]');

        if(glossaryElements)
        {
            for(const element of glossaryElements)
            {
                element.addEventListener(this.options.hovercard.showEvent, (e) => this._onShowHovercard(e))
                element.addEventListener(this.options.hovercard.hideEvent, (e) => this._onHideHovercard(e))
            }
        }
    }

    /**
     * Cache - Saves already fetched content into sessionStorage
     * @private
     */
    _setItemCache(id, htmlContent)
    {
        let bag = sessionStorage.getItem('glossaryCache');
        sessionStorage.setItem('glossaryCache', JSON.stringify({...(bag ? JSON.parse(bag) : {}), ...{[id]: htmlContent}}))
    }

    /**
     * Cache - Checks and loads cached content from sessionStorage
     * @private
     */
    _getItemCached(id)
    {
        let bag = sessionStorage.getItem('glossaryCache');
        bag = JSON.parse(bag)

        return (bag && bag[id]) ? bag[id] : null
    }

    /**
     * Show-handler for hovercards
     * @private
     */
    _onShowHovercard(event)
    {
        this.currentElement = event.target;

        const id = this.currentElement.dataset.glossaryId;

        // Clear existing hovercard
        if(this.glossaryHovercard)
        {
            this._clearHideTimeout()
            this._destroyHovercard()
            this._clearShowDelay()
        }

        // Cache implementation
        const cachedResponse = this._getItemCached(id)

        if(cachedResponse)
        {
            this._buildHovercard(cachedResponse);
            this._updateHovercard(cachedResponse)
            return
        }

        // Only fetch glossary content after certain time to prevent too many requests
        this.showDelay = setTimeout(() => {
            this._fetchGlossaryItem(id)
        }, this.options.hovercard.showThreshold)
    }

    /**
     * Destroy-handler for hovercards
     * @private
     */
    _onHideHovercard(event)
    {
        // Clear delays
        this._clearHideTimeout()
        this._clearShowDelay()

        if (this?.glossaryHovercard)
        {
            // Do not destroy if showEvent is over hovercard
            if(this.options.hovercard.interactive)
            {
                this.hideTimeout = setTimeout(() => {
                    this._abortFetch()
                    this._destroyHovercard()
                }, this.options.hovercard.hideThreshold)
            }
            else
            {
                this._abortFetch()
                this._destroyHovercard()
            }
        }
    }

    /**
     * Gets glossary item content through a route
     * @private
     */
    async _fetchGlossaryItem(id)
    {
        this.abortController = new AbortController()

        // Build skeleton hovercard
        if(this.options.hovercard.showLoadingAnimation)
            this._buildHovercard()

        // Fetch glossary content from API
        await fetch(this.options.route + id + '/html', {signal: this.abortController.signal})
            .then((response) => {
                if(response.status >= 300)
                    throw new Error(response.statusText)

                response.text().then((htmlContent) => {
                    // Write into cache
                    this._setItemCache(id, htmlContent);

                    // Build or parse content into hovercard
                    if(!this.options.hovercard.showLoadingAnimation)
                        this._buildHovercard(htmlContent);
                    else
                        this._updateHovercard(htmlContent)
                })

            }).catch((e) => {})
    }

    /**
     * AbortController for fetching glossary items
     * @private
     */
    _abortFetch()
    {
        if (this?.abortController)
            this.abortController.abort()
    }

    /**
     * Creates the hovercard
     * @private
     */
    _buildHovercard(response)
    {
        this.glossaryHovercard = document.createElement('div')
        this.glossaryHovercard.style.maxWidth = this.options.hovercard.maxWidth + 'px'

        if(this.options.hovercard.interactive)
        {
            // Bind show and hide event to hovercard
            this.glossaryHovercard.addEventListener(this.options.hovercard.showEvent, () =>
            {
                this._clearHideTimeout()
                this.glossaryHovercard?.addEventListener(this.options.hovercard.hideEvent, () => {
                    this._destroyHovercard()
                    this._abortFetch()
                })
            })
        }

        // Set ID for hovercard styles
        this.glossaryHovercard.id = 'gs-hovercard'

        // Update hovercard content
        if(!this.options.hovercard.showLoadingAnimation)
            this._updateHovercard(response)

        document.body.appendChild(this.glossaryHovercard)

        // Positioning of hovercard / PopperJS
        this.popper = createPopper(this.currentElement, this.glossaryHovercard, {
            modifiers: [
                {
                    name: 'offset',
                    options: {
                        offset: [16, 5],
                    },
                },
                {
                    name: 'preventOverflow',
                    options: {
                        padding: 16,
                    },
                },
            ]
        })
    }

    /**
     * Updates content or position of hovercard
     * @private
     */
    _updateHovercard(response)
    {
        if(this?.glossaryHovercard) {
            this.glossaryHovercard.innerHTML = response

            if(this.options.hovercard.showLoadingAnimation)
                this.popper.update()
        }
    }

    /**
     * Destroys the hovercard
     * @private
     */
    _destroyHovercard()
    {
        this.popper.destroy()

        // Remove events
        /*if(this.options.hovercard.interactive)
        {
            this.glossaryHovercard.removeEventListener(this.options.hovercard.showEvent, null);
            this.glossaryHovercard.removeEventListener(this.options.hovercard.hideEvent, null);
        }*/

        this.glossaryHovercard.parentNode.removeChild(this.glossaryHovercard)
        this.glossaryHovercard = null
    }

    /**
     * Clears showThreshold
     * @private
     */
    _clearShowDelay()
    {
        if(this.showDelay)
        {
            clearTimeout(this.showDelay)
            this.showDelay = null
        }
    }

    /**
     * Clears hideThreshold
     * @private
     */
    _clearHideTimeout()
    {
        if(this.hideTimeout)
        {
            clearTimeout(this.hideTimeout)
            this.hideTimeout = null
        }
    }
}
