import { extend } from "./helper/extend";
import { createPopper } from '@popperjs/core';

export class Glossary {

    constructor(options) {
        this.options = extend(true, {
            entrySelector: '#wrapper',
            markup: 'mark',
            markupAttr: {
                'class': null
            },
            hovercard: {
                interactive: true,
                showLoadingAnimation: true,
                maxWidth: 500,
                position: 'auto',
                showEvent: 'mouseenter',
                hideEvent: 'mouseleave',
                showDelay: 300,             //
                threshold: 200              // Time how long hovercards are still visible after leaving
            },
            includes: [
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

        this.hideTimeout = null

        // Only parse nodes when config exists
        if(null !== this.options.config)
        {
            this.contentNodes = document.querySelectorAll(this.options.entrySelector)
            this._parseNodes(this.contentNodes, 0)
        }

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

    _replaceTerm(node)
    {
        if(!node.textContent.trim())
            return

        let termCache = [];

        for (const term of this.options.config)
        {
            // Case-sensitive search from term via config
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

        const wrap = document.createElement('span')
        wrap.innerHTML = node.textContent

        node.replaceWith(wrap)

        wrap.outerHTML = wrap.innerHTML
    }

    _createTermMarkup(text, term)
    {
        const el = document.createElement(this.options.markup)

        el.innerText = text

        if(null !== this.options.markupAttr.class)
            el.className = this.options.markupAttr.class

        el.dataset.glossaryId = term.id

        if(this.options.markup === 'a')
        {
            el.title = text
            el.href = term.url
        }

        return el.outerHTML;
    }

    _bindEvents()
    {
        const glossaryElements = document.querySelectorAll('[data-glossary-id]');

        /*if(this.options.hovercard.showEvent === 'click')
            this.options.hovercard.interactive = true*/

        if(glossaryElements)
        {
            for(const element of glossaryElements)
            {
                element.addEventListener(this.options.hovercard.showEvent, (e) => this._onShowHovercard(e))
                element.addEventListener(this.options.hovercard.hideEvent, (e) => this._onHideHovercard(e))
            }
        }
    }

    _onShowHovercard(event)
    {
        this.currentElement = event.target;

        const id = this.currentElement.dataset.glossaryId;

        // if delay -> setTimeout -> hide -> clearTimeout

        if(this.glossaryHovercard)
        {
            this._clearTimeout()
            this._destroyHovercard()
        }

        // Cache implementation
        const cachedResponse = this._getItemCached(id)

        if(cachedResponse)
        {
            //console.log("%cCached", "color:green")
            this._buildHovercard(cachedResponse);
            this._updateHovercard(cachedResponse)
            return
        }

        //console.log("%c Not cached", "color:red")
        document.body.style.cursor = 'progress';
        this._fetchGlossaryItem(id)
    }

    _onHideHovercard(event)
    {
        this._clearTimeout()

        // delay -> check if mouse over hovercard -> yes -> stay -> no -> close hovercard and destroy popper

        if(this?.abortController)
        {
            this.abortController.abort()
        }

        if (this?.glossaryHovercard)
        {
            if(this.options.hovercard.interactive)
            {
                this.hideTimeout = setTimeout(() => {
                    this._destroyHovercard()
                }, this.options.hovercard.threshold)
            }
            else
            {
                this._destroyHovercard()
            }
        }
    }

    async _fetchGlossaryItem(id)
    {
        //console.log('fetch new data')
        this.abortController = new AbortController()

        if(this.options.hovercard.showLoadingAnimation)
            this._buildHovercard();

        await fetch(this.options.route + id + '/html', {signal: this.abortController.signal})
            .then((response) => {
                if(response.status >= 300)
                    throw new Error(response.statusText)

                //result = await fetched.json()
                response.text().then((htmlContent) => {
                    // Hide loading
                    document.body.style.cursor = 'auto';

                    // Write into cache
                    this._setItemCache(id, htmlContent);

                    // Build hovercard
                    if(!this.options.hovercard.showLoadingAnimation)
                        this._buildHovercard(htmlContent);
                    else
                        this._updateHovercard(htmlContent)
                })

            }).catch((e) => {
                document.body.style.cursor = 'auto';
            })
    }

    _buildHovercard(response)
    {
        //console.log('Build hovercard', response, this.currentElement)

        const hovercardContent = '';
        const hovercardArrow = '';

        this.glossaryHovercard = document.createElement('div')
        this.glossaryHovercard.style.width = this.options.hovercard.maxWidth + 'px'

        if(this.options.hovercard.interactive)
        {
            this.glossaryHovercard.addEventListener(this.options.hovercard.showEvent, () =>
            {
                this._clearTimeout()
                this.glossaryHovercard?.addEventListener(this.options.hovercard.hideEvent, () => {
                    this._destroyHovercard()
                })
            })
        }

        this.glossaryHovercard.id = 'gs-hovercard'

        if(!this.options.hovercard.showLoadingAnimation)
            //this.glossaryHovercard.innerHTML = response
            this._updateHovercard(response)


        //Move to config
        //this.currentElement.appendChild(this.glossaryHovercard)
        document.body.appendChild(this.glossaryHovercard)

        this.popper = createPopper(this.currentElement, this.glossaryHovercard, {

        })
    }

    _updateHovercard(response)
    {
        if(this?.glossaryHovercard) {
            this.glossaryHovercard.innerHTML = response

            if(this.options.hovercard.showLoadingAnimation)
                this.popper.update()
        }
    }

    _destroyHovercard()
    {
        this.popper.destroy()

        // Remove events
        if(this.options.hovercard.interactive)
        {
            this.glossaryHovercard.removeEventListener(this.options.hovercard.showEvent, null);
            this.glossaryHovercard.removeEventListener(this.options.hovercard.hideEvent, null);
        }

        this.glossaryHovercard.parentNode.removeChild(this.glossaryHovercard)
        this.glossaryHovercard = null
    }

    _clearTimeout()
    {
        if(this.hideTimeout)
        {
            clearTimeout(this.hideTimeout)
            this.hideTimeout = null
        }
    }

    _setItemCache(id, htmlContent)
    {
        let bag = sessionStorage.getItem('glossaryCache');
        sessionStorage.setItem('glossaryCache', JSON.stringify({...(bag ? JSON.parse(bag) : {}), ...{[id]: htmlContent}}))
    }

    _getItemCached(id)
    {
        let bag = sessionStorage.getItem('glossaryCache');
        bag = JSON.parse(bag)

        return (bag && bag[id]) ? bag[id] : null
    }

    _isValid(node)
    {
        return node.nodeType === Node.TEXT_NODE || (node.glossary !== true && node.nodeType === Node.ELEMENT_NODE && !!node.matches(this.options.includes.join(',')));
    }
}
