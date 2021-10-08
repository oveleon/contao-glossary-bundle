var Glossary;
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ 637:
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  "Glossary": () => (/* binding */ Glossary)
});

;// CONCATENATED MODULE: ./src/Resources/public/scripts/helper/extend.js
function extend() {
    var extended = {};
    var deep = false;
    var i = 0;
    var length = arguments.length;

    // Check if a deep merge
    if ( Object.prototype.toString.call( arguments[0] ) === '[object Boolean]' ) {
        deep = arguments[0];
        i++;
    }

    // Merge the object into the extended object
    var merge = function (obj) {
        for ( var prop in obj ) {
            if ( Object.prototype.hasOwnProperty.call( obj, prop ) ) {
                // If deep merge and property is an object, merge properties
                if ( deep && Object.prototype.toString.call(obj[prop]) === '[object Object]' ) {
                    extended[prop] = extend( true, extended[prop], obj[prop] );
                } else {
                    extended[prop] = obj[prop];
                }
            }
        }
    };

    // Loop through each object and conduct a merge
    for ( ; i < length; i++ ) {
        var obj = arguments[i];
        merge(obj);
    }

    return extended;
}

;// CONCATENATED MODULE: ./src/Resources/public/scripts/Glossary.js


class Glossary {

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


/***/ }),

/***/ 36:
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

const { Glossary } = __webpack_require__(637)
module.exports = Glossary;


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module is referenced by other modules so it can't be inlined
/******/ 	var __webpack_exports__ = __webpack_require__(36);
/******/ 	Glossary = __webpack_exports__;
/******/ 	
/******/ })()
;