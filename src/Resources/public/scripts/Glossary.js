/**
 * Glossary JavaScript
 * Parses html content and creates hovercards
 *
 * package     contao-glossary-bundle
 * license     AGPL-3.0
 * author      Daniele Sciannimanica    <https://github.com/doishub>
 * author      Sebastian Zoglowek       <https://github.com/zoglo>
 * copyright   Oveleon                  <https://www.oveleon.de/>
 */

import { extend } from "./helper/extend";
import { createPopper } from '@popperjs/core';

export class Glossary
{
    constructor(options)
    {
        this.options = extend(true, {
            entrySelector: '#wrapper',          // Selectors for glossary-term search
            markup: 'a',                        // Markup attribute for parsed glossary terms (e.g. 'mark', 'span', 'a')
            markupAttr: null,                   // Markup attributes for created markups
            hovercard: {
                active: true,                   // Whether the hovercard feature should be enabled or not
                id: 'gs-hovercard',             // Id for the hovercard
                interactive: true,              // Enables interaction with the hovercard
                showLoadingAnimation: true,     // Show placeholder animation until content is loaded
                maxWidth: 380,                  // Maximum width of hovercard
                showThreshold: 300,             // Minimum time that showEvent has to be triggered to show a hovercard
                leaveThreshold: 200             // Time that hovercard will stay visible after triggering the hideEvent
            },
            popperOptions: {                    // PopperJS options -> check https://popper.js.org/docs/v2/
                placement: 'top',
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: [0, 8],
                        },
                    },
                    {
                        name: 'preventOverflow',
                        options: {
                            padding: 16,
                        },
                    },
                    {
                        name: 'arrow',
                        options: {
                            padding: 5,
                        },
                    },
                ]
            },
            includes: [                         // Allowed nodes for glossary term markup
                'body',
                'div,span,p',
                'main,section,article',
                'ol,ul,li',
                'table,tr,th,tbody,thead,td',
                'i,b,em,strong',
                'mark,abbr',
                'sub,sup'
            ],
            route: {                            // API settings
                prefix: '/api/glossary/item/',
                suffix: '/html',
                cache: true
            },
            hovercardBreakpoint : 1024,        // Minimum width for hovercard-creation
            config: null
        }, options || {})

        // Eventlistener
        this.showEvent = 'pointerenter'
        this.hideEvent = 'pointerleave'

        this.showDelay = null
        this.hideTimeout = null

        // User agent check
        this.isNewIE = this._isNewIE()

        // Only parse nodes when config exists and markup on mobile is a link
        if(null !== this.options.config && this._shouldParse())
        {
            this.contentNodes = document.querySelectorAll(this.options.entrySelector)
            this._parseNodes(this.contentNodes, 0)
        }

        // Check if hovercards are activated and device is not mobile
        if(this.options.hovercard.active && (window.innerWidth >= this.options.hovercardBreakpoint))
        {
            // Bind events for hovercard creation
            this._bindEvents()
        }
    }

    /**
     * Checks if its an apple device
     * @private
     */
    _isNewIE()
    {
        return (/iPod|iPhone|iPad|Macintosh/.test(navigator.userAgent))
    }

    /**
     * Checks if parsing is allowed
     * @private
     */
    _shouldParse()
    {
        // Only parse if markup is a link
        if(window.innerWidth < this.options.hovercardBreakpoint) {
            return this.options.markup.toLowerCase() === 'a';
        }

        return true
    }

    /**
     * Parse all nodes within an entry selector
     * @private
     */
    _parseNodes(_nodes)
    {
        const nodes = Array.from(_nodes)

        for(const node of nodes)
        {
            if(this._isValidNode(node))
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
    _isValidNode(node)
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
            const rgx = new RegExp("(?:\\s|>|^|\\()(" + term.keywords.join('|') + ")\\b", term.cs ? 'gu' : 'giu')

            const matches = node.textContent.matchAll(rgx)

            if(null !== matches)
            {
                // Get each *group* match of matches
                for (let [group,match] of matches)
                {
                    if(termCache.includes(match))
                        continue

                    termCache.push(match)

                    const elementMarkup = this._createTermMarkup(match, term)

                    // Polyfill for ios/safari etc.
                    // Lookbehind in JS regular expressions ( ?>= ) : https://caniuse.com/js-regexp-lookbehind
                    if(this.isNewIE)
                    {
                        const matchRgx = new RegExp("(?:>|^|\\()(" + match + ")\\b", 'gu')
                        let sentence = [];

                        for(let word of node.textContent.split(' '))
                        {
                            if(word.match(matchRgx))
                                sentence.push(word.replace(match, elementMarkup))
                            else
                                sentence.push(word)
                        }

                        node.textContent = sentence.join(' ')
                    }
                    else
                    {
                        // Lookbehind regex for other browsers
                        const matchRgx = new RegExp("(?<=\\s|>|^|\\()(" + match + ")\\b", 'gu')
                        node.textContent = node.textContent.replace(matchRgx, elementMarkup)
                    }
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
        el.dataset.glossaryId = term.id

        // Link markup
        if(this.options.markup.toLowerCase() === 'a')
        {
            el.title = text
            el.href = term.url
        }

        // Set markup attributes
        if(null !== this.options.markupAttr) {

            for (const key in this.options.markupAttr)
            {
                el.setAttribute(key, this.options.markupAttr[key])
            }
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
                element.addEventListener(this.showEvent, (e) => this._onShowHovercard(e))
                element.addEventListener(this.hideEvent, (e) => this._onHideHovercard(e))
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
        this.currentElement = event.target

        const id = this.currentElement.dataset.glossaryId

        // Clear existing hovercard
        if(this.glossaryHovercard)
        {
            this._clearHideTimeout()
            this._destroyHovercard()
            this._clearShowDelay()
        }



        // Only fetch glossary content after certain time to prevent too many requests
        this.showDelay = setTimeout(() => {
            // Cache implementation
            if(this.options.route.cache)
            {
                const cachedResponse = this._getItemCached(id)

                if(cachedResponse)
                {
                    this._buildHovercard(cachedResponse)
                    this._updateHovercard(cachedResponse)
                    return
                }
            }

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

        if (this.glossaryHovercard)
        {
            // Do not destroy if showEvent is over hovercard
            if(this.options.hovercard.interactive)
            {
                this.hideTimeout = setTimeout(() => {
                    this._abortFetch()
                    this._destroyHovercard()
                }, this.options.hovercard.leaveThreshold)
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
        await fetch(this.options.route.prefix + id + this.options.route.suffix, {signal: this.abortController.signal})
            .then((response) => {
                if(response.status >= 300)
                    throw new Error(response.statusText)

                response.text().then((htmlContent) => {
                    // Write into cache
                    if(this.options.route.cache)
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

        // Create inner markup
        this.glossaryHovercardContent = document.createElement('div')
        this.glossaryHovercardContent.classList.add('content')
        this.glossaryHovercard.appendChild(this.glossaryHovercardContent);

        // Create Popper arrow
        this.popperArrow = document.createElement('div')
        this.popperArrow.setAttribute('data-popper-arrow', '')
        this.glossaryHovercard.appendChild(this.popperArrow);

        if(this.options.hovercard.interactive)
        {
            // Bind show and hide event to hovercard
            this.glossaryHovercard.addEventListener(this.showEvent, () =>
            {
                this._clearHideTimeout()
                this.glossaryHovercard?.addEventListener(this.hideEvent, () => {
                    this._destroyHovercard()
                    this._abortFetch()
                })
            })
        }

        // Set ID for hovercard styles
        this.glossaryHovercard.id = this.options.hovercard.id;

        // Update hovercard content
        if(!this.options.hovercard.showLoadingAnimation) {
            this._updateHovercard(response)
        }
        else
        {
            const loading = document.createElement('span')
                  loading.classList.add('hovercard-loader')

            this.glossaryHovercardContent.appendChild(loading);
        }

        document.body.appendChild(this.glossaryHovercard)

        // Positioning of hovercard / PopperJS
        this.popper = createPopper(this.currentElement, this.glossaryHovercard, this.options.popperOptions)
    }

    /**
     * Updates content or position of hovercard
     * @private
     */
    _updateHovercard(response)
    {
        if(this?.glossaryHovercard) {
            this.glossaryHovercardContent.innerHTML = response

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
            this.glossaryHovercard.removeEventListener(this.showEvent, null);
            this.glossaryHovercard.removeEventListener(this.hideEvent, null);
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
