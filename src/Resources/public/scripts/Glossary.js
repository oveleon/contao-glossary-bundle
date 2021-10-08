import { extend } from "./helper/extend";

export class Glossary {

    constructor(options) {
        this.options = extend(true, {
            entrySelector: '#wrapper',
            markup: 'mark',
            markupAttr: {
                'class': 'gl-item'
            },
            tooltip: {
                maxWidth: 500,
                position: 'auto',
                showEvent: 'mouseenter',
                hideEvent: 'mouseleave'
            },
            includes: [
                'body',
                'div,p',
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

        console.log(this.options.config)

        this.contentNodes = document.querySelectorAll(this.options.entrySelector)
        this._parseNodes();
    }

    /**
     * Parse all nodes within an entry selector
     * @private
     */
    _parseNodes(nodes)
    {
        if(typeof nodes === "undefined")
        {
            nodes = this.contentNodes
        }

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


        for (const term of this.options.config)
        {
            const rgx = new RegExp("\\b(" + term.keywords.join('|') + ")\\b", 'gi')
            const matches = node.textContent.match(rgx)

            if(null !== matches)
            {
                // ToDo: 1. Check case sensitive terms
                const filteredMatches = matches.filter((v, i, a) => a.indexOf(v) === i)

                for (const match in filteredMatches)
                {
                    const el = this._createTermMarkup(match);
                }
            }
        }


        //const replaceContent = node.textContent.replaceAll('')
        //node.textContent = "Replaced Term"
    }

    _createTermMarkup(text)
    {
        // ToDo: 2. Create markup
        const el = document.createElement(this.options.markup)

        el.innerText = text
        el.glossary = true

        // Todo: set attributes
        // ToDo: 3. Bind eventListeners with fetch via api
        // ToDo: 4. Create markup

        return el;
    }

    _isValid(node)
    {
        return node.nodeType === Node.TEXT_NODE || (node.nodeType === Node.ELEMENT_NODE && !!node.matches(this.options.includes.join(',')));
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
