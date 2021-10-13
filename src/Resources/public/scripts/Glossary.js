import { extend } from "./helper/extend";

export class Glossary {

    constructor(options) {
        this.options = extend(true, {
            entrySelector: '#wrapper',
            markup: 'mark',
            markupAttr: {
                'class': null
            },
            tooltip: {
                maxWidth: 500,
                position: 'auto',
                showEvent: 'mouseenter',
                hideEvent: 'mouseleave'
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
            route: '/glossary/item/',
            config: []
        }, options || {})

        this.contentNodes = document.querySelectorAll(this.options.entrySelector)
        this._parseNodes(this.contentNodes, 0);
        this._bindEvents();
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

        if(glossaryElements)
        {
            for(const element of glossaryElements)
            {
                element.addEventListener(this.options.tooltip.showEvent, (e) => this._onShowTooltip(e))
                element.addEventListener(this.options.tooltip.hideEvent, (e) => this._onHideTooltip(e))
            }
        }
    }

    _onShowTooltip(event)
    {

        const id = event.target.dataset.glossaryId;

        //ToDO: Fetch via API
        // Implement Cache

    }

    _onHideTooltip()
    {

    }

    _isValid(node)
    {
        return node.nodeType === Node.TEXT_NODE || (node.glossary !== true && node.nodeType === Node.ELEMENT_NODE && !!node.matches(this.options.includes.join(',')));
    }

    /**
     * Parse nodes by selector
     * @param selector
     * @public
     */
    parseNodes(selector)
    {
        if(typeof selector === 'string')
        {
            this.contentNodes = document.querySelectorAll(selector)
        }
        else
        {
            this.contentNodes = selector
        }

        this._parseNodes();
    }
}
