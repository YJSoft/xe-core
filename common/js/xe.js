/*! Copyright (C) XEHub <https://www.xehub.io> */
/**!
 * @concat modernizr.js + common.js + js_app.js + xml2json.js + xml_handler.js + xml_js_filter.js
 * @brief XE Common JavaScript
 **/
/*!
 * modernizr v3.6.0
 * Build https://modernizr.com/download?-applicationcache-audio-backgroundsize-borderimage-borderradius-boxshadow-canvas-canvastext-cssanimations-csscolumns-cssgradients-cssreflections-csstransforms-csstransforms3d-csstransitions-flexbox-flexboxlegacy-fontface-generatedcontent-geolocation-hashchange-history-hsla-indexeddb-inlinesvg-input-inputtypes-localstorage-multiplebgs-opacity-postmessage-rgba-sessionstorage-smil-svg-svgclippaths-textshadow-video-webgl-websockets-websqldatabase-webworkers-domprefixes-hasevent-prefixes-setclasses-shiv-testallprops-testprop-teststyles-dontmin
 *
 * Copyright (c)
 *  Faruk Ates
 *  Paul Irish
 *  Alex Sexton
 *  Ryan Seddon
 *  Patrick Kettner
 *  Stu Cox
 *  Richard Herrera

 * MIT License
 */

/*
 * Modernizr tests which native CSS3 and HTML5 features are available in the
 * current UA and makes the results available to you in two ways: as properties on
 * a global `Modernizr` object, and as classes on the `<html>` element. This
 * information allows you to progressively enhance your pages with a granular level
 * of control over the experience.
*/

;(function(window, document, undefined){
  var classes = [];
  

  /**
   * docElement is a convenience wrapper to grab the root element of the document
   *
   * @access private
   * @returns {HTMLElement|SVGElement} The root element of the document
   */

  var docElement = document.documentElement;
  

  var tests = [];
  

  /**
   *
   * ModernizrProto is the constructor for Modernizr
   *
   * @class
   * @access public
   */

  var ModernizrProto = {
    // The current version, dummy
    _version: '3.6.0',

    // Any settings that don't work as separate modules
    // can go in here as configuration.
    _config: {
      'classPrefix': '',
      'enableClasses': true,
      'enableJSClass': true,
      'usePrefixes': true
    },

    // Queue of tests
    _q: [],

    // Stub these for people who are listening
    on: function(test, cb) {
      // I don't really think people should do this, but we can
      // safe guard it a bit.
      // -- NOTE:: this gets WAY overridden in src/addTest for actual async tests.
      // This is in case people listen to synchronous tests. I would leave it out,
      // but the code to *disallow* sync tests in the real version of this
      // function is actually larger than this.
      var self = this;
      setTimeout(function() {
        cb(self[test]);
      }, 0);
    },

    addTest: function(name, fn, options) {
      tests.push({name: name, fn: fn, options: options});
    },

    addAsyncTest: function(fn) {
      tests.push({name: null, fn: fn});
    }
  };

  

  // Fake some of Object.create so we can force non test results to be non "own" properties.
  var Modernizr = function() {};
  Modernizr.prototype = ModernizrProto;

  // Leak modernizr globally when you `require` it rather than force it here.
  // Overwrite name so constructor name is nicer :D
  Modernizr = new Modernizr();

  
/*!
{
  "name": "History API",
  "property": "history",
  "caniuse": "history",
  "tags": ["history"],
  "authors": ["Hay Kranen", "Alexander Farkas"],
  "notes": [{
    "name": "W3C Spec",
    "href": "https://www.w3.org/TR/html51/browsers.html#the-history-interface"
  }, {
    "name": "MDN documentation",
    "href": "https://developer.mozilla.org/en-US/docs/Web/API/window.history"
  }],
  "polyfills": ["historyjs", "html5historyapi"]
}
!*/
/* DOC
Detects support for the History API for manipulating the browser session history.
*/

  Modernizr.addTest('history', function() {
    // Issue #733
    // The stock browser on Android 2.2 & 2.3, and 4.0.x returns positive on history support
    // Unfortunately support is really buggy and there is no clean way to detect
    // these bugs, so we fall back to a user agent sniff :(
    var ua = navigator.userAgent;

    // We only want Android 2 and 4.0, stock browser, and not Chrome which identifies
    // itself as 'Mobile Safari' as well, nor Windows Phone (issue #1471).
    if ((ua.indexOf('Android 2.') !== -1 ||
        (ua.indexOf('Android 4.0') !== -1)) &&
        ua.indexOf('Mobile Safari') !== -1 &&
        ua.indexOf('Chrome') === -1 &&
        ua.indexOf('Windows Phone') === -1 &&
    // Since all documents on file:// share an origin, the History apis are
    // blocked there as well
        location.protocol !== 'file:'
    ) {
      return false;
    }

    // Return the regular check
    return (window.history && 'pushState' in window.history);
  });

/*!
{
  "name": "Geolocation API",
  "property": "geolocation",
  "caniuse": "geolocation",
  "tags": ["media"],
  "notes": [{
    "name": "MDN documentation",
    "href": "https://developer.mozilla.org/en-US/docs/WebAPI/Using_geolocation"
  }],
  "polyfills": [
    "joshuabell-polyfill",
    "webshims",
    "geo-location-javascript",
    "geolocation-api-polyfill"
  ]
}
!*/
/* DOC
Detects support for the Geolocation API for users to provide their location to web applications.
*/

  // geolocation is often considered a trivial feature detect...
  // Turns out, it's quite tricky to get right:
  //
  // Using !!navigator.geolocation does two things we don't want. It:
  //   1. Leaks memory in IE9: github.com/Modernizr/Modernizr/issues/513
  //   2. Disables page caching in WebKit: webk.it/43956
  //
  // Meanwhile, in Firefox < 8, an about:config setting could expose
  // a false positive that would throw an exception: bugzil.la/688158

  Modernizr.addTest('geolocation', 'geolocation' in navigator);

/*!
{
  "name": "Application Cache",
  "property": "applicationcache",
  "caniuse": "offline-apps",
  "tags": ["storage", "offline"],
  "notes": [{
    "name": "MDN documentation",
    "href": "https://developer.mozilla.org/en/docs/HTML/Using_the_application_cache"
  }],
  "polyfills": ["html5gears"]
}
!*/
/* DOC
Detects support for the Application Cache, for storing data to enable web-based applications run offline.

The API has been [heavily criticized](http://alistapart.com/article/application-cache-is-a-douchebag) and discussions are underway to address this.
*/

  Modernizr.addTest('applicationcache', 'applicationCache' in window);

/*!
{
  "name": "Session Storage",
  "property": "sessionstorage",
  "tags": ["storage"],
  "polyfills": ["joshuabell-polyfill", "cupcake", "sessionstorage"]
}
!*/

  // Because we are forced to try/catch this, we'll go aggressive.

  // Just FWIW: IE8 Compat mode supports these features completely:
  //   www.quirksmode.org/dom/html5.html
  // But IE8 doesn't support either with local files
  Modernizr.addTest('sessionstorage', function() {
    var mod = 'modernizr';
    try {
      sessionStorage.setItem(mod, mod);
      sessionStorage.removeItem(mod);
      return true;
    } catch (e) {
      return false;
    }
  });

/*!
{
  "name": "Local Storage",
  "property": "localstorage",
  "caniuse": "namevalue-storage",
  "tags": ["storage"],
  "knownBugs": [],
  "notes": [],
  "warnings": [],
  "polyfills": [
    "joshuabell-polyfill",
    "cupcake",
    "storagepolyfill",
    "amplifyjs",
    "yui-cacheoffline"
  ]
}
!*/

  // In FF4, if disabled, window.localStorage should === null.

  // Normally, we could not test that directly and need to do a
  //   `('localStorage' in window)` test first because otherwise Firefox will
  //   throw bugzil.la/365772 if cookies are disabled

  // Similarly, in Chrome with "Block third-party cookies and site data" enabled,
  // attempting to access `window.sessionStorage` will throw an exception. crbug.com/357625

  // Also in iOS5 Private Browsing mode, attempting to use localStorage.setItem
  // will throw the exception:
  //   QUOTA_EXCEEDED_ERROR DOM Exception 22.
  // Peculiarly, getItem and removeItem calls do not throw.

  // Because we are forced to try/catch this, we'll go aggressive.

  // Just FWIW: IE8 Compat mode supports these features completely:
  //   www.quirksmode.org/dom/html5.html
  // But IE8 doesn't support either with local files

  Modernizr.addTest('localstorage', function() {
    var mod = 'modernizr';
    try {
      localStorage.setItem(mod, mod);
      localStorage.removeItem(mod);
      return true;
    } catch (e) {
      return false;
    }
  });

/*!
{
  "name": "Web Workers",
  "property": "webworkers",
  "caniuse" : "webworkers",
  "tags": ["performance", "workers"],
  "notes": [{
    "name": "W3C Reference",
    "href": "https://www.w3.org/TR/workers/"
  }, {
    "name": "HTML5 Rocks article",
    "href": "http://www.html5rocks.com/en/tutorials/workers/basics/"
  }, {
    "name": "MDN documentation",
    "href": "https://developer.mozilla.org/en-US/docs/Web/Guide/Performance/Using_web_workers"
  }],
  "polyfills": ["fakeworker", "html5shims"]
}
!*/
/* DOC
Detects support for the basic `Worker` API from the Web Workers spec. Web Workers provide a simple means for web content to run scripts in background threads.
*/

  Modernizr.addTest('webworkers', 'Worker' in window);

/*!
{
  "name": "WebSockets Support",
  "property": "websockets",
  "authors": ["Phread [fearphage]", "Mike Sherov [mikesherov]", "Burak Yigit Kaya [BYK]"],
  "caniuse": "websockets",
  "tags": ["html5"],
  "warnings": [
    "This test will reject any old version of WebSockets even if it is not prefixed such as in Safari 5.1"
  ],
  "notes": [{
    "name": "CLOSING State and Spec",
    "href": "https://www.w3.org/TR/websockets/#the-websocket-interface"
  }],
  "polyfills": [
    "sockjs",
    "socketio",
    "kaazing-websocket-gateway",
    "websocketjs",
    "atmosphere",
    "graceful-websocket",
    "portal",
    "datachannel"
  ]
}
!*/

  var supports = false;
  try {
    supports = 'WebSocket' in window && window.WebSocket.CLOSING === 2;
  } catch (e) {}
  Modernizr.addTest('websockets', supports);

/*!
{
  "name": "Web SQL Database",
  "property": "websqldatabase",
  "caniuse": "sql-storage",
  "tags": ["storage"]
}
!*/

  // Chrome incognito mode used to throw an exception when using openDatabase
  // It doesn't anymore.
  Modernizr.addTest('websqldatabase', 'openDatabase' in window);

/*!
{
  "name": "SVG",
  "property": "svg",
  "caniuse": "svg",
  "tags": ["svg"],
  "authors": ["Erik Dahlstrom"],
  "polyfills": [
    "svgweb",
    "raphael",
    "amplesdk",
    "canvg",
    "svg-boilerplate",
    "sie",
    "dojogfx",
    "fabricjs"
  ]
}
!*/
/* DOC
Detects support for SVG in `<embed>` or `<object>` elements.
*/

  Modernizr.addTest('svg', !!document.createElementNS && !!document.createElementNS('http://www.w3.org/2000/svg', 'svg').createSVGRect);

/*!
{
  "name": "postMessage",
  "property": "postmessage",
  "caniuse": "x-doc-messaging",
  "notes": [{
    "name": "W3C Spec",
    "href": "http://www.w3.org/TR/html5/comms.html#posting-messages"
  }],
  "polyfills": ["easyxdm", "postmessage-jquery"]
}
!*/
/* DOC
Detects support for the `window.postMessage` protocol for cross-document messaging.
*/

  Modernizr.addTest('postmessage', 'postMessage' in window);


  /**
   * List of property values to set for css tests. See ticket #21
   * http://git.io/vUGl4
   *
   * @memberof Modernizr
   * @name Modernizr._prefixes
   * @optionName Modernizr._prefixes
   * @optionProp prefixes
   * @access public
   * @example
   *
   * Modernizr._prefixes is the internal list of prefixes that we test against
   * inside of things like [prefixed](#modernizr-prefixed) and [prefixedCSS](#-code-modernizr-prefixedcss). It is simply
   * an array of kebab-case vendor prefixes you can use within your code.
   *
   * Some common use cases include
   *
   * Generating all possible prefixed version of a CSS property
   * ```js
   * var rule = Modernizr._prefixes.join('transform: rotate(20deg); ');
   *
   * rule === 'transform: rotate(20deg); webkit-transform: rotate(20deg); moz-transform: rotate(20deg); o-transform: rotate(20deg); ms-transform: rotate(20deg);'
   * ```
   *
   * Generating all possible prefixed version of a CSS value
   * ```js
   * rule = 'display:' +  Modernizr._prefixes.join('flex; display:') + 'flex';
   *
   * rule === 'display:flex; display:-webkit-flex; display:-moz-flex; display:-o-flex; display:-ms-flex; display:flex'
   * ```
   */

  // we use ['',''] rather than an empty array in order to allow a pattern of .`join()`ing prefixes to test
  // values in feature detects to continue to work
  var prefixes = (ModernizrProto._config.usePrefixes ? ' -webkit- -moz- -o- -ms- '.split(' ') : ['','']);

  // expose these for the plugin API. Look in the source for how to join() them against your input
  ModernizrProto._prefixes = prefixes;

  

  /**
   * Object.prototype.toString can be used with every object and allows you to
   * get its class easily. Abstracting it off of an object prevents situations
   * where the toString property has been overridden
   *
   * @access private
   * @function toStringFn
   * @returns {function} An abstracted toString function
   */

  var toStringFn = ({}).toString;
  
/*!
{
  "name": "SVG clip paths",
  "property": "svgclippaths",
  "tags": ["svg"],
  "notes": [{
    "name": "Demo",
    "href": "http://srufaculty.sru.edu/david.dailey/svg/newstuff/clipPath4.svg"
  }]
}
!*/
/* DOC
Detects support for clip paths in SVG (only, not on HTML content).

See [this discussion](https://github.com/Modernizr/Modernizr/issues/213) regarding applying SVG clip paths to HTML content.
*/

  Modernizr.addTest('svgclippaths', function() {
    return !!document.createElementNS &&
      /SVGClipPath/.test(toStringFn.call(document.createElementNS('http://www.w3.org/2000/svg', 'clipPath')));
  });

/*!
{
  "name": "SVG SMIL animation",
  "property": "smil",
  "caniuse": "svg-smil",
  "tags": ["svg"],
  "notes": [{
  "name": "W3C Synchronised Multimedia spec",
  "href": "https://www.w3.org/AudioVideo/"
  }]
}
!*/

  // SVG SMIL animation
  Modernizr.addTest('smil', function() {
    return !!document.createElementNS &&
      /SVGAnimate/.test(toStringFn.call(document.createElementNS('http://www.w3.org/2000/svg', 'animate')));
  });


  /**
   * A convenience helper to check if the document we are running in is an SVG document
   *
   * @access private
   * @returns {boolean}
   */

  var isSVG = docElement.nodeName.toLowerCase() === 'svg';
  

  /**
   * setClasses takes an array of class names and adds them to the root element
   *
   * @access private
   * @function setClasses
   * @param {string[]} classes - Array of class names
   */

  // Pass in an and array of class names, e.g.:
  //  ['no-webp', 'borderradius', ...]
  function setClasses(classes) {
    var className = docElement.className;
    var classPrefix = Modernizr._config.classPrefix || '';

    if (isSVG) {
      className = className.baseVal;
    }

    // Change `no-js` to `js` (independently of the `enableClasses` option)
    // Handle classPrefix on this too
    if (Modernizr._config.enableJSClass) {
      var reJS = new RegExp('(^|\\s)' + classPrefix + 'no-js(\\s|$)');
      className = className.replace(reJS, '$1' + classPrefix + 'js$2');
    }

    if (Modernizr._config.enableClasses) {
      // Add the new classes
      className += ' ' + classPrefix + classes.join(' ' + classPrefix);
      if (isSVG) {
        docElement.className.baseVal = className;
      } else {
        docElement.className = className;
      }
    }

  }

  ;

/**
  * @optionName html5shiv
  * @optionProp html5shiv
  */

  // Take the html5 variable out of the html5shiv scope so we can return it.
  var html5;
  if (!isSVG) {
    /**
     * @preserve HTML5 Shiv 3.7.3 | @afarkas @jdalton @jon_neal @rem | MIT/GPL2 Licensed
     */
    ;(function(window, document) {
      /** version */
      var version = '3.7.3';

      /** Preset options */
      var options = window.html5 || {};

      /** Used to skip problem elements */
      var reSkip = /^<|^(?:button|map|select|textarea|object|iframe|option|optgroup)$/i;

      /** Not all elements can be cloned in IE **/
      var saveClones = /^(?:a|b|code|div|fieldset|h1|h2|h3|h4|h5|h6|i|label|li|ol|p|q|span|strong|style|table|tbody|td|th|tr|ul)$/i;

      /** Detect whether the browser supports default html5 styles */
      var supportsHtml5Styles;

      /** Name of the expando, to work with multiple documents or to re-shiv one document */
      var expando = '_html5shiv';

      /** The id for the the documents expando */
      var expanID = 0;

      /** Cached data for each document */
      var expandoData = {};

      /** Detect whether the browser supports unknown elements */
      var supportsUnknownElements;

      (function() {
        try {
          var a = document.createElement('a');
          a.innerHTML = '<xyz></xyz>';
          //if the hidden property is implemented we can assume, that the browser supports basic HTML5 Styles
          supportsHtml5Styles = ('hidden' in a);

          supportsUnknownElements = a.childNodes.length == 1 || (function() {
            // assign a false positive if unable to shiv
            (document.createElement)('a');
            var frag = document.createDocumentFragment();
            return (
              typeof frag.cloneNode == 'undefined' ||
                typeof frag.createDocumentFragment == 'undefined' ||
                typeof frag.createElement == 'undefined'
            );
          }());
        } catch(e) {
          // assign a false positive if detection fails => unable to shiv
          supportsHtml5Styles = true;
          supportsUnknownElements = true;
        }

      }());

      /*--------------------------------------------------------------------------*/

      /**
       * Creates a style sheet with the given CSS text and adds it to the document.
       * @private
       * @param {Document} ownerDocument The document.
       * @param {String} cssText The CSS text.
       * @returns {StyleSheet} The style element.
       */
      function addStyleSheet(ownerDocument, cssText) {
        var p = ownerDocument.createElement('p'),
          parent = ownerDocument.getElementsByTagName('head')[0] || ownerDocument.documentElement;

        p.innerHTML = 'x<style>' + cssText + '</style>';
        return parent.insertBefore(p.lastChild, parent.firstChild);
      }

      /**
       * Returns the value of `html5.elements` as an array.
       * @private
       * @returns {Array} An array of shived element node names.
       */
      function getElements() {
        var elements = html5.elements;
        return typeof elements == 'string' ? elements.split(' ') : elements;
      }

      /**
       * Extends the built-in list of html5 elements
       * @memberOf html5
       * @param {String|Array} newElements whitespace separated list or array of new element names to shiv
       * @param {Document} ownerDocument The context document.
       */
      function addElements(newElements, ownerDocument) {
        var elements = html5.elements;
        if(typeof elements != 'string'){
          elements = elements.join(' ');
        }
        if(typeof newElements != 'string'){
          newElements = newElements.join(' ');
        }
        html5.elements = elements +' '+ newElements;
        shivDocument(ownerDocument);
      }

      /**
       * Returns the data associated to the given document
       * @private
       * @param {Document} ownerDocument The document.
       * @returns {Object} An object of data.
       */
      function getExpandoData(ownerDocument) {
        var data = expandoData[ownerDocument[expando]];
        if (!data) {
          data = {};
          expanID++;
          ownerDocument[expando] = expanID;
          expandoData[expanID] = data;
        }
        return data;
      }

      /**
       * returns a shived element for the given nodeName and document
       * @memberOf html5
       * @param {String} nodeName name of the element
       * @param {Document|DocumentFragment} ownerDocument The context document.
       * @returns {Object} The shived element.
       */
      function createElement(nodeName, ownerDocument, data){
        if (!ownerDocument) {
          ownerDocument = document;
        }
        if(supportsUnknownElements){
          return ownerDocument.createElement(nodeName);
        }
        if (!data) {
          data = getExpandoData(ownerDocument);
        }
        var node;

        if (data.cache[nodeName]) {
          node = data.cache[nodeName].cloneNode();
        } else if (saveClones.test(nodeName)) {
          node = (data.cache[nodeName] = data.createElem(nodeName)).cloneNode();
        } else {
          node = data.createElem(nodeName);
        }

        // Avoid adding some elements to fragments in IE < 9 because
        // * Attributes like `name` or `type` cannot be set/changed once an element
        //   is inserted into a document/fragment
        // * Link elements with `src` attributes that are inaccessible, as with
        //   a 403 response, will cause the tab/window to crash
        // * Script elements appended to fragments will execute when their `src`
        //   or `text` property is set
        return node.canHaveChildren && !reSkip.test(nodeName) && !node.tagUrn ? data.frag.appendChild(node) : node;
      }

      /**
       * returns a shived DocumentFragment for the given document
       * @memberOf html5
       * @param {Document} ownerDocument The context document.
       * @returns {Object} The shived DocumentFragment.
       */
      function createDocumentFragment(ownerDocument, data){
        if (!ownerDocument) {
          ownerDocument = document;
        }
        if(supportsUnknownElements){
          return ownerDocument.createDocumentFragment();
        }
        data = data || getExpandoData(ownerDocument);
        var clone = data.frag.cloneNode(),
          i = 0,
          elems = getElements(),
          l = elems.length;
        for(;i<l;i++){
          clone.createElement(elems[i]);
        }
        return clone;
      }

      /**
       * Shivs the `createElement` and `createDocumentFragment` methods of the document.
       * @private
       * @param {Document|DocumentFragment} ownerDocument The document.
       * @param {Object} data of the document.
       */
      function shivMethods(ownerDocument, data) {
        if (!data.cache) {
          data.cache = {};
          data.createElem = ownerDocument.createElement;
          data.createFrag = ownerDocument.createDocumentFragment;
          data.frag = data.createFrag();
        }


        ownerDocument.createElement = function(nodeName) {
          //abort shiv
          if (!html5.shivMethods) {
            return data.createElem(nodeName);
          }
          return createElement(nodeName, ownerDocument, data);
        };

        ownerDocument.createDocumentFragment = Function('h,f', 'return function(){' +
                                                        'var n=f.cloneNode(),c=n.createElement;' +
                                                        'h.shivMethods&&(' +
                                                        // unroll the `createElement` calls
                                                        getElements().join().replace(/[\w\-:]+/g, function(nodeName) {
                                                          data.createElem(nodeName);
                                                          data.frag.createElement(nodeName);
                                                          return 'c("' + nodeName + '")';
                                                        }) +
          ');return n}'
                                                       )(html5, data.frag);
      }

      /*--------------------------------------------------------------------------*/

      /**
       * Shivs the given document.
       * @memberOf html5
       * @param {Document} ownerDocument The document to shiv.
       * @returns {Document} The shived document.
       */
      function shivDocument(ownerDocument) {
        if (!ownerDocument) {
          ownerDocument = document;
        }
        var data = getExpandoData(ownerDocument);

        if (html5.shivCSS && !supportsHtml5Styles && !data.hasCSS) {
          data.hasCSS = !!addStyleSheet(ownerDocument,
                                        // corrects block display not defined in IE6/7/8/9
                                        'article,aside,dialog,figcaption,figure,footer,header,hgroup,main,nav,section{display:block}' +
                                        // adds styling not present in IE6/7/8/9
                                        'mark{background:#FF0;color:#000}' +
                                        // hides non-rendered elements
                                        'template{display:none}'
                                       );
        }
        if (!supportsUnknownElements) {
          shivMethods(ownerDocument, data);
        }
        return ownerDocument;
      }

      /*--------------------------------------------------------------------------*/

      /**
       * The `html5` object is exposed so that more elements can be shived and
       * existing shiving can be detected on iframes.
       * @type Object
       * @example
       *
       * // options can be changed before the script is included
       * html5 = { 'elements': 'mark section', 'shivCSS': false, 'shivMethods': false };
       */
      var html5 = {

        /**
         * An array or space separated string of node names of the elements to shiv.
         * @memberOf html5
         * @type Array|String
         */
        'elements': options.elements || 'abbr article aside audio bdi canvas data datalist details dialog figcaption figure footer header hgroup main mark meter nav output picture progress section summary template time video',

        /**
         * current version of html5shiv
         */
        'version': version,

        /**
         * A flag to indicate that the HTML5 style sheet should be inserted.
         * @memberOf html5
         * @type Boolean
         */
        'shivCSS': (options.shivCSS !== false),

        /**
         * Is equal to true if a browser supports creating unknown/HTML5 elements
         * @memberOf html5
         * @type boolean
         */
        'supportsUnknownElements': supportsUnknownElements,

        /**
         * A flag to indicate that the document's `createElement` and `createDocumentFragment`
         * methods should be overwritten.
         * @memberOf html5
         * @type Boolean
         */
        'shivMethods': (options.shivMethods !== false),

        /**
         * A string to describe the type of `html5` object ("default" or "default print").
         * @memberOf html5
         * @type String
         */
        'type': 'default',

        // shivs the document according to the specified `html5` object options
        'shivDocument': shivDocument,

        //creates a shived element
        createElement: createElement,

        //creates a shived documentFragment
        createDocumentFragment: createDocumentFragment,

        //extends list of elements
        addElements: addElements
      };

      /*--------------------------------------------------------------------------*/

      // expose html5
      window.html5 = html5;

      // shiv the document
      shivDocument(document);

      if(typeof module == 'object' && module.exports){
        module.exports = html5;
      }

    }(typeof window !== 'undefined' ? window : this, document));
  }
  ;

  /**
   * createElement is a convenience wrapper around document.createElement. Since we
   * use createElement all over the place, this allows for (slightly) smaller code
   * as well as abstracting away issues with creating elements in contexts other than
   * HTML documents (e.g. SVG documents).
   *
   * @access private
   * @function createElement
   * @returns {HTMLElement|SVGElement} An HTML or SVG element
   */

  function createElement() {
    if (typeof document.createElement !== 'function') {
      // This is the case in IE7, where the type of createElement is "object".
      // For this reason, we cannot call apply() as Object is not a Function.
      return document.createElement(arguments[0]);
    } else if (isSVG) {
      return document.createElementNS.call(document, 'http://www.w3.org/2000/svg', arguments[0]);
    } else {
      return document.createElement.apply(document, arguments);
    }
  }

  ;

  /**
   * Modernizr.hasEvent() detects support for a given event
   *
   * @memberof Modernizr
   * @name Modernizr.hasEvent
   * @optionName Modernizr.hasEvent()
   * @optionProp hasEvent
   * @access public
   * @function hasEvent
   * @param  {string|*} eventName - the name of an event to test for (e.g. "resize")
   * @param  {Element|string} [element=HTMLDivElement] - is the element|document|window|tagName to test on
   * @returns {boolean}
   * @example
   *  `Modernizr.hasEvent` lets you determine if the browser supports a supplied event.
   *  By default, it does this detection on a div element
   *
   * ```js
   *  hasEvent('blur') // true;
   * ```
   *
   * However, you are able to give an object as a second argument to hasEvent to
   * detect an event on something other than a div.
   *
   * ```js
   *  hasEvent('devicelight', window) // true;
   * ```
   *
   */

  var hasEvent = (function() {

    // Detect whether event support can be detected via `in`. Test on a DOM element
    // using the "blur" event b/c it should always exist. bit.ly/event-detection
    var needsFallback = !('onblur' in document.documentElement);

    function inner(eventName, element) {

      var isSupported;
      if (!eventName) { return false; }
      if (!element || typeof element === 'string') {
        element = createElement(element || 'div');
      }

      // Testing via the `in` operator is sufficient for modern browsers and IE.
      // When using `setAttribute`, IE skips "unload", WebKit skips "unload" and
      // "resize", whereas `in` "catches" those.
      eventName = 'on' + eventName;
      isSupported = eventName in element;

      // Fallback technique for old Firefox - bit.ly/event-detection
      if (!isSupported && needsFallback) {
        if (!element.setAttribute) {
          // Switch to generic element if it lacks `setAttribute`.
          // It could be the `document`, `window`, or something else.
          element = createElement('div');
        }

        element.setAttribute(eventName, '');
        isSupported = typeof element[eventName] === 'function';

        if (element[eventName] !== undefined) {
          // If property was created, "remove it" by setting value to `undefined`.
          element[eventName] = undefined;
        }
        element.removeAttribute(eventName);
      }

      return isSupported;
    }
    return inner;
  })();


  ModernizrProto.hasEvent = hasEvent;
  
/*!
{
  "name": "Hashchange event",
  "property": "hashchange",
  "caniuse": "hashchange",
  "tags": ["history"],
  "notes": [{
    "name": "MDN documentation",
    "href": "https://developer.mozilla.org/en-US/docs/Web/API/window.onhashchange"
  }],
  "polyfills": [
    "jquery-hashchange",
    "moo-historymanager",
    "jquery-ajaxy",
    "hasher",
    "shistory"
  ]
}
!*/
/* DOC
Detects support for the `hashchange` event, fired when the current location fragment changes.
*/

  Modernizr.addTest('hashchange', function() {
    if (hasEvent('hashchange', window) === false) {
      return false;
    }

    // documentMode logic from YUI to filter out IE8 Compat Mode
    //   which false positives.
    return (document.documentMode === undefined || document.documentMode > 7);
  });

/*!
{
  "name": "Inline SVG",
  "property": "inlinesvg",
  "caniuse": "svg-html5",
  "tags": ["svg"],
  "notes": [{
    "name": "Test page",
    "href": "https://paulirish.com/demo/inline-svg"
  }, {
    "name": "Test page and results",
    "href": "https://codepen.io/eltonmesquita/full/GgXbvo/"
  }],
  "polyfills": ["inline-svg-polyfill"],
  "knownBugs": ["False negative on some Chromia browsers."]
}
!*/
/* DOC
Detects support for inline SVG in HTML (not within XHTML).
*/

  Modernizr.addTest('inlinesvg', function() {
    var div = createElement('div');
    div.innerHTML = '<svg/>';
    return (typeof SVGRect != 'undefined' && div.firstChild && div.firstChild.namespaceURI) == 'http://www.w3.org/2000/svg';
  });

/*!
{
  "name": "HTML5 Video",
  "property": "video",
  "caniuse": "video",
  "tags": ["html5"],
  "knownBugs": [
    "Without QuickTime, `Modernizr.video.h264` will be `undefined`; https://github.com/Modernizr/Modernizr/issues/546"
  ],
  "polyfills": [
    "html5media",
    "mediaelementjs",
    "sublimevideo",
    "videojs",
    "leanbackplayer",
    "videoforeverybody"
  ]
}
!*/
/* DOC
Detects support for the video element, as well as testing what types of content it supports.

Subproperties are provided to describe support for `ogg`, `h264` and `webm` formats, e.g.:

```javascript
Modernizr.video         // true
Modernizr.video.ogg     // 'probably'
```
*/

  // Codec values from : github.com/NielsLeenheer/html5test/blob/9106a8/index.html#L845
  //                     thx to NielsLeenheer and zcorpan

  // Note: in some older browsers, "no" was a return value instead of empty string.
  //   It was live in FF3.5.0 and 3.5.1, but fixed in 3.5.2
  //   It was also live in Safari 4.0.0 - 4.0.4, but fixed in 4.0.5

  Modernizr.addTest('video', function() {
    var elem = createElement('video');
    var bool = false;

    // IE9 Running on Windows Server SKU can cause an exception to be thrown, bug #224
    try {
      bool = !!elem.canPlayType
      if (bool) {
        bool = new Boolean(bool);
        bool.ogg = elem.canPlayType('video/ogg; codecs="theora"').replace(/^no$/, '');

        // Without QuickTime, this value will be `undefined`. github.com/Modernizr/Modernizr/issues/546
        bool.h264 = elem.canPlayType('video/mp4; codecs="avc1.42E01E"').replace(/^no$/, '');

        bool.webm = elem.canPlayType('video/webm; codecs="vp8, vorbis"').replace(/^no$/, '');

        bool.vp9 = elem.canPlayType('video/webm; codecs="vp9"').replace(/^no$/, '');

        bool.hls = elem.canPlayType('application/x-mpegURL; codecs="avc1.42E01E"').replace(/^no$/, '');
      }
    } catch (e) {}

    return bool;
  });

/*!
{
  "name": "CSS Opacity",
  "caniuse": "css-opacity",
  "property": "opacity",
  "tags": ["css"]
}
!*/

  // Browsers that actually have CSS Opacity implemented have done so
  // according to spec, which means their return values are within the
  // range of [0.0,1.0] - including the leading zero.

  Modernizr.addTest('opacity', function() {
    var style = createElement('a').style;
    style.cssText = prefixes.join('opacity:.55;');

    // The non-literal . in this regex is intentional:
    // German Chrome returns this value as 0,55
    // github.com/Modernizr/Modernizr/issues/#issue/59/comment/516632
    return (/^0.55$/).test(style.opacity);
  });

/*!
{
  "name": "Canvas",
  "property": "canvas",
  "caniuse": "canvas",
  "tags": ["canvas", "graphics"],
  "polyfills": ["flashcanvas", "excanvas", "slcanvas", "fxcanvas"]
}
!*/
/* DOC
Detects support for the `<canvas>` element for 2D drawing.
*/

  // On the S60 and BB Storm, getContext exists, but always returns undefined
  // so we actually have to call getContext() to verify
  // github.com/Modernizr/Modernizr/issues/issue/97/
  Modernizr.addTest('canvas', function() {
    var elem = createElement('canvas');
    return !!(elem.getContext && elem.getContext('2d'));
  });

/*!
{
  "name" : "HTML5 Audio Element",
  "property": "audio",
  "tags" : ["html5", "audio", "media"]
}
!*/
/* DOC
Detects the audio element
*/

  // This tests evaluates support of the audio element, as well as
  // testing what types of content it supports.
  //
  // We're using the Boolean constructor here, so that we can extend the value
  // e.g.  Modernizr.audio     // true
  //       Modernizr.audio.ogg // 'probably'
  //
  // Codec values from : github.com/NielsLeenheer/html5test/blob/9106a8/index.html#L845
  //                     thx to NielsLeenheer and zcorpan

  // Note: in some older browsers, "no" was a return value instead of empty string.
  //   It was live in FF3.5.0 and 3.5.1, but fixed in 3.5.2
  //   It was also live in Safari 4.0.0 - 4.0.4, but fixed in 4.0.5
  Modernizr.addTest('audio', function() {
    var elem = createElement('audio');
    var bool = false;

    try {
      bool = !!elem.canPlayType
      if (bool) {
        bool      = new Boolean(bool);
        bool.ogg  = elem.canPlayType('audio/ogg; codecs="vorbis"') .replace(/^no$/, '');
        bool.mp3  = elem.canPlayType('audio/mpeg; codecs="mp3"')   .replace(/^no$/, '');
        bool.opus  = elem.canPlayType('audio/ogg; codecs="opus"')  ||
                     elem.canPlayType('audio/webm; codecs="opus"') .replace(/^no$/, '');

        // Mimetypes accepted:
        //   developer.mozilla.org/En/Media_formats_supported_by_the_audio_and_video_elements
        //   bit.ly/iphoneoscodecs
        bool.wav  = elem.canPlayType('audio/wav; codecs="1"')     .replace(/^no$/, '');
        bool.m4a  = (elem.canPlayType('audio/x-m4a;')            ||
                     elem.canPlayType('audio/aac;'))             .replace(/^no$/, '');
      }
    } catch (e) { }

    return bool;
  });

/*!
{
  "name": "WebGL",
  "property": "webgl",
  "caniuse": "webgl",
  "tags": ["webgl", "graphics"],
  "polyfills": ["jebgl", "cwebgl", "iewebgl"]
}
!*/

  Modernizr.addTest('webgl', function() {
    var canvas = createElement('canvas');
    var supports = 'probablySupportsContext' in canvas ? 'probablySupportsContext' :  'supportsContext';
    if (supports in canvas) {
      return canvas[supports]('webgl') || canvas[supports]('experimental-webgl');
    }
    return 'WebGLRenderingContext' in window;
  });

/*!
{
  "name": "CSS Gradients",
  "caniuse": "css-gradients",
  "property": "cssgradients",
  "tags": ["css"],
  "knownBugs": ["False-positives on webOS (https://github.com/Modernizr/Modernizr/issues/202)"],
  "notes": [{
    "name": "Webkit Gradient Syntax",
    "href": "https://webkit.org/blog/175/introducing-css-gradients/"
  },{
    "name": "Linear Gradient Syntax",
    "href": "https://developer.mozilla.org/en-US/docs/Web/CSS/linear-gradient"
  },{
    "name": "W3C Gradient Spec",
    "href": "https://drafts.csswg.org/css-images-3/#gradients"
  }]
}
!*/


  Modernizr.addTest('cssgradients', function() {

    var str1 = 'background-image:';
    var str2 = 'gradient(linear,left top,right bottom,from(#9f9),to(white));';
    var css = '';
    var angle;

    for (var i = 0, len = prefixes.length - 1; i < len; i++) {
      angle = (i === 0 ? 'to ' : '');
      css += str1 + prefixes[i] + 'linear-gradient(' + angle + 'left top, #9f9, white);';
    }

    if (Modernizr._config.usePrefixes) {
    // legacy webkit syntax (FIXME: remove when syntax not in use anymore)
      css += str1 + '-webkit-' + str2;
    }

    var elem = createElement('a');
    var style = elem.style;
    style.cssText = css;

    // IE6 returns undefined so cast to string
    return ('' + style.backgroundImage).indexOf('gradient') > -1;
  });

/*!
{
  "name": "Canvas text",
  "property": "canvastext",
  "caniuse": "canvas-text",
  "tags": ["canvas", "graphics"],
  "polyfills": ["canvastext"]
}
!*/
/* DOC
Detects support for the text APIs for `<canvas>` elements.
*/

  Modernizr.addTest('canvastext',  function() {
    if (Modernizr.canvas  === false) {
      return false;
    }
    return typeof createElement('canvas').getContext('2d').fillText == 'function';
  });

/*!
{
  "name": "CSS rgba",
  "caniuse": "css3-colors",
  "property": "rgba",
  "tags": ["css"],
  "notes": [{
    "name": "CSSTricks Tutorial",
    "href": "https://css-tricks.com/rgba-browser-support/"
  }]
}
!*/

  Modernizr.addTest('rgba', function() {
    var style = createElement('a').style;
    style.cssText = 'background-color:rgba(150,255,150,.5)';

    return ('' + style.backgroundColor).indexOf('rgba') > -1;
  });

/*!
{
  "name": "CSS Multiple Backgrounds",
  "caniuse": "multibackgrounds",
  "property": "multiplebgs",
  "tags": ["css"]
}
!*/

  // Setting multiple images AND a color on the background shorthand property
  // and then querying the style.background property value for the number of
  // occurrences of "url(" is a reliable method for detecting ACTUAL support for this!

  Modernizr.addTest('multiplebgs', function() {
    var style = createElement('a').style;
    style.cssText = 'background:url(https://),url(https://),red url(https://)';

    // If the UA supports multiple backgrounds, there should be three occurrences
    // of the string "url(" in the return value for elemStyle.background
    return (/(url\s*\(.*?){3}/).test(style.background);
  });


  /**
   * since we have a fairly large number of input tests that don't mutate the input
   * we create a single element that can be shared with all of those tests for a
   * minor perf boost
   *
   * @access private
   * @returns {HTMLInputElement}
   */
  var inputElem = createElement('input');
  
/*!
{
  "name": "Form input types",
  "property": "inputtypes",
  "caniuse": "forms",
  "tags": ["forms"],
  "authors": ["Mike Taylor"],
  "polyfills": [
    "jquerytools",
    "webshims",
    "h5f",
    "webforms2",
    "nwxforms",
    "fdslider",
    "html5slider",
    "galleryhtml5forms",
    "jscolor",
    "html5formshim",
    "selectedoptionsjs",
    "formvalidationjs"
  ]
}
!*/
/* DOC
Detects support for HTML5 form input types and exposes Boolean subproperties with the results:

```javascript
Modernizr.inputtypes.color
Modernizr.inputtypes.date
Modernizr.inputtypes.datetime
Modernizr.inputtypes['datetime-local']
Modernizr.inputtypes.email
Modernizr.inputtypes.month
Modernizr.inputtypes.number
Modernizr.inputtypes.range
Modernizr.inputtypes.search
Modernizr.inputtypes.tel
Modernizr.inputtypes.time
Modernizr.inputtypes.url
Modernizr.inputtypes.week
```
*/

  // Run through HTML5's new input types to see if the UA understands any.
  //   This is put behind the tests runloop because it doesn't return a
  //   true/false like all the other tests; instead, it returns an object
  //   containing each input type with its corresponding true/false value

  // Big thanks to @miketaylr for the html5 forms expertise. miketaylr.com/
  var inputtypes = 'search tel url email datetime date month week time datetime-local number range color'.split(' ');
  var inputs = {};

  Modernizr.inputtypes = (function(props) {
    var len = props.length;
    var smile = '1)';
    var inputElemType;
    var defaultView;
    var bool;

    for (var i = 0; i < len; i++) {

      inputElem.setAttribute('type', inputElemType = props[i]);
      bool = inputElem.type !== 'text' && 'style' in inputElem;

      // We first check to see if the type we give it sticks..
      // If the type does, we feed it a textual value, which shouldn't be valid.
      // If the value doesn't stick, we know there's input sanitization which infers a custom UI
      if (bool) {

        inputElem.value         = smile;
        inputElem.style.cssText = 'position:absolute;visibility:hidden;';

        if (/^range$/.test(inputElemType) && inputElem.style.WebkitAppearance !== undefined) {

          docElement.appendChild(inputElem);
          defaultView = document.defaultView;

          // Safari 2-4 allows the smiley as a value, despite making a slider
          bool =  defaultView.getComputedStyle &&
            defaultView.getComputedStyle(inputElem, null).WebkitAppearance !== 'textfield' &&
            // Mobile android web browser has false positive, so must
            // check the height to see if the widget is actually there.
            (inputElem.offsetHeight !== 0);

          docElement.removeChild(inputElem);

        } else if (/^(search|tel)$/.test(inputElemType)) {
          // Spec doesn't define any special parsing or detectable UI
          //   behaviors so we pass these through as true

          // Interestingly, opera fails the earlier test, so it doesn't
          //  even make it here.

        } else if (/^(url|email)$/.test(inputElemType)) {
          // Real url and email support comes with prebaked validation.
          bool = inputElem.checkValidity && inputElem.checkValidity() === false;

        } else {
          // If the upgraded input compontent rejects the :) text, we got a winner
          bool = inputElem.value != smile;
        }
      }

      inputs[ props[i] ] = !!bool;
    }
    return inputs;
  })(inputtypes);

/*!
{
  "name": "Input attributes",
  "property": "input",
  "tags": ["forms"],
  "authors": ["Mike Taylor"],
  "notes": [{
    "name": "WHATWG spec",
    "href": "https://html.spec.whatwg.org/multipage/forms.html#input-type-attr-summary"
  }],
  "knownBugs": ["Some blackberry devices report false positive for input.multiple"]
}
!*/
/* DOC
Detects support for HTML5 `<input>` element attributes and exposes Boolean subproperties with the results:

```javascript
Modernizr.input.autocomplete
Modernizr.input.autofocus
Modernizr.input.list
Modernizr.input.max
Modernizr.input.min
Modernizr.input.multiple
Modernizr.input.pattern
Modernizr.input.placeholder
Modernizr.input.required
Modernizr.input.step
```
*/

  // Run through HTML5's new input attributes to see if the UA understands any.
  // Mike Taylr has created a comprehensive resource for testing these attributes
  //   when applied to all input types:
  //   miketaylr.com/code/input-type-attr.html

  // Only input placeholder is tested while textarea's placeholder is not.
  // Currently Safari 4 and Opera 11 have support only for the input placeholder
  // Both tests are available in feature-detects/forms-placeholder.js

  var inputattrs = 'autocomplete autofocus list placeholder max min multiple pattern required step'.split(' ');
  var attrs = {};

  Modernizr.input = (function(props) {
    for (var i = 0, len = props.length; i < len; i++) {
      attrs[ props[i] ] = !!(props[i] in inputElem);
    }
    if (attrs.list) {
      // safari false positive's on datalist: webk.it/74252
      // see also github.com/Modernizr/Modernizr/issues/146
      attrs.list = !!(createElement('datalist') && window.HTMLDataListElement);
    }
    return attrs;
  })(inputattrs);



  /**
   * contains checks to see if a string contains another string
   *
   * @access private
   * @function contains
   * @param {string} str - The string we want to check for substrings
   * @param {string} substr - The substring we want to search the first string for
   * @returns {boolean}
   */

  function contains(str, substr) {
    return !!~('' + str).indexOf(substr);
  }

  ;
/*!
{
  "name": "CSS HSLA Colors",
  "caniuse": "css3-colors",
  "property": "hsla",
  "tags": ["css"]
}
!*/

  Modernizr.addTest('hsla', function() {
    var style = createElement('a').style;
    style.cssText = 'background-color:hsla(120,40%,100%,.5)';
    return contains(style.backgroundColor, 'rgba') || contains(style.backgroundColor, 'hsla');
  });

/*!
{
  "name": "CSS Supports",
  "property": "supports",
  "caniuse": "css-featurequeries",
  "tags": ["css"],
  "builderAliases": ["css_supports"],
  "notes": [{
    "name": "W3 Spec",
    "href": "http://dev.w3.org/csswg/css3-conditional/#at-supports"
  },{
    "name": "Related Github Issue",
    "href": "https://github.com/Modernizr/Modernizr/issues/648"
  },{
    "name": "W3 Info",
    "href": "http://dev.w3.org/csswg/css3-conditional/#the-csssupportsrule-interface"
  }]
}
!*/

  var newSyntax = 'CSS' in window && 'supports' in window.CSS;
  var oldSyntax = 'supportsCSS' in window;
  Modernizr.addTest('supports', newSyntax || oldSyntax);


  /**
   * is returns a boolean if the typeof an obj is exactly type.
   *
   * @access private
   * @function is
   * @param {*} obj - A thing we want to check the type of
   * @param {string} type - A string to compare the typeof against
   * @returns {boolean}
   */

  function is(obj, type) {
    return typeof obj === type;
  }
  ;

  /**
   * Run through all tests and detect their support in the current UA.
   *
   * @access private
   */

  function testRunner() {
    var featureNames;
    var feature;
    var aliasIdx;
    var result;
    var nameIdx;
    var featureName;
    var featureNameSplit;

    for (var featureIdx in tests) {
      if (tests.hasOwnProperty(featureIdx)) {
        featureNames = [];
        feature = tests[featureIdx];
        // run the test, throw the return value into the Modernizr,
        // then based on that boolean, define an appropriate className
        // and push it into an array of classes we'll join later.
        //
        // If there is no name, it's an 'async' test that is run,
        // but not directly added to the object. That should
        // be done with a post-run addTest call.
        if (feature.name) {
          featureNames.push(feature.name.toLowerCase());

          if (feature.options && feature.options.aliases && feature.options.aliases.length) {
            // Add all the aliases into the names list
            for (aliasIdx = 0; aliasIdx < feature.options.aliases.length; aliasIdx++) {
              featureNames.push(feature.options.aliases[aliasIdx].toLowerCase());
            }
          }
        }

        // Run the test, or use the raw value if it's not a function
        result = is(feature.fn, 'function') ? feature.fn() : feature.fn;


        // Set each of the names on the Modernizr object
        for (nameIdx = 0; nameIdx < featureNames.length; nameIdx++) {
          featureName = featureNames[nameIdx];
          // Support dot properties as sub tests. We don't do checking to make sure
          // that the implied parent tests have been added. You must call them in
          // order (either in the test, or make the parent test a dependency).
          //
          // Cap it to TWO to make the logic simple and because who needs that kind of subtesting
          // hashtag famous last words
          featureNameSplit = featureName.split('.');

          if (featureNameSplit.length === 1) {
            Modernizr[featureNameSplit[0]] = result;
          } else {
            // cast to a Boolean, if not one already
            if (Modernizr[featureNameSplit[0]] && !(Modernizr[featureNameSplit[0]] instanceof Boolean)) {
              Modernizr[featureNameSplit[0]] = new Boolean(Modernizr[featureNameSplit[0]]);
            }

            Modernizr[featureNameSplit[0]][featureNameSplit[1]] = result;
          }

          classes.push((result ? '' : 'no-') + featureNameSplit.join('-'));
        }
      }
    }
  }
  ;

  /**
   * If the browsers follow the spec, then they would expose vendor-specific styles as:
   *   elem.style.WebkitBorderRadius
   * instead of something like the following (which is technically incorrect):
   *   elem.style.webkitBorderRadius

   * WebKit ghosts their properties in lowercase but Opera & Moz do not.
   * Microsoft uses a lowercase `ms` instead of the correct `Ms` in IE8+
   *   erik.eae.net/archives/2008/03/10/21.48.10/

   * More here: github.com/Modernizr/Modernizr/issues/issue/21
   *
   * @access private
   * @returns {string} The string representing the vendor-specific style properties
   */

  var omPrefixes = 'Moz O ms Webkit';
  

  /**
   * List of JavaScript DOM values used for tests
   *
   * @memberof Modernizr
   * @name Modernizr._domPrefixes
   * @optionName Modernizr._domPrefixes
   * @optionProp domPrefixes
   * @access public
   * @example
   *
   * Modernizr._domPrefixes is exactly the same as [_prefixes](#modernizr-_prefixes), but rather
   * than kebab-case properties, all properties are their Capitalized variant
   *
   * ```js
   * Modernizr._domPrefixes === [ "Moz", "O", "ms", "Webkit" ];
   * ```
   */

  var domPrefixes = (ModernizrProto._config.usePrefixes ? omPrefixes.toLowerCase().split(' ') : []);
  ModernizrProto._domPrefixes = domPrefixes;
  

  /**
   * hasOwnProp is a shim for hasOwnProperty that is needed for Safari 2.0 support
   *
   * @author kangax
   * @access private
   * @function hasOwnProp
   * @param {object} object - The object to check for a property
   * @param {string} property - The property to check for
   * @returns {boolean}
   */

  // hasOwnProperty shim by kangax needed for Safari 2.0 support
  var hasOwnProp;

  (function() {
    var _hasOwnProperty = ({}).hasOwnProperty;
    /* istanbul ignore else */
    /* we have no way of testing IE 5.5 or safari 2,
     * so just assume the else gets hit */
    if (!is(_hasOwnProperty, 'undefined') && !is(_hasOwnProperty.call, 'undefined')) {
      hasOwnProp = function(object, property) {
        return _hasOwnProperty.call(object, property);
      };
    }
    else {
      hasOwnProp = function(object, property) { /* yes, this can give false positives/negatives, but most of the time we don't care about those */
        return ((property in object) && is(object.constructor.prototype[property], 'undefined'));
      };
    }
  })();

  


   // _l tracks listeners for async tests, as well as tests that execute after the initial run
  ModernizrProto._l = {};

  /**
   * Modernizr.on is a way to listen for the completion of async tests. Being
   * asynchronous, they may not finish before your scripts run. As a result you
   * will get a possibly false negative `undefined` value.
   *
   * @memberof Modernizr
   * @name Modernizr.on
   * @access public
   * @function on
   * @param {string} feature - String name of the feature detect
   * @param {function} cb - Callback function returning a Boolean - true if feature is supported, false if not
   * @example
   *
   * ```js
   * Modernizr.on('flash', function( result ) {
   *   if (result) {
   *    // the browser has flash
   *   } else {
   *     // the browser does not have flash
   *   }
   * });
   * ```
   */

  ModernizrProto.on = function(feature, cb) {
    // Create the list of listeners if it doesn't exist
    if (!this._l[feature]) {
      this._l[feature] = [];
    }

    // Push this test on to the listener list
    this._l[feature].push(cb);

    // If it's already been resolved, trigger it on next tick
    if (Modernizr.hasOwnProperty(feature)) {
      // Next Tick
      setTimeout(function() {
        Modernizr._trigger(feature, Modernizr[feature]);
      }, 0);
    }
  };

  /**
   * _trigger is the private function used to signal test completion and run any
   * callbacks registered through [Modernizr.on](#modernizr-on)
   *
   * @memberof Modernizr
   * @name Modernizr._trigger
   * @access private
   * @function _trigger
   * @param {string} feature - string name of the feature detect
   * @param {function|boolean} [res] - A feature detection function, or the boolean =
   * result of a feature detection function
   */

  ModernizrProto._trigger = function(feature, res) {
    if (!this._l[feature]) {
      return;
    }

    var cbs = this._l[feature];

    // Force async
    setTimeout(function() {
      var i, cb;
      for (i = 0; i < cbs.length; i++) {
        cb = cbs[i];
        cb(res);
      }
    }, 0);

    // Don't trigger these again
    delete this._l[feature];
  };

  /**
   * addTest allows you to define your own feature detects that are not currently
   * included in Modernizr (under the covers it's the exact same code Modernizr
   * uses for its own [feature detections](https://github.com/Modernizr/Modernizr/tree/master/feature-detects)). Just like the offical detects, the result
   * will be added onto the Modernizr object, as well as an appropriate className set on
   * the html element when configured to do so
   *
   * @memberof Modernizr
   * @name Modernizr.addTest
   * @optionName Modernizr.addTest()
   * @optionProp addTest
   * @access public
   * @function addTest
   * @param {string|object} feature - The string name of the feature detect, or an
   * object of feature detect names and test
   * @param {function|boolean} test - Function returning true if feature is supported,
   * false if not. Otherwise a boolean representing the results of a feature detection
   * @example
   *
   * The most common way of creating your own feature detects is by calling
   * `Modernizr.addTest` with a string (preferably just lowercase, without any
   * punctuation), and a function you want executed that will return a boolean result
   *
   * ```js
   * Modernizr.addTest('itsTuesday', function() {
   *  var d = new Date();
   *  return d.getDay() === 2;
   * });
   * ```
   *
   * When the above is run, it will set Modernizr.itstuesday to `true` when it is tuesday,
   * and to `false` every other day of the week. One thing to notice is that the names of
   * feature detect functions are always lowercased when added to the Modernizr object. That
   * means that `Modernizr.itsTuesday` will not exist, but `Modernizr.itstuesday` will.
   *
   *
   *  Since we only look at the returned value from any feature detection function,
   *  you do not need to actually use a function. For simple detections, just passing
   *  in a statement that will return a boolean value works just fine.
   *
   * ```js
   * Modernizr.addTest('hasJquery', 'jQuery' in window);
   * ```
   *
   * Just like before, when the above runs `Modernizr.hasjquery` will be true if
   * jQuery has been included on the page. Not using a function saves a small amount
   * of overhead for the browser, as well as making your code much more readable.
   *
   * Finally, you also have the ability to pass in an object of feature names and
   * their tests. This is handy if you want to add multiple detections in one go.
   * The keys should always be a string, and the value can be either a boolean or
   * function that returns a boolean.
   *
   * ```js
   * var detects = {
   *  'hasjquery': 'jQuery' in window,
   *  'itstuesday': function() {
   *    var d = new Date();
   *    return d.getDay() === 2;
   *  }
   * }
   *
   * Modernizr.addTest(detects);
   * ```
   *
   * There is really no difference between the first methods and this one, it is
   * just a convenience to let you write more readable code.
   */

  function addTest(feature, test) {

    if (typeof feature == 'object') {
      for (var key in feature) {
        if (hasOwnProp(feature, key)) {
          addTest(key, feature[ key ]);
        }
      }
    } else {

      feature = feature.toLowerCase();
      var featureNameSplit = feature.split('.');
      var last = Modernizr[featureNameSplit[0]];

      // Again, we don't check for parent test existence. Get that right, though.
      if (featureNameSplit.length == 2) {
        last = last[featureNameSplit[1]];
      }

      if (typeof last != 'undefined') {
        // we're going to quit if you're trying to overwrite an existing test
        // if we were to allow it, we'd do this:
        //   var re = new RegExp("\\b(no-)?" + feature + "\\b");
        //   docElement.className = docElement.className.replace( re, '' );
        // but, no rly, stuff 'em.
        return Modernizr;
      }

      test = typeof test == 'function' ? test() : test;

      // Set the value (this is the magic, right here).
      if (featureNameSplit.length == 1) {
        Modernizr[featureNameSplit[0]] = test;
      } else {
        // cast to a Boolean, if not one already
        if (Modernizr[featureNameSplit[0]] && !(Modernizr[featureNameSplit[0]] instanceof Boolean)) {
          Modernizr[featureNameSplit[0]] = new Boolean(Modernizr[featureNameSplit[0]]);
        }

        Modernizr[featureNameSplit[0]][featureNameSplit[1]] = test;
      }

      // Set a single class (either `feature` or `no-feature`)
      setClasses([(!!test && test != false ? '' : 'no-') + featureNameSplit.join('-')]);

      // Trigger the event
      Modernizr._trigger(feature, test);
    }

    return Modernizr; // allow chaining.
  }

  // After all the tests are run, add self to the Modernizr prototype
  Modernizr._q.push(function() {
    ModernizrProto.addTest = addTest;
  });

  


  /**
   * cssToDOM takes a kebab-case string and converts it to camelCase
   * e.g. box-sizing -> boxSizing
   *
   * @access private
   * @function cssToDOM
   * @param {string} name - String name of kebab-case prop we want to convert
   * @returns {string} The camelCase version of the supplied name
   */

  function cssToDOM(name) {
    return name.replace(/([a-z])-([a-z])/g, function(str, m1, m2) {
      return m1 + m2.toUpperCase();
    }).replace(/^-/, '');
  }
  ;

  var cssomPrefixes = (ModernizrProto._config.usePrefixes ? omPrefixes.split(' ') : []);
  ModernizrProto._cssomPrefixes = cssomPrefixes;
  

  /**
   * atRule returns a given CSS property at-rule (eg @keyframes), possibly in
   * some prefixed form, or false, in the case of an unsupported rule
   *
   * @memberof Modernizr
   * @name Modernizr.atRule
   * @optionName Modernizr.atRule()
   * @optionProp atRule
   * @access public
   * @function atRule
   * @param {string} prop - String name of the @-rule to test for
   * @returns {string|boolean} The string representing the (possibly prefixed)
   * valid version of the @-rule, or `false` when it is unsupported.
   * @example
   * ```js
   *  var keyframes = Modernizr.atRule('@keyframes');
   *
   *  if (keyframes) {
   *    // keyframes are supported
   *    // could be `@-webkit-keyframes` or `@keyframes`
   *  } else {
   *    // keyframes === `false`
   *  }
   * ```
   *
   */

  var atRule = function(prop) {
    var length = prefixes.length;
    var cssrule = window.CSSRule;
    var rule;

    if (typeof cssrule === 'undefined') {
      return undefined;
    }

    if (!prop) {
      return false;
    }

    // remove literal @ from beginning of provided property
    prop = prop.replace(/^@/, '');

    // CSSRules use underscores instead of dashes
    rule = prop.replace(/-/g, '_').toUpperCase() + '_RULE';

    if (rule in cssrule) {
      return '@' + prop;
    }

    for (var i = 0; i < length; i++) {
      // prefixes gives us something like -o-, and we want O_
      var prefix = prefixes[i];
      var thisRule = prefix.toUpperCase() + '_' + rule;

      if (thisRule in cssrule) {
        return '@-' + prefix.toLowerCase() + '-' + prop;
      }
    }

    return false;
  };

  ModernizrProto.atRule = atRule;

  

  /**
   * getBody returns the body of a document, or an element that can stand in for
   * the body if a real body does not exist
   *
   * @access private
   * @function getBody
   * @returns {HTMLElement|SVGElement} Returns the real body of a document, or an
   * artificially created element that stands in for the body
   */

  function getBody() {
    // After page load injecting a fake body doesn't work so check if body exists
    var body = document.body;

    if (!body) {
      // Can't use the real body create a fake one.
      body = createElement(isSVG ? 'svg' : 'body');
      body.fake = true;
    }

    return body;
  }

  ;

  /**
   * injectElementWithStyles injects an element with style element and some CSS rules
   *
   * @access private
   * @function injectElementWithStyles
   * @param {string} rule - String representing a css rule
   * @param {function} callback - A function that is used to test the injected element
   * @param {number} [nodes] - An integer representing the number of additional nodes you want injected
   * @param {string[]} [testnames] - An array of strings that are used as ids for the additional nodes
   * @returns {boolean}
   */

  function injectElementWithStyles(rule, callback, nodes, testnames) {
    var mod = 'modernizr';
    var style;
    var ret;
    var node;
    var docOverflow;
    var div = createElement('div');
    var body = getBody();

    if (parseInt(nodes, 10)) {
      // In order not to give false positives we create a node for each test
      // This also allows the method to scale for unspecified uses
      while (nodes--) {
        node = createElement('div');
        node.id = testnames ? testnames[nodes] : mod + (nodes + 1);
        div.appendChild(node);
      }
    }

    style = createElement('style');
    style.type = 'text/css';
    style.id = 's' + mod;

    // IE6 will false positive on some tests due to the style element inside the test div somehow interfering offsetHeight, so insert it into body or fakebody.
    // Opera will act all quirky when injecting elements in documentElement when page is served as xml, needs fakebody too. #270
    (!body.fake ? div : body).appendChild(style);
    body.appendChild(div);

    if (style.styleSheet) {
      style.styleSheet.cssText = rule;
    } else {
      style.appendChild(document.createTextNode(rule));
    }
    div.id = mod;

    if (body.fake) {
      //avoid crashing IE8, if background image is used
      body.style.background = '';
      //Safari 5.13/5.1.4 OSX stops loading if ::-webkit-scrollbar is used and scrollbars are visible
      body.style.overflow = 'hidden';
      docOverflow = docElement.style.overflow;
      docElement.style.overflow = 'hidden';
      docElement.appendChild(body);
    }

    ret = callback(div, rule);
    // If this is done after page load we don't want to remove the body so check if body exists
    if (body.fake) {
      body.parentNode.removeChild(body);
      docElement.style.overflow = docOverflow;
      // Trigger layout so kinetic scrolling isn't disabled in iOS6+
      // eslint-disable-next-line
      docElement.offsetHeight;
    } else {
      div.parentNode.removeChild(div);
    }

    return !!ret;

  }

  ;

  /**
   * testStyles injects an element with style element and some CSS rules
   *
   * @memberof Modernizr
   * @name Modernizr.testStyles
   * @optionName Modernizr.testStyles()
   * @optionProp testStyles
   * @access public
   * @function testStyles
   * @param {string} rule - String representing a css rule
   * @param {function} callback - A function that is used to test the injected element
   * @param {number} [nodes] - An integer representing the number of additional nodes you want injected
   * @param {string[]} [testnames] - An array of strings that are used as ids for the additional nodes
   * @returns {boolean}
   * @example
   *
   * `Modernizr.testStyles` takes a CSS rule and injects it onto the current page
   * along with (possibly multiple) DOM elements. This lets you check for features
   * that can not be detected by simply checking the [IDL](https://developer.mozilla.org/en-US/docs/Mozilla/Developer_guide/Interface_development_guide/IDL_interface_rules).
   *
   * ```js
   * Modernizr.testStyles('#modernizr { width: 9px; color: papayawhip; }', function(elem, rule) {
   *   // elem is the first DOM node in the page (by default #modernizr)
   *   // rule is the first argument you supplied - the CSS rule in string form
   *
   *   addTest('widthworks', elem.style.width === '9px')
   * });
   * ```
   *
   * If your test requires multiple nodes, you can include a third argument
   * indicating how many additional div elements to include on the page. The
   * additional nodes are injected as children of the `elem` that is returned as
   * the first argument to the callback.
   *
   * ```js
   * Modernizr.testStyles('#modernizr {width: 1px}; #modernizr2 {width: 2px}', function(elem) {
   *   document.getElementById('modernizr').style.width === '1px'; // true
   *   document.getElementById('modernizr2').style.width === '2px'; // true
   *   elem.firstChild === document.getElementById('modernizr2'); // true
   * }, 1);
   * ```
   *
   * By default, all of the additional elements have an ID of `modernizr[n]`, where
   * `n` is its index (e.g. the first additional, second overall is `#modernizr2`,
   * the second additional is `#modernizr3`, etc.).
   * If you want to have more meaningful IDs for your function, you can provide
   * them as the fourth argument, as an array of strings
   *
   * ```js
   * Modernizr.testStyles('#foo {width: 10px}; #bar {height: 20px}', function(elem) {
   *   elem.firstChild === document.getElementById('foo'); // true
   *   elem.lastChild === document.getElementById('bar'); // true
   * }, 2, ['foo', 'bar']);
   * ```
   *
   */

  var testStyles = ModernizrProto.testStyles = injectElementWithStyles;
  
/*!
{
  "name": "@font-face",
  "property": "fontface",
  "authors": ["Diego Perini", "Mat Marquis"],
  "tags": ["css"],
  "knownBugs": [
    "False Positive: WebOS https://github.com/Modernizr/Modernizr/issues/342",
    "False Postive: WP7 https://github.com/Modernizr/Modernizr/issues/538"
  ],
  "notes": [{
    "name": "@font-face detection routine by Diego Perini",
    "href": "http://javascript.nwbox.com/CSSSupport/"
  },{
    "name": "Filament Group @font-face compatibility research",
    "href": "https://docs.google.com/presentation/d/1n4NyG4uPRjAA8zn_pSQ_Ket0RhcWC6QlZ6LMjKeECo0/edit#slide=id.p"
  },{
    "name": "Filament Grunticon/@font-face device testing results",
    "href": "https://docs.google.com/spreadsheet/ccc?key=0Ag5_yGvxpINRdHFYeUJPNnZMWUZKR2ItMEpRTXZPdUE#gid=0"
  },{
    "name": "CSS fonts on Android",
    "href": "https://stackoverflow.com/questions/3200069/css-fonts-on-android"
  },{
    "name": "@font-face and Android",
    "href": "http://archivist.incutio.com/viewlist/css-discuss/115960"
  }]
}
!*/

  var blacklist = (function() {
    var ua = navigator.userAgent;
    var webos = ua.match(/w(eb)?osbrowser/gi);
    var wppre8 = ua.match(/windows phone/gi) && ua.match(/iemobile\/([0-9])+/gi) && parseFloat(RegExp.$1) >= 9;
    return webos || wppre8;
  }());
  if (blacklist) {
    Modernizr.addTest('fontface', false);
  } else {
    testStyles('@font-face {font-family:"font";src:url("https://")}', function(node, rule) {
      var style = document.getElementById('smodernizr');
      var sheet = style.sheet || style.styleSheet;
      var cssText = sheet ? (sheet.cssRules && sheet.cssRules[0] ? sheet.cssRules[0].cssText : sheet.cssText || '') : '';
      var bool = /src/i.test(cssText) && cssText.indexOf(rule.split(' ')[0]) === 0;
      Modernizr.addTest('fontface', bool);
    });
  }
;
/*!
{
  "name": "CSS Generated Content",
  "property": "generatedcontent",
  "tags": ["css"],
  "warnings": ["Android won't return correct height for anything below 7px #738"],
  "notes": [{
    "name": "W3C CSS Selectors Level 3 spec",
    "href": "https://www.w3.org/TR/css3-selectors/#gen-content"
  },{
    "name": "MDN article on :before",
    "href": "https://developer.mozilla.org/en-US/docs/Web/CSS/::before"
  },{
    "name": "MDN article on :after",
    "href": "https://developer.mozilla.org/en-US/docs/Web/CSS/::before"
  }]
}
!*/

  testStyles('#modernizr{font:0/0 a}#modernizr:after{content:":)";visibility:hidden;font:7px/1 a}', function(node) {
    // See bug report on why this value is 6 crbug.com/608142
    Modernizr.addTest('generatedcontent', node.offsetHeight >= 6);
  });


  /**
   * Create our "modernizr" element that we do most feature tests on.
   *
   * @access private
   */

  var modElem = {
    elem: createElement('modernizr')
  };

  // Clean up this element
  Modernizr._q.push(function() {
    delete modElem.elem;
  });

  

  var mStyle = {
    style: modElem.elem.style
  };

  // kill ref for gc, must happen before mod.elem is removed, so we unshift on to
  // the front of the queue.
  Modernizr._q.unshift(function() {
    delete mStyle.style;
  });

  

  /**
   * domToCSS takes a camelCase string and converts it to kebab-case
   * e.g. boxSizing -> box-sizing
   *
   * @access private
   * @function domToCSS
   * @param {string} name - String name of camelCase prop we want to convert
   * @returns {string} The kebab-case version of the supplied name
   */

  function domToCSS(name) {
    return name.replace(/([A-Z])/g, function(str, m1) {
      return '-' + m1.toLowerCase();
    }).replace(/^ms-/, '-ms-');
  }
  ;


  /**
   * wrapper around getComputedStyle, to fix issues with Firefox returning null when
   * called inside of a hidden iframe
   *
   * @access private
   * @function computedStyle
   * @param {HTMLElement|SVGElement} - The element we want to find the computed styles of
   * @param {string|null} [pseudoSelector]- An optional pseudo element selector (e.g. :before), of null if none
   * @returns {CSSStyleDeclaration}
   */

  function computedStyle(elem, pseudo, prop) {
    var result;

    if ('getComputedStyle' in window) {
      result = getComputedStyle.call(window, elem, pseudo);
      var console = window.console;

      if (result !== null) {
        if (prop) {
          result = result.getPropertyValue(prop);
        }
      } else {
        if (console) {
          var method = console.error ? 'error' : 'log';
          console[method].call(console, 'getComputedStyle returning null, its possible modernizr test results are inaccurate');
        }
      }
    } else {
      result = !pseudo && elem.currentStyle && elem.currentStyle[prop];
    }

    return result;
  }

  ;

  /**
   * nativeTestProps allows for us to use native feature detection functionality if available.
   * some prefixed form, or false, in the case of an unsupported rule
   *
   * @access private
   * @function nativeTestProps
   * @param {array} props - An array of property names
   * @param {string} value - A string representing the value we want to check via @supports
   * @returns {boolean|undefined} A boolean when @supports exists, undefined otherwise
   */

  // Accepts a list of property names and a single value
  // Returns `undefined` if native detection not available
  function nativeTestProps(props, value) {
    var i = props.length;
    // Start with the JS API: http://www.w3.org/TR/css3-conditional/#the-css-interface
    if ('CSS' in window && 'supports' in window.CSS) {
      // Try every prefixed variant of the property
      while (i--) {
        if (window.CSS.supports(domToCSS(props[i]), value)) {
          return true;
        }
      }
      return false;
    }
    // Otherwise fall back to at-rule (for Opera 12.x)
    else if ('CSSSupportsRule' in window) {
      // Build a condition string for every prefixed variant
      var conditionText = [];
      while (i--) {
        conditionText.push('(' + domToCSS(props[i]) + ':' + value + ')');
      }
      conditionText = conditionText.join(' or ');
      return injectElementWithStyles('@supports (' + conditionText + ') { #modernizr { position: absolute; } }', function(node) {
        return computedStyle(node, null, 'position') == 'absolute';
      });
    }
    return undefined;
  }
  ;

  // testProps is a generic CSS / DOM property test.

  // In testing support for a given CSS property, it's legit to test:
  //    `elem.style[styleName] !== undefined`
  // If the property is supported it will return an empty string,
  // if unsupported it will return undefined.

  // We'll take advantage of this quick test and skip setting a style
  // on our modernizr element, but instead just testing undefined vs
  // empty string.

  // Property names can be provided in either camelCase or kebab-case.

  function testProps(props, prefixed, value, skipValueTest) {
    skipValueTest = is(skipValueTest, 'undefined') ? false : skipValueTest;

    // Try native detect first
    if (!is(value, 'undefined')) {
      var result = nativeTestProps(props, value);
      if (!is(result, 'undefined')) {
        return result;
      }
    }

    // Otherwise do it properly
    var afterInit, i, propsLength, prop, before;

    // If we don't have a style element, that means we're running async or after
    // the core tests, so we'll need to create our own elements to use

    // inside of an SVG element, in certain browsers, the `style` element is only
    // defined for valid tags. Therefore, if `modernizr` does not have one, we
    // fall back to a less used element and hope for the best.
    // for strict XHTML browsers the hardly used samp element is used
    var elems = ['modernizr', 'tspan', 'samp'];
    while (!mStyle.style && elems.length) {
      afterInit = true;
      mStyle.modElem = createElement(elems.shift());
      mStyle.style = mStyle.modElem.style;
    }

    // Delete the objects if we created them.
    function cleanElems() {
      if (afterInit) {
        delete mStyle.style;
        delete mStyle.modElem;
      }
    }

    propsLength = props.length;
    for (i = 0; i < propsLength; i++) {
      prop = props[i];
      before = mStyle.style[prop];

      if (contains(prop, '-')) {
        prop = cssToDOM(prop);
      }

      if (mStyle.style[prop] !== undefined) {

        // If value to test has been passed in, do a set-and-check test.
        // 0 (integer) is a valid property value, so check that `value` isn't
        // undefined, rather than just checking it's truthy.
        if (!skipValueTest && !is(value, 'undefined')) {

          // Needs a try catch block because of old IE. This is slow, but will
          // be avoided in most cases because `skipValueTest` will be used.
          try {
            mStyle.style[prop] = value;
          } catch (e) {}

          // If the property value has changed, we assume the value used is
          // supported. If `value` is empty string, it'll fail here (because
          // it hasn't changed), which matches how browsers have implemented
          // CSS.supports()
          if (mStyle.style[prop] != before) {
            cleanElems();
            return prefixed == 'pfx' ? prop : true;
          }
        }
        // Otherwise just return true, or the property name if this is a
        // `prefixed()` call
        else {
          cleanElems();
          return prefixed == 'pfx' ? prop : true;
        }
      }
    }
    cleanElems();
    return false;
  }

  ;

  /**
   * testProp() investigates whether a given style property is recognized
   * Property names can be provided in either camelCase or kebab-case.
   *
   * @memberof Modernizr
   * @name Modernizr.testProp
   * @access public
   * @optionName Modernizr.testProp()
   * @optionProp testProp
   * @function testProp
   * @param {string} prop - Name of the CSS property to check
   * @param {string} [value] - Name of the CSS value to check
   * @param {boolean} [useValue] - Whether or not to check the value if @supports isn't supported
   * @returns {boolean}
   * @example
   *
   * Just like [testAllProps](#modernizr-testallprops), only it does not check any vendor prefixed
   * version of the string.
   *
   * Note that the property name must be provided in camelCase (e.g. boxSizing not box-sizing)
   *
   * ```js
   * Modernizr.testProp('pointerEvents')  // true
   * ```
   *
   * You can also provide a value as an optional second argument to check if a
   * specific value is supported
   *
   * ```js
   * Modernizr.testProp('pointerEvents', 'none') // true
   * Modernizr.testProp('pointerEvents', 'penguin') // false
   * ```
   */

  var testProp = ModernizrProto.testProp = function(prop, value, useValue) {
    return testProps([prop], undefined, value, useValue);
  };
  
/*!
{
  "name": "CSS textshadow",
  "property": "textshadow",
  "caniuse": "css-textshadow",
  "tags": ["css"],
  "knownBugs": ["FF3.0 will false positive on this test"]
}
!*/

  Modernizr.addTest('textshadow', testProp('textShadow', '1px 1px'));


  /**
   * fnBind is a super small [bind](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/bind) polyfill.
   *
   * @access private
   * @function fnBind
   * @param {function} fn - a function you want to change `this` reference to
   * @param {object} that - the `this` you want to call the function with
   * @returns {function} The wrapped version of the supplied function
   */

  function fnBind(fn, that) {
    return function() {
      return fn.apply(that, arguments);
    };
  }

  ;

  /**
   * testDOMProps is a generic DOM property test; if a browser supports
   *   a certain property, it won't return undefined for it.
   *
   * @access private
   * @function testDOMProps
   * @param {array.<string>} props - An array of properties to test for
   * @param {object} obj - An object or Element you want to use to test the parameters again
   * @param {boolean|object} elem - An Element to bind the property lookup again. Use `false` to prevent the check
   * @returns {false|*} returns false if the prop is unsupported, otherwise the value that is supported
   */
  function testDOMProps(props, obj, elem) {
    var item;

    for (var i in props) {
      if (props[i] in obj) {

        // return the property name as a string
        if (elem === false) {
          return props[i];
        }

        item = obj[props[i]];

        // let's bind a function
        if (is(item, 'function')) {
          // bind to obj unless overriden
          return fnBind(item, elem || obj);
        }

        // return the unbound function or obj or value
        return item;
      }
    }
    return false;
  }

  ;

  /**
   * testPropsAll tests a list of DOM properties we want to check against.
   * We specify literally ALL possible (known and/or likely) properties on
   * the element including the non-vendor prefixed one, for forward-
   * compatibility.
   *
   * @access private
   * @function testPropsAll
   * @param {string} prop - A string of the property to test for
   * @param {string|object} [prefixed] - An object to check the prefixed properties on. Use a string to skip
   * @param {HTMLElement|SVGElement} [elem] - An element used to test the property and value against
   * @param {string} [value] - A string of a css value
   * @param {boolean} [skipValueTest] - An boolean representing if you want to test if value sticks when set
   * @returns {false|string} returns the string version of the property, or false if it is unsupported
   */
  function testPropsAll(prop, prefixed, elem, value, skipValueTest) {

    var ucProp = prop.charAt(0).toUpperCase() + prop.slice(1),
      props = (prop + ' ' + cssomPrefixes.join(ucProp + ' ') + ucProp).split(' ');

    // did they call .prefixed('boxSizing') or are we just testing a prop?
    if (is(prefixed, 'string') || is(prefixed, 'undefined')) {
      return testProps(props, prefixed, value, skipValueTest);

      // otherwise, they called .prefixed('requestAnimationFrame', window[, elem])
    } else {
      props = (prop + ' ' + (domPrefixes).join(ucProp + ' ') + ucProp).split(' ');
      return testDOMProps(props, prefixed, elem);
    }
  }

  // Modernizr.testAllProps() investigates whether a given style property,
  // or any of its vendor-prefixed variants, is recognized
  //
  // Note that the property names must be provided in the camelCase variant.
  // Modernizr.testAllProps('boxSizing')
  ModernizrProto.testAllProps = testPropsAll;

  

  /**
   * testAllProps determines whether a given CSS property is supported in the browser
   *
   * @memberof Modernizr
   * @name Modernizr.testAllProps
   * @optionName Modernizr.testAllProps()
   * @optionProp testAllProps
   * @access public
   * @function testAllProps
   * @param {string} prop - String naming the property to test (either camelCase or kebab-case)
   * @param {string} [value] - String of the value to test
   * @param {boolean} [skipValueTest=false] - Whether to skip testing that the value is supported when using non-native detection
   * @example
   *
   * testAllProps determines whether a given CSS property, in some prefixed form,
   * is supported by the browser.
   *
   * ```js
   * testAllProps('boxSizing')  // true
   * ```
   *
   * It can optionally be given a CSS value in string form to test if a property
   * value is valid
   *
   * ```js
   * testAllProps('display', 'block') // true
   * testAllProps('display', 'penguin') // false
   * ```
   *
   * A boolean can be passed as a third parameter to skip the value check when
   * native detection (@supports) isn't available.
   *
   * ```js
   * testAllProps('shapeOutside', 'content-box', true);
   * ```
   */

  function testAllProps(prop, value, skipValueTest) {
    return testPropsAll(prop, undefined, undefined, value, skipValueTest);
  }
  ModernizrProto.testAllProps = testAllProps;
  
/*!
{
  "name": "Background Size",
  "property": "backgroundsize",
  "tags": ["css"],
  "knownBugs": ["This will false positive in Opera Mini - https://github.com/Modernizr/Modernizr/issues/396"],
  "notes": [{
    "name": "Related Issue",
    "href": "https://github.com/Modernizr/Modernizr/issues/396"
  }]
}
!*/

  Modernizr.addTest('backgroundsize', testAllProps('backgroundSize', '100%', true));

/*!
{
  "name": "Flexbox (legacy)",
  "property": "flexboxlegacy",
  "tags": ["css"],
  "polyfills": ["flexie"],
  "notes": [{
    "name": "The _old_ flexbox",
    "href": "https://www.w3.org/TR/2009/WD-css3-flexbox-20090723/"
  }]
}
!*/

  Modernizr.addTest('flexboxlegacy', testAllProps('boxDirection', 'reverse', true));

/*!
{
  "name": "Box Shadow",
  "property": "boxshadow",
  "caniuse": "css-boxshadow",
  "tags": ["css"],
  "knownBugs": [
    "WebOS false positives on this test.",
    "The Kindle Silk browser false positives"
  ]
}
!*/

  Modernizr.addTest('boxshadow', testAllProps('boxShadow', '1px 1px', true));

/*!
{
  "name": "CSS Transforms",
  "property": "csstransforms",
  "caniuse": "transforms2d",
  "tags": ["css"]
}
!*/

  Modernizr.addTest('csstransforms', function() {
    // Android < 3.0 is buggy, so we sniff and blacklist
    // http://git.io/hHzL7w
    return navigator.userAgent.indexOf('Android 2.') === -1 &&
           testAllProps('transform', 'scale(1)', true);
  });

/*!
{
  "name": "CSS Columns",
  "property": "csscolumns",
  "caniuse": "multicolumn",
  "polyfills": ["css3multicolumnjs"],
  "tags": ["css"]
}
!*/


  (function() {

    Modernizr.addTest('csscolumns', function() {
      var bool = false;
      var test = testAllProps('columnCount');
      try {
        bool = !!test
        if (bool) {
          bool = new Boolean(bool);
        }
      } catch (e) {}

      return bool;
    });

    var props = ['Width', 'Span', 'Fill', 'Gap', 'Rule', 'RuleColor', 'RuleStyle', 'RuleWidth', 'BreakBefore', 'BreakAfter', 'BreakInside'];
    var name, test;

    for (var i = 0; i < props.length; i++) {
      name = props[i].toLowerCase();
      test = testAllProps('column' + props[i]);

      // break-before, break-after & break-inside are not "column"-prefixed in spec
      if (name === 'breakbefore' || name === 'breakafter' || name == 'breakinside') {
        test = test || testAllProps(props[i]);
      }

      Modernizr.addTest('csscolumns.' + name, test);
    }


  })();


/*!
{
  "name": "CSS Transitions",
  "property": "csstransitions",
  "caniuse": "css-transitions",
  "tags": ["css"]
}
!*/

  Modernizr.addTest('csstransitions', testAllProps('transition', 'all', true));

/*!
{
  "name": "CSS Animations",
  "property": "cssanimations",
  "caniuse": "css-animation",
  "polyfills": ["transformie", "csssandpaper"],
  "tags": ["css"],
  "warnings": ["Android < 4 will pass this test, but can only animate a single property at a time"],
  "notes": [{
    "name" : "Article: 'Dispelling the Android CSS animation myths'",
    "href": "https://goo.gl/OGw5Gm"
  }]
}
!*/
/* DOC
Detects whether or not elements can be animated using CSS
*/

  Modernizr.addTest('cssanimations', testAllProps('animationName', 'a', true));

/*!
{
  "name": "Flexbox",
  "property": "flexbox",
  "caniuse": "flexbox",
  "tags": ["css"],
  "notes": [{
    "name": "The _new_ flexbox",
    "href": "http://dev.w3.org/csswg/css3-flexbox"
  }],
  "warnings": [
    "A `true` result for this detect does not imply that the `flex-wrap` property is supported; see the `flexwrap` detect."
  ]
}
!*/
/* DOC
Detects support for the Flexible Box Layout model, a.k.a. Flexbox, which allows easy manipulation of layout order and sizing within a container.
*/

  Modernizr.addTest('flexbox', testAllProps('flexBasis', '1px', true));

/*!
{
  "name": "CSS Reflections",
  "caniuse": "css-reflections",
  "property": "cssreflections",
  "tags": ["css"]
}
!*/

  Modernizr.addTest('cssreflections', testAllProps('boxReflect', 'above', true));

/*!
{
  "name": "CSS Transforms 3D",
  "property": "csstransforms3d",
  "caniuse": "transforms3d",
  "tags": ["css"],
  "warnings": [
    "Chrome may occassionally fail this test on some systems; more info: https://code.google.com/p/chromium/issues/detail?id=129004"
  ]
}
!*/

  Modernizr.addTest('csstransforms3d', function() {
    return !!testAllProps('perspective', '1px', true);
  });

/*!
{
  "name": "Border Radius",
  "property": "borderradius",
  "caniuse": "border-radius",
  "polyfills": ["css3pie"],
  "tags": ["css"],
  "notes": [{
    "name": "Comprehensive Compat Chart",
    "href": "https://muddledramblings.com/table-of-css3-border-radius-compliance"
  }]
}
!*/

  Modernizr.addTest('borderradius', testAllProps('borderRadius', '0px', true));

/*!
{
  "name": "Border Image",
  "property": "borderimage",
  "caniuse": "border-image",
  "polyfills": ["css3pie"],
   "knownBugs": ["Android < 2.0 is true, but has a broken implementation"],
  "tags": ["css"]
}
!*/

  Modernizr.addTest('borderimage', testAllProps('borderImage', 'url() 1', true));


  /**
   * prefixed returns the prefixed or nonprefixed property name variant of your input
   *
   * @memberof Modernizr
   * @name Modernizr.prefixed
   * @optionName Modernizr.prefixed()
   * @optionProp prefixed
   * @access public
   * @function prefixed
   * @param {string} prop - String name of the property to test for
   * @param {object} [obj] - An object to test for the prefixed properties on
   * @param {HTMLElement} [elem] - An element used to test specific properties against
   * @returns {string|false} The string representing the (possibly prefixed) valid
   * version of the property, or `false` when it is unsupported.
   * @example
   *
   * Modernizr.prefixed takes a string css value in the DOM style camelCase (as
   * opposed to the css style kebab-case) form and returns the (possibly prefixed)
   * version of that property that the browser actually supports.
   *
   * For example, in older Firefox...
   * ```js
   * prefixed('boxSizing')
   * ```
   * returns 'MozBoxSizing'
   *
   * In newer Firefox, as well as any other browser that support the unprefixed
   * version would simply return `boxSizing`. Any browser that does not support
   * the property at all, it will return `false`.
   *
   * By default, prefixed is checked against a DOM element. If you want to check
   * for a property on another object, just pass it as a second argument
   *
   * ```js
   * var rAF = prefixed('requestAnimationFrame', window);
   *
   * raf(function() {
   *  renderFunction();
   * })
   * ```
   *
   * Note that this will return _the actual function_ - not the name of the function.
   * If you need the actual name of the property, pass in `false` as a third argument
   *
   * ```js
   * var rAFProp = prefixed('requestAnimationFrame', window, false);
   *
   * rafProp === 'WebkitRequestAnimationFrame' // in older webkit
   * ```
   *
   * One common use case for prefixed is if you're trying to determine which transition
   * end event to bind to, you might do something like...
   * ```js
   * var transEndEventNames = {
   *     'WebkitTransition' : 'webkitTransitionEnd', * Saf 6, Android Browser
   *     'MozTransition'    : 'transitionend',       * only for FF < 15
   *     'transition'       : 'transitionend'        * IE10, Opera, Chrome, FF 15+, Saf 7+
   * };
   *
   * var transEndEventName = transEndEventNames[ Modernizr.prefixed('transition') ];
   * ```
   *
   * If you want a similar lookup, but in kebab-case, you can use [prefixedCSS](#modernizr-prefixedcss).
   */

  var prefixed = ModernizrProto.prefixed = function(prop, obj, elem) {
    if (prop.indexOf('@') === 0) {
      return atRule(prop);
    }

    if (prop.indexOf('-') != -1) {
      // Convert kebab-case to camelCase
      prop = cssToDOM(prop);
    }
    if (!obj) {
      return testPropsAll(prop, 'pfx');
    } else {
      // Testing DOM property e.g. Modernizr.prefixed('requestAnimationFrame', window) // 'mozRequestAnimationFrame'
      return testPropsAll(prop, obj, elem);
    }
  };

  
/*!
{
  "name": "IndexedDB",
  "property": "indexeddb",
  "caniuse": "indexeddb",
  "tags": ["storage"],
  "polyfills": ["indexeddb"],
  "async": true
}
!*/
/* DOC
Detects support for the IndexedDB client-side storage API (final spec).
*/

  // Vendors had inconsistent prefixing with the experimental Indexed DB:
  // - Webkit's implementation is accessible through webkitIndexedDB
  // - Firefox shipped moz_indexedDB before FF4b9, but since then has been mozIndexedDB
  // For speed, we don't test the legacy (and beta-only) indexedDB

  Modernizr.addAsyncTest(function() {

    var indexeddb;

    try {
      // Firefox throws a Security Error when cookies are disabled
      indexeddb = prefixed('indexedDB', window);
    } catch (e) {
    }

    if (!!indexeddb) {
      var testDBName = 'modernizr-' + Math.random();
      var req = indexeddb.open(testDBName);

      req.onerror = function() {
        if (req.error && req.error.name === 'InvalidStateError') {
          addTest('indexeddb', false);
        } else {
          addTest('indexeddb', true);
          detectDeleteDatabase(indexeddb, testDBName);
        }
      };

      req.onsuccess = function() {
        addTest('indexeddb', true);
        detectDeleteDatabase(indexeddb, testDBName);
      };
    } else {
      addTest('indexeddb', false);
    }
  });

  function detectDeleteDatabase(indexeddb, testDBName) {
    var deleteReq = indexeddb.deleteDatabase(testDBName);
    deleteReq.onsuccess = function() {
      addTest('indexeddb.deletedatabase', true);
    };
    deleteReq.onerror = function() {
      addTest('indexeddb.deletedatabase', false);
    };
  }

;

  // Run each test
  testRunner();

  // Remove the "no-js" class if it exists
  setClasses(classes);

  delete ModernizrProto.addTest;
  delete ModernizrProto.addAsyncTest;

  // Run the things that are supposed to run after the tests
  for (var i = 0; i < Modernizr._q.length; i++) {
    Modernizr._q[i]();
  }

  // Leak Modernizr namespace
  window.Modernizr = Modernizr;


;

})(window, document);
/*! URI.js v1.19.11 http://medialize.github.io/URI.js/ */
/* build contains: IPv6.js, punycode.js, SecondLevelDomains.js, URI.js, URITemplate.js, jquery.URI.js */
/*
 URI.js - Mutating URLs
 IPv6 Support

 Version: 1.19.11

 Author: Rodney Rehm
 Web: http://medialize.github.io/URI.js/

 Licensed under
   MIT License http://www.opensource.org/licenses/mit-license

 https://mths.be/punycode v1.4.0 by @mathias  URI.js - Mutating URLs
 Second Level Domain (SLD) Support

 Version: 1.19.11

 Author: Rodney Rehm
 Web: http://medialize.github.io/URI.js/

 Licensed under
   MIT License http://www.opensource.org/licenses/mit-license

 URI.js - Mutating URLs

 Version: 1.19.11

 Author: Rodney Rehm
 Web: http://medialize.github.io/URI.js/

 Licensed under
   MIT License http://www.opensource.org/licenses/mit-license

 URI.js - Mutating URLs
 URI Template Support - http://tools.ietf.org/html/rfc6570

 Version: 1.19.11

 Author: Rodney Rehm
 Web: http://medialize.github.io/URI.js/

 Licensed under
   MIT License http://www.opensource.org/licenses/mit-license

 URI.js - Mutating URLs
 jQuery Plugin

 Version: 1.19.11

 Author: Rodney Rehm
 Web: http://medialize.github.io/URI.js/jquery-uri-plugin.html

 Licensed under
   MIT License http://www.opensource.org/licenses/mit-license

*/
(function(root,factory){if(typeof module==="object"&&module.exports)module.exports=factory();else if(typeof define==="function"&&define.amd)define(factory);else root.IPv6=factory(root)})(this,function(root){var _IPv6=root&&root.IPv6;function bestPresentation(address){var _address=address.toLowerCase();var segments=_address.split(":");var length=segments.length;var total=8;if(segments[0]===""&&segments[1]===""&&segments[2]===""){segments.shift();segments.shift()}else if(segments[0]===""&&segments[1]===
"")segments.shift();else if(segments[length-1]===""&&segments[length-2]==="")segments.pop();length=segments.length;if(segments[length-1].indexOf(".")!==-1)total=7;var pos;for(pos=0;pos<length;pos++)if(segments[pos]==="")break;if(pos<total){segments.splice(pos,1,"0000");while(segments.length<total)segments.splice(pos,0,"0000")}var _segments;for(var i=0;i<total;i++){_segments=segments[i].split("");for(var j=0;j<3;j++)if(_segments[0]==="0"&&_segments.length>1)_segments.splice(0,1);else break;segments[i]=
_segments.join("")}var best=-1;var _best=0;var _current=0;var current=-1;var inzeroes=false;for(i=0;i<total;i++)if(inzeroes)if(segments[i]==="0")_current+=1;else{inzeroes=false;if(_current>_best){best=current;_best=_current}}else if(segments[i]==="0"){inzeroes=true;current=i;_current=1}if(_current>_best){best=current;_best=_current}if(_best>1)segments.splice(best,_best,"");length=segments.length;var result="";if(segments[0]==="")result=":";for(i=0;i<length;i++){result+=segments[i];if(i===length-1)break;
result+=":"}if(segments[length-1]==="")result+=":";return result}function noConflict(){if(root.IPv6===this)root.IPv6=_IPv6;return this}return{best:bestPresentation,noConflict:noConflict}});
(function(root){var freeExports=typeof exports=="object"&&exports&&!exports.nodeType&&exports;var freeModule=typeof module=="object"&&module&&!module.nodeType&&module;var freeGlobal=typeof global=="object"&&global;if(freeGlobal.global===freeGlobal||freeGlobal.window===freeGlobal||freeGlobal.self===freeGlobal)root=freeGlobal;var punycode,maxInt=2147483647,base=36,tMin=1,tMax=26,skew=38,damp=700,initialBias=72,initialN=128,delimiter="-",regexPunycode=/^xn--/,regexNonASCII=/[^\x20-\x7E]/,regexSeparators=
/[\x2E\u3002\uFF0E\uFF61]/g,errors={"overflow":"Overflow: input needs wider integers to process","not-basic":"Illegal input \x3e\x3d 0x80 (not a basic code point)","invalid-input":"Invalid input"},baseMinusTMin=base-tMin,floor=Math.floor,stringFromCharCode=String.fromCharCode,key;function error(type){throw new RangeError(errors[type]);}function map(array,fn){var length=array.length;var result=[];while(length--)result[length]=fn(array[length]);return result}function mapDomain(string,fn){var parts=
string.split("@");var result="";if(parts.length>1){result=parts[0]+"@";string=parts[1]}string=string.replace(regexSeparators,".");var labels=string.split(".");var encoded=map(labels,fn).join(".");return result+encoded}function ucs2decode(string){var output=[],counter=0,length=string.length,value,extra;while(counter<length){value=string.charCodeAt(counter++);if(value>=55296&&value<=56319&&counter<length){extra=string.charCodeAt(counter++);if((extra&64512)==56320)output.push(((value&1023)<<10)+(extra&
1023)+65536);else{output.push(value);counter--}}else output.push(value)}return output}function ucs2encode(array){return map(array,function(value){var output="";if(value>65535){value-=65536;output+=stringFromCharCode(value>>>10&1023|55296);value=56320|value&1023}output+=stringFromCharCode(value);return output}).join("")}function basicToDigit(codePoint){if(codePoint-48<10)return codePoint-22;if(codePoint-65<26)return codePoint-65;if(codePoint-97<26)return codePoint-97;return base}function digitToBasic(digit,
flag){return digit+22+75*(digit<26)-((flag!=0)<<5)}function adapt(delta,numPoints,firstTime){var k=0;delta=firstTime?floor(delta/damp):delta>>1;delta+=floor(delta/numPoints);for(;delta>baseMinusTMin*tMax>>1;k+=base)delta=floor(delta/baseMinusTMin);return floor(k+(baseMinusTMin+1)*delta/(delta+skew))}function decode(input){var output=[],inputLength=input.length,out,i=0,n=initialN,bias=initialBias,basic,j,index,oldi,w,k,digit,t,baseMinusT;basic=input.lastIndexOf(delimiter);if(basic<0)basic=0;for(j=
0;j<basic;++j){if(input.charCodeAt(j)>=128)error("not-basic");output.push(input.charCodeAt(j))}for(index=basic>0?basic+1:0;index<inputLength;){for(oldi=i,w=1,k=base;;k+=base){if(index>=inputLength)error("invalid-input");digit=basicToDigit(input.charCodeAt(index++));if(digit>=base||digit>floor((maxInt-i)/w))error("overflow");i+=digit*w;t=k<=bias?tMin:k>=bias+tMax?tMax:k-bias;if(digit<t)break;baseMinusT=base-t;if(w>floor(maxInt/baseMinusT))error("overflow");w*=baseMinusT}out=output.length+1;bias=adapt(i-
oldi,out,oldi==0);if(floor(i/out)>maxInt-n)error("overflow");n+=floor(i/out);i%=out;output.splice(i++,0,n)}return ucs2encode(output)}function encode(input){var n,delta,handledCPCount,basicLength,bias,j,m,q,k,t,currentValue,output=[],inputLength,handledCPCountPlusOne,baseMinusT,qMinusT;input=ucs2decode(input);inputLength=input.length;n=initialN;delta=0;bias=initialBias;for(j=0;j<inputLength;++j){currentValue=input[j];if(currentValue<128)output.push(stringFromCharCode(currentValue))}handledCPCount=
basicLength=output.length;if(basicLength)output.push(delimiter);while(handledCPCount<inputLength){for(m=maxInt,j=0;j<inputLength;++j){currentValue=input[j];if(currentValue>=n&&currentValue<m)m=currentValue}handledCPCountPlusOne=handledCPCount+1;if(m-n>floor((maxInt-delta)/handledCPCountPlusOne))error("overflow");delta+=(m-n)*handledCPCountPlusOne;n=m;for(j=0;j<inputLength;++j){currentValue=input[j];if(currentValue<n&&++delta>maxInt)error("overflow");if(currentValue==n){for(q=delta,k=base;;k+=base){t=
k<=bias?tMin:k>=bias+tMax?tMax:k-bias;if(q<t)break;qMinusT=q-t;baseMinusT=base-t;output.push(stringFromCharCode(digitToBasic(t+qMinusT%baseMinusT,0)));q=floor(qMinusT/baseMinusT)}output.push(stringFromCharCode(digitToBasic(q,0)));bias=adapt(delta,handledCPCountPlusOne,handledCPCount==basicLength);delta=0;++handledCPCount}}++delta;++n}return output.join("")}function toUnicode(input){return mapDomain(input,function(string){return regexPunycode.test(string)?decode(string.slice(4).toLowerCase()):string})}
function toASCII(input){return mapDomain(input,function(string){return regexNonASCII.test(string)?"xn--"+encode(string):string})}punycode={"version":"1.3.2","ucs2":{"decode":ucs2decode,"encode":ucs2encode},"decode":decode,"encode":encode,"toASCII":toASCII,"toUnicode":toUnicode};if(typeof define=="function"&&typeof define.amd=="object"&&define.amd)define("punycode",function(){return punycode});else if(freeExports&&freeModule)if(module.exports==freeExports)freeModule.exports=punycode;else for(key in punycode)punycode.hasOwnProperty(key)&&
(freeExports[key]=punycode[key]);else root.punycode=punycode})(this);
(function(root,factory){if(typeof module==="object"&&module.exports)module.exports=factory();else if(typeof define==="function"&&define.amd)define(factory);else root.SecondLevelDomains=factory(root)})(this,function(root){var _SecondLevelDomains=root&&root.SecondLevelDomains;var SLD={list:{"ac":" com gov mil net org ","ae":" ac co gov mil name net org pro sch ","af":" com edu gov net org ","al":" com edu gov mil net org ","ao":" co ed gv it og pb ","ar":" com edu gob gov int mil net org tur ","at":" ac co gv or ",
"au":" asn com csiro edu gov id net org ","ba":" co com edu gov mil net org rs unbi unmo unsa untz unze ","bb":" biz co com edu gov info net org store tv ","bh":" biz cc com edu gov info net org ","bn":" com edu gov net org ","bo":" com edu gob gov int mil net org tv ","br":" adm adv agr am arq art ato b bio blog bmd cim cng cnt com coop ecn edu eng esp etc eti far flog fm fnd fot fst g12 ggf gov imb ind inf jor jus lel mat med mil mus net nom not ntr odo org ppg pro psc psi qsl rec slg srv tmp trd tur tv vet vlog wiki zlg ",
"bs":" com edu gov net org ","bz":" du et om ov rg ","ca":" ab bc mb nb nf nl ns nt nu on pe qc sk yk ","ck":" biz co edu gen gov info net org ","cn":" ac ah bj com cq edu fj gd gov gs gx gz ha hb he hi hl hn jl js jx ln mil net nm nx org qh sc sd sh sn sx tj tw xj xz yn zj ","co":" com edu gov mil net nom org ","cr":" ac c co ed fi go or sa ","cy":" ac biz com ekloges gov ltd name net org parliament press pro tm ","do":" art com edu gob gov mil net org sld web ","dz":" art asso com edu gov net org pol ",
"ec":" com edu fin gov info med mil net org pro ","eg":" com edu eun gov mil name net org sci ","er":" com edu gov ind mil net org rochest w ","es":" com edu gob nom org ","et":" biz com edu gov info name net org ","fj":" ac biz com info mil name net org pro ","fk":" ac co gov net nom org ","fr":" asso com f gouv nom prd presse tm ","gg":" co net org ","gh":" com edu gov mil org ","gn":" ac com gov net org ","gr":" com edu gov mil net org ","gt":" com edu gob ind mil net org ","gu":" com edu gov net org ",
"hk":" com edu gov idv net org ","hu":" 2000 agrar bolt casino city co erotica erotika film forum games hotel info ingatlan jogasz konyvelo lakas media news org priv reklam sex shop sport suli szex tm tozsde utazas video ","id":" ac co go mil net or sch web ","il":" ac co gov idf k12 muni net org ","in":" ac co edu ernet firm gen gov i ind mil net nic org res ","iq":" com edu gov i mil net org ","ir":" ac co dnssec gov i id net org sch ","it":" edu gov ","je":" co net org ","jo":" com edu gov mil name net org sch ",
"jp":" ac ad co ed go gr lg ne or ","ke":" ac co go info me mobi ne or sc ","kh":" com edu gov mil net org per ","ki":" biz com de edu gov info mob net org tel ","km":" asso com coop edu gouv k medecin mil nom notaires pharmaciens presse tm veterinaire ","kn":" edu gov net org ","kr":" ac busan chungbuk chungnam co daegu daejeon es gangwon go gwangju gyeongbuk gyeonggi gyeongnam hs incheon jeju jeonbuk jeonnam k kg mil ms ne or pe re sc seoul ulsan ","kw":" com edu gov net org ","ky":" com edu gov net org ",
"kz":" com edu gov mil net org ","lb":" com edu gov net org ","lk":" assn com edu gov grp hotel int ltd net ngo org sch soc web ","lr":" com edu gov net org ","lv":" asn com conf edu gov id mil net org ","ly":" com edu gov id med net org plc sch ","ma":" ac co gov m net org press ","mc":" asso tm ","me":" ac co edu gov its net org priv ","mg":" com edu gov mil nom org prd tm ","mk":" com edu gov inf name net org pro ","ml":" com edu gov net org presse ","mn":" edu gov org ","mo":" com edu gov net org ",
"mt":" com edu gov net org ","mv":" aero biz com coop edu gov info int mil museum name net org pro ","mw":" ac co com coop edu gov int museum net org ","mx":" com edu gob net org ","my":" com edu gov mil name net org sch ","nf":" arts com firm info net other per rec store web ","ng":" biz com edu gov mil mobi name net org sch ","ni":" ac co com edu gob mil net nom org ","np":" com edu gov mil net org ","nr":" biz com edu gov info net org ","om":" ac biz co com edu gov med mil museum net org pro sch ",
"pe":" com edu gob mil net nom org sld ","ph":" com edu gov i mil net ngo org ","pk":" biz com edu fam gob gok gon gop gos gov net org web ","pl":" art bialystok biz com edu gda gdansk gorzow gov info katowice krakow lodz lublin mil net ngo olsztyn org poznan pwr radom slupsk szczecin torun warszawa waw wroc wroclaw zgora ","pr":" ac biz com edu est gov info isla name net org pro prof ","ps":" com edu gov net org plo sec ","pw":" belau co ed go ne or ","ro":" arts com firm info nom nt org rec store tm www ",
"rs":" ac co edu gov in org ","sb":" com edu gov net org ","sc":" com edu gov net org ","sh":" co com edu gov net nom org ","sl":" com edu gov net org ","st":" co com consulado edu embaixada gov mil net org principe saotome store ","sv":" com edu gob org red ","sz":" ac co org ","tr":" av bbs bel biz com dr edu gen gov info k12 name net org pol tel tsk tv web ","tt":" aero biz cat co com coop edu gov info int jobs mil mobi museum name net org pro tel travel ","tw":" club com ebiz edu game gov idv mil net org ",
"mu":" ac co com gov net or org ","mz":" ac co edu gov org ","na":" co com ","nz":" ac co cri geek gen govt health iwi maori mil net org parliament school ","pa":" abo ac com edu gob ing med net nom org sld ","pt":" com edu gov int net nome org publ ","py":" com edu gov mil net org ","qa":" com edu gov mil net org ","re":" asso com nom ","ru":" ac adygeya altai amur arkhangelsk astrakhan bashkiria belgorod bir bryansk buryatia cbg chel chelyabinsk chita chukotka chuvashia com dagestan e-burg edu gov grozny int irkutsk ivanovo izhevsk jar joshkar-ola kalmykia kaluga kamchatka karelia kazan kchr kemerovo khabarovsk khakassia khv kirov koenig komi kostroma kranoyarsk kuban kurgan kursk lipetsk magadan mari mari-el marine mil mordovia mosreg msk murmansk nalchik net nnov nov novosibirsk nsk omsk orenburg org oryol penza perm pp pskov ptz rnd ryazan sakhalin samara saratov simbirsk smolensk spb stavropol stv surgut tambov tatarstan tom tomsk tsaritsyn tsk tula tuva tver tyumen udm udmurtia ulan-ude vladikavkaz vladimir vladivostok volgograd vologda voronezh vrn vyatka yakutia yamal yekaterinburg yuzhno-sakhalinsk ",
"rw":" ac co com edu gouv gov int mil net ","sa":" com edu gov med net org pub sch ","sd":" com edu gov info med net org tv ","se":" a ac b bd c d e f g h i k l m n o org p parti pp press r s t tm u w x y z ","sg":" com edu gov idn net org per ","sn":" art com edu gouv org perso univ ","sy":" com edu gov mil net news org ","th":" ac co go in mi net or ","tj":" ac biz co com edu go gov info int mil name net nic org test web ","tn":" agrinet com defense edunet ens fin gov ind info intl mincom nat net org perso rnrt rns rnu tourism ",
"tz":" ac co go ne or ","ua":" biz cherkassy chernigov chernovtsy ck cn co com crimea cv dn dnepropetrovsk donetsk dp edu gov if in ivano-frankivsk kh kharkov kherson khmelnitskiy kiev kirovograd km kr ks kv lg lugansk lutsk lviv me mk net nikolaev od odessa org pl poltava pp rovno rv sebastopol sumy te ternopil uzhgorod vinnica vn zaporizhzhe zhitomir zp zt ","ug":" ac co go ne or org sc ","uk":" ac bl british-library co cym gov govt icnet jet lea ltd me mil mod national-library-scotland nel net nhs nic nls org orgn parliament plc police sch scot soc ",
"us":" dni fed isa kids nsn ","uy":" com edu gub mil net org ","ve":" co com edu gob info mil net org web ","vi":" co com k12 net org ","vn":" ac biz com edu gov health info int name net org pro ","ye":" co com gov ltd me net org plc ","yu":" ac co edu gov org ","za":" ac agric alt bourse city co cybernet db edu gov grondar iaccess imt inca landesign law mil net ngo nis nom olivetti org pix school tm web ","zm":" ac co com edu gov net org sch ","com":"ar br cn de eu gb gr hu jpn kr no qc ru sa se uk us uy za ",
"net":"gb jp se uk ","org":"ae","de":"com "},has:function(domain){var tldOffset=domain.lastIndexOf(".");if(tldOffset<=0||tldOffset>=domain.length-1)return false;var sldOffset=domain.lastIndexOf(".",tldOffset-1);if(sldOffset<=0||sldOffset>=tldOffset-1)return false;var sldList=SLD.list[domain.slice(tldOffset+1)];if(!sldList)return false;return sldList.indexOf(" "+domain.slice(sldOffset+1,tldOffset)+" ")>=0},is:function(domain){var tldOffset=domain.lastIndexOf(".");if(tldOffset<=0||tldOffset>=domain.length-
1)return false;var sldOffset=domain.lastIndexOf(".",tldOffset-1);if(sldOffset>=0)return false;var sldList=SLD.list[domain.slice(tldOffset+1)];if(!sldList)return false;return sldList.indexOf(" "+domain.slice(0,tldOffset)+" ")>=0},get:function(domain){var tldOffset=domain.lastIndexOf(".");if(tldOffset<=0||tldOffset>=domain.length-1)return null;var sldOffset=domain.lastIndexOf(".",tldOffset-1);if(sldOffset<=0||sldOffset>=tldOffset-1)return null;var sldList=SLD.list[domain.slice(tldOffset+1)];if(!sldList)return null;
if(sldList.indexOf(" "+domain.slice(sldOffset+1,tldOffset)+" ")<0)return null;return domain.slice(sldOffset+1)},noConflict:function(){if(root.SecondLevelDomains===this)root.SecondLevelDomains=_SecondLevelDomains;return this}};return SLD});
(function(root,factory){if(typeof module==="object"&&module.exports)module.exports=factory(require("./punycode"),require("./IPv6"),require("./SecondLevelDomains"));else if(typeof define==="function"&&define.amd)define(["./punycode","./IPv6","./SecondLevelDomains"],factory);else root.URI=factory(root.punycode,root.IPv6,root.SecondLevelDomains,root)})(this,function(punycode,IPv6,SLD,root){var _URI=root&&root.URI;function URI(url,base){var _urlSupplied=arguments.length>=1;var _baseSupplied=arguments.length>=
2;if(!(this instanceof URI)){if(_urlSupplied){if(_baseSupplied)return new URI(url,base);return new URI(url)}return new URI}if(url===undefined){if(_urlSupplied)throw new TypeError("undefined is not a valid argument for URI");if(typeof location!=="undefined")url=location.href+"";else url=""}if(url===null)if(_urlSupplied)throw new TypeError("null is not a valid argument for URI");this.href(url);if(base!==undefined)return this.absoluteTo(base);return this}function isInteger(value){return/^[0-9]+$/.test(value)}
URI.version="1.19.11";var p=URI.prototype;var hasOwn=Object.prototype.hasOwnProperty;function escapeRegEx(string){return string.replace(/([.*+?^=!:${}()|[\]\/\\])/g,"\\$1")}function getType(value){if(value===undefined)return"Undefined";return String(Object.prototype.toString.call(value)).slice(8,-1)}function isArray(obj){return getType(obj)==="Array"}function filterArrayValues(data,value){var lookup={};var i,length;if(getType(value)==="RegExp")lookup=null;else if(isArray(value))for(i=0,length=value.length;i<
length;i++)lookup[value[i]]=true;else lookup[value]=true;for(i=0,length=data.length;i<length;i++){var _match=lookup&&lookup[data[i]]!==undefined||!lookup&&value.test(data[i]);if(_match){data.splice(i,1);length--;i--}}return data}function arrayContains(list,value){var i,length;if(isArray(value)){for(i=0,length=value.length;i<length;i++)if(!arrayContains(list,value[i]))return false;return true}var _type=getType(value);for(i=0,length=list.length;i<length;i++)if(_type==="RegExp"){if(typeof list[i]===
"string"&&list[i].match(value))return true}else if(list[i]===value)return true;return false}function arraysEqual(one,two){if(!isArray(one)||!isArray(two))return false;if(one.length!==two.length)return false;one.sort();two.sort();for(var i=0,l=one.length;i<l;i++)if(one[i]!==two[i])return false;return true}function trimSlashes(text){var trim_expression=/^\/+|\/+$/g;return text.replace(trim_expression,"")}URI._parts=function(){return{protocol:null,username:null,password:null,hostname:null,urn:null,port:null,
path:null,query:null,fragment:null,preventInvalidHostname:URI.preventInvalidHostname,duplicateQueryParameters:URI.duplicateQueryParameters,escapeQuerySpace:URI.escapeQuerySpace}};URI.preventInvalidHostname=false;URI.duplicateQueryParameters=false;URI.escapeQuerySpace=true;URI.protocol_expression=/^[a-z][a-z0-9.+-]*$/i;URI.idn_expression=/[^a-z0-9\._-]/i;URI.punycode_expression=/(xn--)/i;URI.ip4_expression=/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/;URI.ip6_expression=/^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/;
URI.find_uri_expression=/\b((?:[a-z][\w-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'".,<>?\u00ab\u00bb\u201c\u201d\u2018\u2019]))/ig;URI.findUri={start:/\b(?:([a-z][a-z0-9.+-]*:\/\/)|www\.)/gi,end:/[\s\r\n]|$/,trim:/[`!()\[\]{};:'".,<>?\u00ab\u00bb\u201c\u201d\u201e\u2018\u2019]+$/,parens:/(\([^\)]*\)|\[[^\]]*\]|\{[^}]*\}|<[^>]*>)/g};URI.leading_whitespace_expression=/^[\x00-\x20\u00a0\u1680\u2000-\u200a\u2028\u2029\u202f\u205f\u3000\ufeff]+/;
URI.ascii_tab_whitespace=/[\u0009\u000A\u000D]+/g;URI.defaultPorts={http:"80",https:"443",ftp:"21",gopher:"70",ws:"80",wss:"443"};URI.hostProtocols=["http","https"];URI.invalid_hostname_characters=/[^a-zA-Z0-9\.\-:_]/;URI.domAttributes={"a":"href","blockquote":"cite","link":"href","base":"href","script":"src","form":"action","img":"src","area":"href","iframe":"src","embed":"src","source":"src","track":"src","input":"src","audio":"src","video":"src"};URI.getDomAttribute=function(node){if(!node||!node.nodeName)return undefined;
var nodeName=node.nodeName.toLowerCase();if(nodeName==="input"&&node.type!=="image")return undefined;return URI.domAttributes[nodeName]};function escapeForDumbFirefox36(value){return escape(value)}function strictEncodeURIComponent(string){return encodeURIComponent(string).replace(/[!'()*]/g,escapeForDumbFirefox36).replace(/\*/g,"%2A")}URI.encode=strictEncodeURIComponent;URI.decode=decodeURIComponent;URI.iso8859=function(){URI.encode=escape;URI.decode=unescape};URI.unicode=function(){URI.encode=strictEncodeURIComponent;
URI.decode=decodeURIComponent};URI.characters={pathname:{encode:{expression:/%(24|26|2B|2C|3B|3D|3A|40)/ig,map:{"%24":"$","%26":"\x26","%2B":"+","%2C":",","%3B":";","%3D":"\x3d","%3A":":","%40":"@"}},decode:{expression:/[\/\?#]/g,map:{"/":"%2F","?":"%3F","#":"%23"}}},reserved:{encode:{expression:/%(21|23|24|26|27|28|29|2A|2B|2C|2F|3A|3B|3D|3F|40|5B|5D)/ig,map:{"%3A":":","%2F":"/","%3F":"?","%23":"#","%5B":"[","%5D":"]","%40":"@","%21":"!","%24":"$","%26":"\x26","%27":"'","%28":"(","%29":")","%2A":"*",
"%2B":"+","%2C":",","%3B":";","%3D":"\x3d"}}},urnpath:{encode:{expression:/%(21|24|27|28|29|2A|2B|2C|3B|3D|40)/ig,map:{"%21":"!","%24":"$","%27":"'","%28":"(","%29":")","%2A":"*","%2B":"+","%2C":",","%3B":";","%3D":"\x3d","%40":"@"}},decode:{expression:/[\/\?#:]/g,map:{"/":"%2F","?":"%3F","#":"%23",":":"%3A"}}}};URI.encodeQuery=function(string,escapeQuerySpace){var escaped=URI.encode(string+"");if(escapeQuerySpace===undefined)escapeQuerySpace=URI.escapeQuerySpace;return escapeQuerySpace?escaped.replace(/%20/g,
"+"):escaped};URI.decodeQuery=function(string,escapeQuerySpace){string+="";if(escapeQuerySpace===undefined)escapeQuerySpace=URI.escapeQuerySpace;try{return URI.decode(escapeQuerySpace?string.replace(/\+/g,"%20"):string)}catch(e){return string}};var _parts={"encode":"encode","decode":"decode"};var _part;var generateAccessor=function(_group,_part){return function(string){try{return URI[_part](string+"").replace(URI.characters[_group][_part].expression,function(c){return URI.characters[_group][_part].map[c]})}catch(e){return string}}};
for(_part in _parts){URI[_part+"PathSegment"]=generateAccessor("pathname",_parts[_part]);URI[_part+"UrnPathSegment"]=generateAccessor("urnpath",_parts[_part])}var generateSegmentedPathFunction=function(_sep,_codingFuncName,_innerCodingFuncName){return function(string){var actualCodingFunc;if(!_innerCodingFuncName)actualCodingFunc=URI[_codingFuncName];else actualCodingFunc=function(string){return URI[_codingFuncName](URI[_innerCodingFuncName](string))};var segments=(string+"").split(_sep);for(var i=
0,length=segments.length;i<length;i++)segments[i]=actualCodingFunc(segments[i]);return segments.join(_sep)}};URI.decodePath=generateSegmentedPathFunction("/","decodePathSegment");URI.decodeUrnPath=generateSegmentedPathFunction(":","decodeUrnPathSegment");URI.recodePath=generateSegmentedPathFunction("/","encodePathSegment","decode");URI.recodeUrnPath=generateSegmentedPathFunction(":","encodeUrnPathSegment","decode");URI.encodeReserved=generateAccessor("reserved","encode");URI.parse=function(string,
parts){var pos;if(!parts)parts={preventInvalidHostname:URI.preventInvalidHostname};string=string.replace(URI.leading_whitespace_expression,"");string=string.replace(URI.ascii_tab_whitespace,"");pos=string.indexOf("#");if(pos>-1){parts.fragment=string.substring(pos+1)||null;string=string.substring(0,pos)}pos=string.indexOf("?");if(pos>-1){parts.query=string.substring(pos+1)||null;string=string.substring(0,pos)}string=string.replace(/^(https?|ftp|wss?)?:+[/\\]*/i,"$1://");string=string.replace(/^[/\\]{2,}/i,
"//");if(string.substring(0,2)==="//"){parts.protocol=null;string=string.substring(2);string=URI.parseAuthority(string,parts)}else{pos=string.indexOf(":");if(pos>-1){parts.protocol=string.substring(0,pos)||null;if(parts.protocol&&!parts.protocol.match(URI.protocol_expression))parts.protocol=undefined;else if(string.substring(pos+1,pos+3).replace(/\\/g,"/")==="//"){string=string.substring(pos+3);string=URI.parseAuthority(string,parts)}else{string=string.substring(pos+1);parts.urn=true}}}parts.path=
string;return parts};URI.parseHost=function(string,parts){if(!string)string="";string=string.replace(/\\/g,"/");var pos=string.indexOf("/");var bracketPos;var t;if(pos===-1)pos=string.length;if(string.charAt(0)==="["){bracketPos=string.indexOf("]");parts.hostname=string.substring(1,bracketPos)||null;parts.port=string.substring(bracketPos+2,pos)||null;if(parts.port==="/")parts.port=null}else{var firstColon=string.indexOf(":");var firstSlash=string.indexOf("/");var nextColon=string.indexOf(":",firstColon+
1);if(nextColon!==-1&&(firstSlash===-1||nextColon<firstSlash)){parts.hostname=string.substring(0,pos)||null;parts.port=null}else{t=string.substring(0,pos).split(":");parts.hostname=t[0]||null;parts.port=t[1]||null}}if(parts.hostname&&string.substring(pos).charAt(0)!=="/"){pos++;string="/"+string}if(parts.preventInvalidHostname)URI.ensureValidHostname(parts.hostname,parts.protocol);if(parts.port)URI.ensureValidPort(parts.port);return string.substring(pos)||"/"};URI.parseAuthority=function(string,parts){string=
URI.parseUserinfo(string,parts);return URI.parseHost(string,parts)};URI.parseUserinfo=function(string,parts){var _string=string;var firstBackSlash=string.indexOf("\\");if(firstBackSlash!==-1)string=string.replace(/\\/g,"/");var firstSlash=string.indexOf("/");var pos=string.lastIndexOf("@",firstSlash>-1?firstSlash:string.length-1);var t;if(pos>-1&&(firstSlash===-1||pos<firstSlash)){t=string.substring(0,pos).split(":");parts.username=t[0]?URI.decode(t[0]):null;t.shift();parts.password=t[0]?URI.decode(t.join(":")):
null;string=_string.substring(pos+1)}else{parts.username=null;parts.password=null}return string};URI.parseQuery=function(string,escapeQuerySpace){if(!string)return{};string=string.replace(/&+/g,"\x26").replace(/^\?*&*|&+$/g,"");if(!string)return{};var items={};var splits=string.split("\x26");var length=splits.length;var v,name,value;for(var i=0;i<length;i++){v=splits[i].split("\x3d");name=URI.decodeQuery(v.shift(),escapeQuerySpace);value=v.length?URI.decodeQuery(v.join("\x3d"),escapeQuerySpace):null;
if(name==="__proto__")continue;else if(hasOwn.call(items,name)){if(typeof items[name]==="string"||items[name]===null)items[name]=[items[name]];items[name].push(value)}else items[name]=value}return items};URI.build=function(parts){var t="";var requireAbsolutePath=false;if(parts.protocol)t+=parts.protocol+":";if(!parts.urn&&(t||parts.hostname)){t+="//";requireAbsolutePath=true}t+=URI.buildAuthority(parts)||"";if(typeof parts.path==="string"){if(parts.path.charAt(0)!=="/"&&requireAbsolutePath)t+="/";
t+=parts.path}if(typeof parts.query==="string"&&parts.query)t+="?"+parts.query;if(typeof parts.fragment==="string"&&parts.fragment)t+="#"+parts.fragment;return t};URI.buildHost=function(parts){var t="";if(!parts.hostname)return"";else if(URI.ip6_expression.test(parts.hostname))t+="["+parts.hostname+"]";else t+=parts.hostname;if(parts.port)t+=":"+parts.port;return t};URI.buildAuthority=function(parts){return URI.buildUserinfo(parts)+URI.buildHost(parts)};URI.buildUserinfo=function(parts){var t="";
if(parts.username)t+=URI.encode(parts.username);if(parts.password)t+=":"+URI.encode(parts.password);if(t)t+="@";return t};URI.buildQuery=function(data,duplicateQueryParameters,escapeQuerySpace){var t="";var unique,key,i,length;for(key in data)if(key==="__proto__")continue;else if(hasOwn.call(data,key))if(isArray(data[key])){unique={};for(i=0,length=data[key].length;i<length;i++)if(data[key][i]!==undefined&&unique[data[key][i]+""]===undefined){t+="\x26"+URI.buildQueryParameter(key,data[key][i],escapeQuerySpace);
if(duplicateQueryParameters!==true)unique[data[key][i]+""]=true}}else if(data[key]!==undefined)t+="\x26"+URI.buildQueryParameter(key,data[key],escapeQuerySpace);return t.substring(1)};URI.buildQueryParameter=function(name,value,escapeQuerySpace){return URI.encodeQuery(name,escapeQuerySpace)+(value!==null?"\x3d"+URI.encodeQuery(value,escapeQuerySpace):"")};URI.addQuery=function(data,name,value){if(typeof name==="object")for(var key in name){if(hasOwn.call(name,key))URI.addQuery(data,key,name[key])}else if(typeof name===
"string"){if(data[name]===undefined){data[name]=value;return}else if(typeof data[name]==="string")data[name]=[data[name]];if(!isArray(value))value=[value];data[name]=(data[name]||[]).concat(value)}else throw new TypeError("URI.addQuery() accepts an object, string as the name parameter");};URI.setQuery=function(data,name,value){if(typeof name==="object")for(var key in name){if(hasOwn.call(name,key))URI.setQuery(data,key,name[key])}else if(typeof name==="string")data[name]=value===undefined?null:value;
else throw new TypeError("URI.setQuery() accepts an object, string as the name parameter");};URI.removeQuery=function(data,name,value){var i,length,key;if(isArray(name))for(i=0,length=name.length;i<length;i++)data[name[i]]=undefined;else if(getType(name)==="RegExp")for(key in data){if(name.test(key))data[key]=undefined}else if(typeof name==="object")for(key in name){if(hasOwn.call(name,key))URI.removeQuery(data,key,name[key])}else if(typeof name==="string")if(value!==undefined)if(getType(value)===
"RegExp")if(!isArray(data[name])&&value.test(data[name]))data[name]=undefined;else data[name]=filterArrayValues(data[name],value);else if(data[name]===String(value)&&(!isArray(value)||value.length===1))data[name]=undefined;else{if(isArray(data[name]))data[name]=filterArrayValues(data[name],value)}else data[name]=undefined;else throw new TypeError("URI.removeQuery() accepts an object, string, RegExp as the first parameter");};URI.hasQuery=function(data,name,value,withinArray){switch(getType(name)){case "String":break;
case "RegExp":for(var key in data)if(hasOwn.call(data,key))if(name.test(key)&&(value===undefined||URI.hasQuery(data,key,value)))return true;return false;case "Object":for(var _key in name)if(hasOwn.call(name,_key))if(!URI.hasQuery(data,_key,name[_key]))return false;return true;default:throw new TypeError("URI.hasQuery() accepts a string, regular expression or object as the name parameter");}switch(getType(value)){case "Undefined":return name in data;case "Boolean":var _booly=Boolean(isArray(data[name])?
data[name].length:data[name]);return value===_booly;case "Function":return!!value(data[name],name,data);case "Array":if(!isArray(data[name]))return false;var op=withinArray?arrayContains:arraysEqual;return op(data[name],value);case "RegExp":if(!isArray(data[name]))return Boolean(data[name]&&data[name].match(value));if(!withinArray)return false;return arrayContains(data[name],value);case "Number":value=String(value);case "String":if(!isArray(data[name]))return data[name]===value;if(!withinArray)return false;
return arrayContains(data[name],value);default:throw new TypeError("URI.hasQuery() accepts undefined, boolean, string, number, RegExp, Function as the value parameter");}};URI.joinPaths=function(){var input=[];var segments=[];var nonEmptySegments=0;for(var i=0;i<arguments.length;i++){var url=new URI(arguments[i]);input.push(url);var _segments=url.segment();for(var s=0;s<_segments.length;s++){if(typeof _segments[s]==="string")segments.push(_segments[s]);if(_segments[s])nonEmptySegments++}}if(!segments.length||
!nonEmptySegments)return new URI("");var uri=(new URI("")).segment(segments);if(input[0].path()===""||input[0].path().slice(0,1)==="/")uri.path("/"+uri.path());return uri.normalize()};URI.commonPath=function(one,two){var length=Math.min(one.length,two.length);var pos;for(pos=0;pos<length;pos++)if(one.charAt(pos)!==two.charAt(pos)){pos--;break}if(pos<1)return one.charAt(0)===two.charAt(0)&&one.charAt(0)==="/"?"/":"";if(one.charAt(pos)!=="/"||two.charAt(pos)!=="/")pos=one.substring(0,pos).lastIndexOf("/");
return one.substring(0,pos+1)};URI.withinString=function(string,callback,options){options||(options={});var _start=options.start||URI.findUri.start;var _end=options.end||URI.findUri.end;var _trim=options.trim||URI.findUri.trim;var _parens=options.parens||URI.findUri.parens;var _attributeOpen=/[a-z0-9-]=["']?$/i;_start.lastIndex=0;while(true){var match=_start.exec(string);if(!match)break;var start=match.index;if(options.ignoreHtml){var attributeOpen=string.slice(Math.max(start-3,0),start);if(attributeOpen&&
_attributeOpen.test(attributeOpen))continue}var end=start+string.slice(start).search(_end);var slice=string.slice(start,end);var parensEnd=-1;while(true){var parensMatch=_parens.exec(slice);if(!parensMatch)break;var parensMatchEnd=parensMatch.index+parensMatch[0].length;parensEnd=Math.max(parensEnd,parensMatchEnd)}if(parensEnd>-1)slice=slice.slice(0,parensEnd)+slice.slice(parensEnd).replace(_trim,"");else slice=slice.replace(_trim,"");if(slice.length<=match[0].length)continue;if(options.ignore&&options.ignore.test(slice))continue;
end=start+slice.length;var result=callback(slice,start,end,string);if(result===undefined){_start.lastIndex=end;continue}result=String(result);string=string.slice(0,start)+result+string.slice(end);_start.lastIndex=start+result.length}_start.lastIndex=0;return string};URI.ensureValidHostname=function(v,protocol){var hasHostname=!!v;var hasProtocol=!!protocol;var rejectEmptyHostname=false;if(hasProtocol)rejectEmptyHostname=arrayContains(URI.hostProtocols,protocol);if(rejectEmptyHostname&&!hasHostname)throw new TypeError("Hostname cannot be empty, if protocol is "+
protocol);else if(v&&v.match(URI.invalid_hostname_characters)){if(!punycode)throw new TypeError('Hostname "'+v+'" contains characters other than [A-Z0-9.-:_] and Punycode.js is not available');if(punycode.toASCII(v).match(URI.invalid_hostname_characters))throw new TypeError('Hostname "'+v+'" contains characters other than [A-Z0-9.-:_]');}};URI.ensureValidPort=function(v){if(!v)return;var port=Number(v);if(isInteger(port)&&port>0&&port<65536)return;throw new TypeError('Port "'+v+'" is not a valid port');
};URI.noConflict=function(removeAll){if(removeAll){var unconflicted={URI:this.noConflict()};if(root.URITemplate&&typeof root.URITemplate.noConflict==="function")unconflicted.URITemplate=root.URITemplate.noConflict();if(root.IPv6&&typeof root.IPv6.noConflict==="function")unconflicted.IPv6=root.IPv6.noConflict();if(root.SecondLevelDomains&&typeof root.SecondLevelDomains.noConflict==="function")unconflicted.SecondLevelDomains=root.SecondLevelDomains.noConflict();return unconflicted}else if(root.URI===
this)root.URI=_URI;return this};p.build=function(deferBuild){if(deferBuild===true)this._deferred_build=true;else if(deferBuild===undefined||this._deferred_build){this._string=URI.build(this._parts);this._deferred_build=false}return this};p.clone=function(){return new URI(this)};p.valueOf=p.toString=function(){return this.build(false)._string};function generateSimpleAccessor(_part){return function(v,build){if(v===undefined)return this._parts[_part]||"";else{this._parts[_part]=v||null;this.build(!build);
return this}}}function generatePrefixAccessor(_part,_key){return function(v,build){if(v===undefined)return this._parts[_part]||"";else{if(v!==null){v=v+"";if(v.charAt(0)===_key)v=v.substring(1)}this._parts[_part]=v;this.build(!build);return this}}}p.protocol=generateSimpleAccessor("protocol");p.username=generateSimpleAccessor("username");p.password=generateSimpleAccessor("password");p.hostname=generateSimpleAccessor("hostname");p.port=generateSimpleAccessor("port");p.query=generatePrefixAccessor("query",
"?");p.fragment=generatePrefixAccessor("fragment","#");p.search=function(v,build){var t=this.query(v,build);return typeof t==="string"&&t.length?"?"+t:t};p.hash=function(v,build){var t=this.fragment(v,build);return typeof t==="string"&&t.length?"#"+t:t};p.pathname=function(v,build){if(v===undefined||v===true){var res=this._parts.path||(this._parts.hostname?"/":"");return v?(this._parts.urn?URI.decodeUrnPath:URI.decodePath)(res):res}else{if(this._parts.urn)this._parts.path=v?URI.recodeUrnPath(v):"";
else this._parts.path=v?URI.recodePath(v):"/";this.build(!build);return this}};p.path=p.pathname;p.href=function(href,build){var key;if(href===undefined)return this.toString();this._string="";this._parts=URI._parts();var _URI=href instanceof URI;var _object=typeof href==="object"&&(href.hostname||href.path||href.pathname);if(href.nodeName){var attribute=URI.getDomAttribute(href);href=href[attribute]||"";_object=false}if(!_URI&&_object&&href.pathname!==undefined)href=href.toString();if(typeof href===
"string"||href instanceof String)this._parts=URI.parse(String(href),this._parts);else if(_URI||_object){var src=_URI?href._parts:href;for(key in src){if(key==="query")continue;if(hasOwn.call(this._parts,key))this._parts[key]=src[key]}if(src.query)this.query(src.query,false)}else throw new TypeError("invalid input");this.build(!build);return this};p.is=function(what){var ip=false;var ip4=false;var ip6=false;var name=false;var sld=false;var idn=false;var punycode=false;var relative=!this._parts.urn;
if(this._parts.hostname){relative=false;ip4=URI.ip4_expression.test(this._parts.hostname);ip6=URI.ip6_expression.test(this._parts.hostname);ip=ip4||ip6;name=!ip;sld=name&&SLD&&SLD.has(this._parts.hostname);idn=name&&URI.idn_expression.test(this._parts.hostname);punycode=name&&URI.punycode_expression.test(this._parts.hostname)}switch(what.toLowerCase()){case "relative":return relative;case "absolute":return!relative;case "domain":case "name":return name;case "sld":return sld;case "ip":return ip;case "ip4":case "ipv4":case "inet4":return ip4;
case "ip6":case "ipv6":case "inet6":return ip6;case "idn":return idn;case "url":return!this._parts.urn;case "urn":return!!this._parts.urn;case "punycode":return punycode}return null};var _protocol=p.protocol;var _port=p.port;var _hostname=p.hostname;p.protocol=function(v,build){if(v){v=v.replace(/:(\/\/)?$/,"");if(!v.match(URI.protocol_expression))throw new TypeError('Protocol "'+v+"\" contains characters other than [A-Z0-9.+-] or doesn't start with [A-Z]");}return _protocol.call(this,v,build)};p.scheme=
p.protocol;p.port=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(v!==undefined){if(v===0)v=null;if(v){v+="";if(v.charAt(0)===":")v=v.substring(1);URI.ensureValidPort(v)}}return _port.call(this,v,build)};p.hostname=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(v!==undefined){var x={preventInvalidHostname:this._parts.preventInvalidHostname};var res=URI.parseHost(v,x);if(res!=="/")throw new TypeError('Hostname "'+v+'" contains characters other than [A-Z0-9.-]');
v=x.hostname;if(this._parts.preventInvalidHostname)URI.ensureValidHostname(v,this._parts.protocol)}return _hostname.call(this,v,build)};p.origin=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(v===undefined){var protocol=this.protocol();var authority=this.authority();if(!authority)return"";return(protocol?protocol+"://":"")+this.authority()}else{var origin=URI(v);this.protocol(origin.protocol()).authority(origin.authority()).build(!build);return this}};p.host=function(v,build){if(this._parts.urn)return v===
undefined?"":this;if(v===undefined)return this._parts.hostname?URI.buildHost(this._parts):"";else{var res=URI.parseHost(v,this._parts);if(res!=="/")throw new TypeError('Hostname "'+v+'" contains characters other than [A-Z0-9.-]');this.build(!build);return this}};p.authority=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(v===undefined)return this._parts.hostname?URI.buildAuthority(this._parts):"";else{var res=URI.parseAuthority(v,this._parts);if(res!=="/")throw new TypeError('Hostname "'+
v+'" contains characters other than [A-Z0-9.-]');this.build(!build);return this}};p.userinfo=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(v===undefined){var t=URI.buildUserinfo(this._parts);return t?t.substring(0,t.length-1):t}else{if(v[v.length-1]!=="@")v+="@";URI.parseUserinfo(v,this._parts);this.build(!build);return this}};p.resource=function(v,build){var parts;if(v===undefined)return this.path()+this.search()+this.hash();parts=URI.parse(v);this._parts.path=parts.path;this._parts.query=
parts.query;this._parts.fragment=parts.fragment;this.build(!build);return this};p.subdomain=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(v===undefined){if(!this._parts.hostname||this.is("IP"))return"";var end=this._parts.hostname.length-this.domain().length-1;return this._parts.hostname.substring(0,end)||""}else{var e=this._parts.hostname.length-this.domain().length;var sub=this._parts.hostname.substring(0,e);var replace=new RegExp("^"+escapeRegEx(sub));if(v&&v.charAt(v.length-
1)!==".")v+=".";if(v.indexOf(":")!==-1)throw new TypeError("Domains cannot contain colons");if(v)URI.ensureValidHostname(v,this._parts.protocol);this._parts.hostname=this._parts.hostname.replace(replace,v);this.build(!build);return this}};p.domain=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(typeof v==="boolean"){build=v;v=undefined}if(v===undefined){if(!this._parts.hostname||this.is("IP"))return"";var t=this._parts.hostname.match(/\./g);if(t&&t.length<2)return this._parts.hostname;
var end=this._parts.hostname.length-this.tld(build).length-1;end=this._parts.hostname.lastIndexOf(".",end-1)+1;return this._parts.hostname.substring(end)||""}else{if(!v)throw new TypeError("cannot set domain empty");if(v.indexOf(":")!==-1)throw new TypeError("Domains cannot contain colons");URI.ensureValidHostname(v,this._parts.protocol);if(!this._parts.hostname||this.is("IP"))this._parts.hostname=v;else{var replace=new RegExp(escapeRegEx(this.domain())+"$");this._parts.hostname=this._parts.hostname.replace(replace,
v)}this.build(!build);return this}};p.tld=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(typeof v==="boolean"){build=v;v=undefined}if(v===undefined){if(!this._parts.hostname||this.is("IP"))return"";var pos=this._parts.hostname.lastIndexOf(".");var tld=this._parts.hostname.substring(pos+1);if(build!==true&&SLD&&SLD.list[tld.toLowerCase()])return SLD.get(this._parts.hostname)||tld;return tld}else{var replace;if(!v)throw new TypeError("cannot set TLD empty");else if(v.match(/[^a-zA-Z0-9-]/))if(SLD&&
SLD.is(v)){replace=new RegExp(escapeRegEx(this.tld())+"$");this._parts.hostname=this._parts.hostname.replace(replace,v)}else throw new TypeError('TLD "'+v+'" contains characters other than [A-Z0-9]');else if(!this._parts.hostname||this.is("IP"))throw new ReferenceError("cannot set TLD on non-domain host");else{replace=new RegExp(escapeRegEx(this.tld())+"$");this._parts.hostname=this._parts.hostname.replace(replace,v)}this.build(!build);return this}};p.directory=function(v,build){if(this._parts.urn)return v===
undefined?"":this;if(v===undefined||v===true){if(!this._parts.path&&!this._parts.hostname)return"";if(this._parts.path==="/")return"/";var end=this._parts.path.length-this.filename().length-1;var res=this._parts.path.substring(0,end)||(this._parts.hostname?"/":"");return v?URI.decodePath(res):res}else{var e=this._parts.path.length-this.filename().length;var directory=this._parts.path.substring(0,e);var replace=new RegExp("^"+escapeRegEx(directory));if(!this.is("relative")){if(!v)v="/";if(v.charAt(0)!==
"/")v="/"+v}if(v&&v.charAt(v.length-1)!=="/")v+="/";v=URI.recodePath(v);this._parts.path=this._parts.path.replace(replace,v);this.build(!build);return this}};p.filename=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(typeof v!=="string"){if(!this._parts.path||this._parts.path==="/")return"";var pos=this._parts.path.lastIndexOf("/");var res=this._parts.path.substring(pos+1);return v?URI.decodePathSegment(res):res}else{var mutatedDirectory=false;if(v.charAt(0)==="/")v=v.substring(1);
if(v.match(/\.?\//))mutatedDirectory=true;var replace=new RegExp(escapeRegEx(this.filename())+"$");v=URI.recodePath(v);this._parts.path=this._parts.path.replace(replace,v);if(mutatedDirectory)this.normalizePath(build);else this.build(!build);return this}};p.suffix=function(v,build){if(this._parts.urn)return v===undefined?"":this;if(v===undefined||v===true){if(!this._parts.path||this._parts.path==="/")return"";var filename=this.filename();var pos=filename.lastIndexOf(".");var s,res;if(pos===-1)return"";
s=filename.substring(pos+1);res=/^[a-z0-9%]+$/i.test(s)?s:"";return v?URI.decodePathSegment(res):res}else{if(v.charAt(0)===".")v=v.substring(1);var suffix=this.suffix();var replace;if(!suffix){if(!v)return this;this._parts.path+="."+URI.recodePath(v)}else if(!v)replace=new RegExp(escapeRegEx("."+suffix)+"$");else replace=new RegExp(escapeRegEx(suffix)+"$");if(replace){v=URI.recodePath(v);this._parts.path=this._parts.path.replace(replace,v)}this.build(!build);return this}};p.segment=function(segment,
v,build){var separator=this._parts.urn?":":"/";var path=this.path();var absolute=path.substring(0,1)==="/";var segments=path.split(separator);if(segment!==undefined&&typeof segment!=="number"){build=v;v=segment;segment=undefined}if(segment!==undefined&&typeof segment!=="number")throw new Error('Bad segment "'+segment+'", must be 0-based integer');if(absolute)segments.shift();if(segment<0)segment=Math.max(segments.length+segment,0);if(v===undefined)return segment===undefined?segments:segments[segment];
else if(segment===null||segments[segment]===undefined)if(isArray(v)){segments=[];for(var i=0,l=v.length;i<l;i++){if(!v[i].length&&(!segments.length||!segments[segments.length-1].length))continue;if(segments.length&&!segments[segments.length-1].length)segments.pop();segments.push(trimSlashes(v[i]))}}else{if(v||typeof v==="string"){v=trimSlashes(v);if(segments[segments.length-1]==="")segments[segments.length-1]=v;else segments.push(v)}}else if(v)segments[segment]=trimSlashes(v);else segments.splice(segment,
1);if(absolute)segments.unshift("");return this.path(segments.join(separator),build)};p.segmentCoded=function(segment,v,build){var segments,i,l;if(typeof segment!=="number"){build=v;v=segment;segment=undefined}if(v===undefined){segments=this.segment(segment,v,build);if(!isArray(segments))segments=segments!==undefined?URI.decode(segments):undefined;else for(i=0,l=segments.length;i<l;i++)segments[i]=URI.decode(segments[i]);return segments}if(!isArray(v))v=typeof v==="string"||v instanceof String?URI.encode(v):
v;else for(i=0,l=v.length;i<l;i++)v[i]=URI.encode(v[i]);return this.segment(segment,v,build)};var q=p.query;p.query=function(v,build){if(v===true)return URI.parseQuery(this._parts.query,this._parts.escapeQuerySpace);else if(typeof v==="function"){var data=URI.parseQuery(this._parts.query,this._parts.escapeQuerySpace);var result=v.call(this,data);this._parts.query=URI.buildQuery(result||data,this._parts.duplicateQueryParameters,this._parts.escapeQuerySpace);this.build(!build);return this}else if(v!==
undefined&&typeof v!=="string"){this._parts.query=URI.buildQuery(v,this._parts.duplicateQueryParameters,this._parts.escapeQuerySpace);this.build(!build);return this}else return q.call(this,v,build)};p.setQuery=function(name,value,build){var data=URI.parseQuery(this._parts.query,this._parts.escapeQuerySpace);if(typeof name==="string"||name instanceof String)data[name]=value!==undefined?value:null;else if(typeof name==="object")for(var key in name){if(hasOwn.call(name,key))data[key]=name[key]}else throw new TypeError("URI.addQuery() accepts an object, string as the name parameter");
this._parts.query=URI.buildQuery(data,this._parts.duplicateQueryParameters,this._parts.escapeQuerySpace);if(typeof name!=="string")build=value;this.build(!build);return this};p.addQuery=function(name,value,build){var data=URI.parseQuery(this._parts.query,this._parts.escapeQuerySpace);URI.addQuery(data,name,value===undefined?null:value);this._parts.query=URI.buildQuery(data,this._parts.duplicateQueryParameters,this._parts.escapeQuerySpace);if(typeof name!=="string")build=value;this.build(!build);return this};
p.removeQuery=function(name,value,build){var data=URI.parseQuery(this._parts.query,this._parts.escapeQuerySpace);URI.removeQuery(data,name,value);this._parts.query=URI.buildQuery(data,this._parts.duplicateQueryParameters,this._parts.escapeQuerySpace);if(typeof name!=="string")build=value;this.build(!build);return this};p.hasQuery=function(name,value,withinArray){var data=URI.parseQuery(this._parts.query,this._parts.escapeQuerySpace);return URI.hasQuery(data,name,value,withinArray)};p.setSearch=p.setQuery;
p.addSearch=p.addQuery;p.removeSearch=p.removeQuery;p.hasSearch=p.hasQuery;p.normalize=function(){if(this._parts.urn)return this.normalizeProtocol(false).normalizePath(false).normalizeQuery(false).normalizeFragment(false).build();return this.normalizeProtocol(false).normalizeHostname(false).normalizePort(false).normalizePath(false).normalizeQuery(false).normalizeFragment(false).build()};p.normalizeProtocol=function(build){if(typeof this._parts.protocol==="string"){this._parts.protocol=this._parts.protocol.toLowerCase();
this.build(!build)}return this};p.normalizeHostname=function(build){if(this._parts.hostname){if(this.is("IDN")&&punycode)this._parts.hostname=punycode.toASCII(this._parts.hostname);else if(this.is("IPv6")&&IPv6)this._parts.hostname=IPv6.best(this._parts.hostname);this._parts.hostname=this._parts.hostname.toLowerCase();this.build(!build)}return this};p.normalizePort=function(build){if(typeof this._parts.protocol==="string"&&this._parts.port===URI.defaultPorts[this._parts.protocol]){this._parts.port=
null;this.build(!build)}return this};p.normalizePath=function(build){var _path=this._parts.path;if(!_path)return this;if(this._parts.urn){this._parts.path=URI.recodeUrnPath(this._parts.path);this.build(!build);return this}if(this._parts.path==="/")return this;_path=URI.recodePath(_path);var _was_relative;var _leadingParents="";var _parent,_pos;if(_path.charAt(0)!=="/"){_was_relative=true;_path="/"+_path}if(_path.slice(-3)==="/.."||_path.slice(-2)==="/.")_path+="/";_path=_path.replace(/(\/(\.\/)+)|(\/\.$)/g,
"/").replace(/\/{2,}/g,"/");if(_was_relative){_leadingParents=_path.substring(1).match(/^(\.\.\/)+/)||"";if(_leadingParents)_leadingParents=_leadingParents[0]}while(true){_parent=_path.search(/\/\.\.(\/|$)/);if(_parent===-1)break;else if(_parent===0){_path=_path.substring(3);continue}_pos=_path.substring(0,_parent).lastIndexOf("/");if(_pos===-1)_pos=_parent;_path=_path.substring(0,_pos)+_path.substring(_parent+3)}if(_was_relative&&this.is("relative"))_path=_leadingParents+_path.substring(1);this._parts.path=
_path;this.build(!build);return this};p.normalizePathname=p.normalizePath;p.normalizeQuery=function(build){if(typeof this._parts.query==="string"){if(!this._parts.query.length)this._parts.query=null;else this.query(URI.parseQuery(this._parts.query,this._parts.escapeQuerySpace));this.build(!build)}return this};p.normalizeFragment=function(build){if(!this._parts.fragment){this._parts.fragment=null;this.build(!build)}return this};p.normalizeSearch=p.normalizeQuery;p.normalizeHash=p.normalizeFragment;
p.iso8859=function(){var e=URI.encode;var d=URI.decode;URI.encode=escape;URI.decode=decodeURIComponent;try{this.normalize()}finally{URI.encode=e;URI.decode=d}return this};p.unicode=function(){var e=URI.encode;var d=URI.decode;URI.encode=strictEncodeURIComponent;URI.decode=unescape;try{this.normalize()}finally{URI.encode=e;URI.decode=d}return this};p.readable=function(){var uri=this.clone();uri.username("").password("").normalize();var t="";if(uri._parts.protocol)t+=uri._parts.protocol+"://";if(uri._parts.hostname)if(uri.is("punycode")&&
punycode){t+=punycode.toUnicode(uri._parts.hostname);if(uri._parts.port)t+=":"+uri._parts.port}else t+=uri.host();if(uri._parts.hostname&&uri._parts.path&&uri._parts.path.charAt(0)!=="/")t+="/";t+=uri.path(true);if(uri._parts.query){var q="";for(var i=0,qp=uri._parts.query.split("\x26"),l=qp.length;i<l;i++){var kv=(qp[i]||"").split("\x3d");q+="\x26"+URI.decodeQuery(kv[0],this._parts.escapeQuerySpace).replace(/&/g,"%26");if(kv[1]!==undefined)q+="\x3d"+URI.decodeQuery(kv[1],this._parts.escapeQuerySpace).replace(/&/g,
"%26")}t+="?"+q.substring(1)}t+=URI.decodeQuery(uri.hash(),true);return t};p.absoluteTo=function(base){var resolved=this.clone();var properties=["protocol","username","password","hostname","port"];var basedir,i,p;if(this._parts.urn)throw new Error("URNs do not have any generally defined hierarchical components");if(!(base instanceof URI))base=new URI(base);if(resolved._parts.protocol)return resolved;else resolved._parts.protocol=base._parts.protocol;if(this._parts.hostname)return resolved;for(i=0;p=
properties[i];i++)resolved._parts[p]=base._parts[p];if(!resolved._parts.path){resolved._parts.path=base._parts.path;if(!resolved._parts.query)resolved._parts.query=base._parts.query}else{if(resolved._parts.path.substring(-2)==="..")resolved._parts.path+="/";if(resolved.path().charAt(0)!=="/"){basedir=base.directory();basedir=basedir?basedir:base.path().indexOf("/")===0?"/":"";resolved._parts.path=(basedir?basedir+"/":"")+resolved._parts.path;resolved.normalizePath()}}resolved.build();return resolved};
p.relativeTo=function(base){var relative=this.clone().normalize();var relativeParts,baseParts,common,relativePath,basePath;if(relative._parts.urn)throw new Error("URNs do not have any generally defined hierarchical components");base=(new URI(base)).normalize();relativeParts=relative._parts;baseParts=base._parts;relativePath=relative.path();basePath=base.path();if(relativePath.charAt(0)!=="/")throw new Error("URI is already relative");if(basePath.charAt(0)!=="/")throw new Error("Cannot calculate a URI relative to another relative URI");
if(relativeParts.protocol===baseParts.protocol)relativeParts.protocol=null;if(relativeParts.username!==baseParts.username||relativeParts.password!==baseParts.password)return relative.build();if(relativeParts.protocol!==null||relativeParts.username!==null||relativeParts.password!==null)return relative.build();if(relativeParts.hostname===baseParts.hostname&&relativeParts.port===baseParts.port){relativeParts.hostname=null;relativeParts.port=null}else return relative.build();if(relativePath===basePath){relativeParts.path=
"";return relative.build()}common=URI.commonPath(relativePath,basePath);if(!common)return relative.build();var parents=baseParts.path.substring(common.length).replace(/[^\/]*$/,"").replace(/.*?\//g,"../");relativeParts.path=parents+relativeParts.path.substring(common.length)||"./";return relative.build()};p.equals=function(uri){var one=this.clone();var two=new URI(uri);var one_map={};var two_map={};var checked={};var one_query,two_query,key;one.normalize();two.normalize();if(one.toString()===two.toString())return true;
one_query=one.query();two_query=two.query();one.query("");two.query("");if(one.toString()!==two.toString())return false;if(one_query.length!==two_query.length)return false;one_map=URI.parseQuery(one_query,this._parts.escapeQuerySpace);two_map=URI.parseQuery(two_query,this._parts.escapeQuerySpace);for(key in one_map)if(hasOwn.call(one_map,key)){if(!isArray(one_map[key])){if(one_map[key]!==two_map[key])return false}else if(!arraysEqual(one_map[key],two_map[key]))return false;checked[key]=true}for(key in two_map)if(hasOwn.call(two_map,
key))if(!checked[key])return false;return true};p.preventInvalidHostname=function(v){this._parts.preventInvalidHostname=!!v;return this};p.duplicateQueryParameters=function(v){this._parts.duplicateQueryParameters=!!v;return this};p.escapeQuerySpace=function(v){this._parts.escapeQuerySpace=!!v;return this};return URI});
(function(root,factory){if(typeof module==="object"&&module.exports)module.exports=factory(require("./URI"));else if(typeof define==="function"&&define.amd)define(["./URI"],factory);else root.URITemplate=factory(root.URI,root)})(this,function(URI,root){var _URITemplate=root&&root.URITemplate;var hasOwn=Object.prototype.hasOwnProperty;function URITemplate(expression){if(URITemplate._cache[expression])return URITemplate._cache[expression];if(!(this instanceof URITemplate))return new URITemplate(expression);
this.expression=expression;URITemplate._cache[expression]=this;return this}function Data(data){this.data=data;this.cache={}}var p=URITemplate.prototype;var operators={"":{prefix:"",separator:",",named:false,empty_name_separator:false,encode:"encode"},"+":{prefix:"",separator:",",named:false,empty_name_separator:false,encode:"encodeReserved"},"#":{prefix:"#",separator:",",named:false,empty_name_separator:false,encode:"encodeReserved"},".":{prefix:".",separator:".",named:false,empty_name_separator:false,
encode:"encode"},"/":{prefix:"/",separator:"/",named:false,empty_name_separator:false,encode:"encode"},";":{prefix:";",separator:";",named:true,empty_name_separator:false,encode:"encode"},"?":{prefix:"?",separator:"\x26",named:true,empty_name_separator:true,encode:"encode"},"\x26":{prefix:"\x26",separator:"\x26",named:true,empty_name_separator:true,encode:"encode"}};URITemplate._cache={};URITemplate.EXPRESSION_PATTERN=/\{([^a-zA-Z0-9%_]?)([^\}]+)(\}|$)/g;URITemplate.VARIABLE_PATTERN=/^([^*:.](?:\.?[^*:.])*)((\*)|:(\d+))?$/;
URITemplate.VARIABLE_NAME_PATTERN=/[^a-zA-Z0-9%_.]/;URITemplate.LITERAL_PATTERN=/[<>{}"`^| \\]/;URITemplate.expand=function(expression,data,opts){var options=operators[expression.operator];var type=options.named?"Named":"Unnamed";var variables=expression.variables;var buffer=[];var d,variable,i;for(i=0;variable=variables[i];i++){d=data.get(variable.name);if(d.type===0&&opts&&opts.strict)throw new Error('Missing expansion value for variable "'+variable.name+'"');if(!d.val.length){if(d.type)buffer.push("");
continue}if(d.type>1&&variable.maxlength)throw new Error('Invalid expression: Prefix modifier not applicable to variable "'+variable.name+'"');buffer.push(URITemplate["expand"+type](d,options,variable.explode,variable.explode&&options.separator||",",variable.maxlength,variable.name))}if(buffer.length)return options.prefix+buffer.join(options.separator);else return""};URITemplate.expandNamed=function(d,options,explode,separator,length,name){var result="";var encode=options.encode;var empty_name_separator=
options.empty_name_separator;var _encode=!d[encode].length;var _name=d.type===2?"":URI[encode](name);var _value,i,l;for(i=0,l=d.val.length;i<l;i++){if(length){_value=URI[encode](d.val[i][1].substring(0,length));if(d.type===2)_name=URI[encode](d.val[i][0].substring(0,length))}else if(_encode){_value=URI[encode](d.val[i][1]);if(d.type===2){_name=URI[encode](d.val[i][0]);d[encode].push([_name,_value])}else d[encode].push([undefined,_value])}else{_value=d[encode][i][1];if(d.type===2)_name=d[encode][i][0]}if(result)result+=
separator;if(!explode){if(!i)result+=URI[encode](name)+(empty_name_separator||_value?"\x3d":"");if(d.type===2)result+=_name+",";result+=_value}else result+=_name+(empty_name_separator||_value?"\x3d":"")+_value}return result};URITemplate.expandUnnamed=function(d,options,explode,separator,length){var result="";var encode=options.encode;var empty_name_separator=options.empty_name_separator;var _encode=!d[encode].length;var _name,_value,i,l;for(i=0,l=d.val.length;i<l;i++){if(length)_value=URI[encode](d.val[i][1].substring(0,
length));else if(_encode){_value=URI[encode](d.val[i][1]);d[encode].push([d.type===2?URI[encode](d.val[i][0]):undefined,_value])}else _value=d[encode][i][1];if(result)result+=separator;if(d.type===2){if(length)_name=URI[encode](d.val[i][0].substring(0,length));else _name=d[encode][i][0];result+=_name;if(explode)result+=empty_name_separator||_value?"\x3d":"";else result+=","}result+=_value}return result};URITemplate.noConflict=function(){if(root.URITemplate===URITemplate)root.URITemplate=_URITemplate;
return URITemplate};p.expand=function(data,opts){var result="";if(!this.parts||!this.parts.length)this.parse();if(!(data instanceof Data))data=new Data(data);for(var i=0,l=this.parts.length;i<l;i++)result+=typeof this.parts[i]==="string"?this.parts[i]:URITemplate.expand(this.parts[i],data,opts);return result};p.parse=function(){var expression=this.expression;var ePattern=URITemplate.EXPRESSION_PATTERN;var vPattern=URITemplate.VARIABLE_PATTERN;var nPattern=URITemplate.VARIABLE_NAME_PATTERN;var lPattern=
URITemplate.LITERAL_PATTERN;var parts=[];var pos=0;var variables,eMatch,vMatch;var checkLiteral=function(literal){if(literal.match(lPattern))throw new Error('Invalid Literal "'+literal+'"');return literal};ePattern.lastIndex=0;while(true){eMatch=ePattern.exec(expression);if(eMatch===null){parts.push(checkLiteral(expression.substring(pos)));break}else{parts.push(checkLiteral(expression.substring(pos,eMatch.index)));pos=eMatch.index+eMatch[0].length}if(!operators[eMatch[1]])throw new Error('Unknown Operator "'+
eMatch[1]+'" in "'+eMatch[0]+'"');else if(!eMatch[3])throw new Error('Unclosed Expression "'+eMatch[0]+'"');variables=eMatch[2].split(",");for(var i=0,l=variables.length;i<l;i++){vMatch=variables[i].match(vPattern);if(vMatch===null)throw new Error('Invalid Variable "'+variables[i]+'" in "'+eMatch[0]+'"');else if(vMatch[1].match(nPattern))throw new Error('Invalid Variable Name "'+vMatch[1]+'" in "'+eMatch[0]+'"');variables[i]={name:vMatch[1],explode:!!vMatch[3],maxlength:vMatch[4]&&parseInt(vMatch[4],
10)}}if(!variables.length)throw new Error('Expression Missing Variable(s) "'+eMatch[0]+'"');parts.push({expression:eMatch[0],operator:eMatch[1],variables:variables})}if(!parts.length)parts.push(checkLiteral(expression));this.parts=parts;return this};Data.prototype.get=function(key){var data=this.data;var d={type:0,val:[],encode:[],encodeReserved:[]};var i,l,value;if(this.cache[key]!==undefined)return this.cache[key];this.cache[key]=d;if(String(Object.prototype.toString.call(data))==="[object Function]")value=
data(key);else if(String(Object.prototype.toString.call(data[key]))==="[object Function]")value=data[key](key);else value=data[key];if(value===undefined||value===null)return d;else if(String(Object.prototype.toString.call(value))==="[object Array]"){for(i=0,l=value.length;i<l;i++)if(value[i]!==undefined&&value[i]!==null)d.val.push([undefined,String(value[i])]);if(d.val.length)d.type=3}else if(String(Object.prototype.toString.call(value))==="[object Object]"){for(i in value)if(hasOwn.call(value,i)&&
value[i]!==undefined&&value[i]!==null)d.val.push([i,String(value[i])]);if(d.val.length)d.type=2}else{d.type=1;d.val.push([undefined,String(value)])}return d};URI.expand=function(expression,data){var template=new URITemplate(expression);var expansion=template.expand(data);return new URI(expansion)};return URITemplate});
(function(root,factory){if(typeof module==="object"&&module.exports)module.exports=factory(require("jquery"),require("./URI"));else if(typeof define==="function"&&define.amd)define(["jquery","./URI"],factory);else factory(root.jQuery,root.URI)})(this,function($,URI){var comparable={};var compare={"\x3d":function(value,target){return value===target},"^\x3d":function(value,target){return!!(value+"").match(new RegExp("^"+escapeRegEx(target),"i"))},"$\x3d":function(value,target){return!!(value+"").match(new RegExp(escapeRegEx(target)+
"$","i"))},"*\x3d":function(value,target,property){if(property==="directory")value+="/";return!!(value+"").match(new RegExp(escapeRegEx(target),"i"))},"equals:":function(uri,target){return uri.equals(target)},"is:":function(uri,target){return uri.is(target)}};function escapeRegEx(string){return string.replace(/([.*+?^=!:${}()|[\]\/\\])/g,"\\$1")}function getUriProperty(elem){var nodeName=elem.nodeName.toLowerCase();var property=URI.domAttributes[nodeName];if(nodeName==="input"&&elem.type!=="image")return undefined;
return property}function generateAccessor(property){return{get:function(elem){return $(elem).uri()[property]()},set:function(elem,value){$(elem).uri()[property](value);return value}}}$.each("origin authority directory domain filename fragment hash host hostname href password path pathname port protocol query resource scheme search subdomain suffix tld username".split(" "),function(k,v){comparable[v]=true;$.attrHooks["uri:"+v]=generateAccessor(v)});var _attrHooks={get:function(elem){return $(elem).uri()},
set:function(elem,value){return $(elem).uri().href(value).toString()}};$.each(["src","href","action","uri","cite"],function(k,v){$.attrHooks[v]={set:_attrHooks.set}});$.attrHooks.uri.get=_attrHooks.get;$.fn.uri=function(uri){var $this=this.first();var elem=$this.get(0);var property=getUriProperty(elem);if(!property)throw new Error('Element "'+elem.nodeName+'" does not have either property: href, src, action, cite');if(uri!==undefined){var old=$this.data("uri");if(old)return old.href(uri);if(!(uri instanceof
URI))uri=URI(uri||"")}else{uri=$this.data("uri");if(uri)return uri;else uri=URI($this.attr(property)||"")}uri._dom_element=elem;uri._dom_attribute=property;uri.normalize();$this.data("uri",uri);return uri};URI.prototype.build=function(deferBuild){if(this._dom_element){this._string=URI.build(this._parts);this._deferred_build=false;this._dom_element.setAttribute(this._dom_attribute,this._string);this._dom_element[this._dom_attribute]=this._string}else if(deferBuild===true)this._deferred_build=true;
else if(deferBuild===undefined||this._deferred_build){this._string=URI.build(this._parts);this._deferred_build=false}return this};var uriSizzle;var pseudoArgs=/^([a-zA-Z]+)\s*([\^\$*]?=|:)\s*(['"]?)(.+)\3|^\s*([a-zA-Z0-9]+)\s*$/;function uriPseudo(elem,text){var match,property,uri;if(!getUriProperty(elem)||!text)return false;match=text.match(pseudoArgs);if(!match||!match[5]&&match[2]!==":"&&!compare[match[2]])return false;uri=$(elem).uri();if(match[5])return uri.is(match[5]);else if(match[2]===":"){property=
match[1].toLowerCase()+":";if(!compare[property])return false;return compare[property](uri,match[4])}else{property=match[1].toLowerCase();if(!comparable[property])return false;return compare[match[2]](uri[property](),match[4],property)}return false}if($.expr.createPseudo)uriSizzle=$.expr.createPseudo(function(text){return function(elem){return uriPseudo(elem,text)}});else uriSizzle=function(elem,i,match){return uriPseudo(elem,match[3])};$.expr[":"].uri=uriSizzle;return $});
!function(e){"use strict";function n(e){if("undefined"==typeof e.length)o(e,"click",t);else if("string"!=typeof e&&!(e instanceof String))for(var n=0;n<e.length;n++)o(e[n],"click",t)}function t(e){var t,o,i,d;return e=e||window.event,t=e.currentTarget||e.srcElement,i=t.getAttribute("href"),i&&(d=e.ctrlKey||e.shiftKey||e.metaKey,o=t.getAttribute("target"),d||o&&!r(o))?(n.open(i),e.preventDefault?e.preventDefault():e.returnValue=!1,!1):void 0}function o(e,n,t){var o,i;return e.addEventListener?e.addEventListener(n,t,!1):(o="on"+n,e.attachEvent?e.attachEvent(o,t):e[o]?(i=e[o],e[o]=function(){t(),i()}):e[o]=t,void 0)}function i(e,n,t){var o,i,r,d,u;return o=document.createElement("iframe"),o.style.display="none",document.body.appendChild(o),i=o.contentDocument||o.contentWindow.document,d='"'+e+'"',n&&(d+=', "'+n+'"'),t&&(d+=', "'+t+'"'),r=i.createElement("script"),r.type="text/javascript",r.text="window.parent = null; window.top = null;window.frameElement = null; var child = window.open("+d+");if (child) { child.opener = null }",i.body.appendChild(r),u=o.contentWindow.child,document.body.removeChild(o),u}function r(e){return"_top"===e||"_self"===e||"_parent"===e}var d;"undefined"!=typeof window&&(d=-1!==window.navigator.userAgent.indexOf("MSIE"));var u;"undefined"!=typeof window&&(u=window.open),n.open=function(e,n,t){var o;return r(n)?u.apply(window,arguments):d?(o=u.apply(window,arguments),o&&(o.opener=null),o):i(e,n,t)},n.patch=function(){window.open=function(){return n.open.apply(this,arguments)}},"undefined"!=typeof exports&&("undefined"!=typeof module&&module.exports?module.exports=n:exports.blankshield=n),"function"==typeof define&&"object"==typeof define.amd&&define("blankshield",[],function(){return n}),e.blankshield=n}(this);
if(typeof window.XE == "undefined") {
	/*jshint -W082 */
	(function($, global) {
		/* OS check */
		var UA = navigator.userAgent.toLowerCase();
		$.os = {
			Linux: /linux/.test(UA),
			Unix: /x11/.test(UA),
			Mac: /mac/.test(UA),
			Windows: /win/.test(UA)
		};
		$.os.name = ($.os.Windows) ? 'Windows' :
			($.os.Linux) ? 'Linux' :
			($.os.Unix) ? 'Unix' :
			($.os.Mac) ? 'Mac' : '';

		var base_url;

		/**
		 * @brief XE 공용 유틸리티 함수
		 * @namespace XE
		 */
		global.XE = {
			loaded_popup_menus : [],
			addedDocument : [],
			URI: global.URI,
			URITemplate : global.URITemplate,
			IPv6: global.IPv6,
			SecondLevelDomains: global.SecondLevelDomains,
			/**
			 * @brief 특정 name을 가진 체크박스들의 checked 속성 변경
			 * @param [itemName='cart',][options={}]
			 */
			checkboxToggleAll : function(itemName) {
				if(!is_def(itemName)) itemName='cart';
				var obj;
				var options = {
					wrap : null,
					checked : 'toggle',
					doClick : false
				};

				switch(arguments.length) {
					case 1:
						if(typeof(arguments[0]) == "string") {
							itemName = arguments[0];
						} else {
							$.extend(options, arguments[0] || {});
							itemName = 'cart';
						}
						break;
					case 2:
						itemName = arguments[0];
						$.extend(options, arguments[1] || {});
				}

				if(options.doClick === true) options.checked = null;
				if(typeof(options.wrap) == "string") options.wrap ='#'+options.wrap;

				if(options.wrap) {
					obj = $(options.wrap).find('input[name="'+itemName+'"]:checkbox');
				} else {
					obj = $('input[name="'+itemName+'"]:checkbox');
				}

				if(options.checked == 'toggle') {
					obj.each(function() {
						$(this).attr('checked', ($(this).attr('checked')) ? false : true);
					});
				} else {
					if(options.doClick === true) {
						obj.click();
					} else {
						obj.attr('checked', options.checked);
					}
				}
			},

			/**
			 * @brief 문서/회원 등 팝업 메뉴 출력
			 */
			displayPopupMenu : function(ret_obj, response_tags, params) {
				var target_srl = params.target_srl;
				var menu_id = params.menu_id;
				var menus = ret_obj.menus;
				var html = "";

				if(this.loaded_popup_menus[menu_id]) {
					html = this.loaded_popup_menus[menu_id];

				} else {
					if(menus) {
						var item = menus.item;
						if(typeof(item.length)=='undefined' || item.length<1) item = new Array(item);
						if(item.length) {
							for(var i=0;i<item.length;i++) {
								var url = item[i].url;
								var str = item[i].str;
								var icon = item[i].icon;
								var target = item[i].target;

								var styleText = "";
								var click_str = "";
								var normalizedUrl = String(url || '').replace(/^\s+|\s+$/g, '');
								if(!/^(?:https?:|\/|#)/i.test(normalizedUrl) && target !== 'javascript') {
									normalizedUrl = '#';
								}
								/* if(icon) styleText = " style=\"background-image:url('"+icon+"')\" "; */
								switch(target) {
									case "popup" :
											click_str = 'onclick="popopen(this.href, \''+target+'\'); return false;"';
										break;
									case "javascript" :
											click_str = 'onclick="'+url+'; return false; "';
											normalizedUrl='#';
										break;
									default :
											click_str = 'target="_blank"';
										break;
								}

								var escapedUrl = $('<div/>').text(normalizedUrl).html();
								var escapedStr = $('<div/>').text(str || '').html();
								html += '<li '+styleText+'><a href="'+escapedUrl+'" '+click_str+'>'+escapedStr+'</a></li> ';
							}
						}
					}
					this.loaded_popup_menus[menu_id] =  html;
				}

				/* 레이어 출력 */
				if(html) {
					var area = $('#popup_menu_area').html('<ul>'+html+'</ul>');
					var areaOffset = {top:params.page_y, left:params.page_x};

					if(area.outerHeight()+areaOffset.top > $(window).height()+$(window).scrollTop())
						areaOffset.top = $(window).height() - area.outerHeight() + $(window).scrollTop();
					if(area.outerWidth()+areaOffset.left > $(window).width()+$(window).scrollLeft())
						areaOffset.left = $(window).width() - area.outerWidth() + $(window).scrollLeft();

					area.css({ top:areaOffset.top, left:areaOffset.left }).show().focus();
				}
			},

			isSameHost: function(url) {
				if(typeof url != "string") return false;

				var target_url = global.XE.URI(url).normalizeHostname().normalizePort().normalizePathname();
				if(target_url.is('urn')) return false;

				var port = [Number(global.http_port) || 80, Number(global.https_port) || 443];

				if(!target_url.hostname()) {
					target_url = target_url.absoluteTo(global.request_uri);
				}

				var target_port = target_url.port();
				if(!target_port) {
					target_port = (target_url.protocol() == 'http') ? 80 : 443;
				}

				if(jQuery.inArray(Number(target_port), port) === -1) {
					return false;
				}

				if(!base_url) {
					base_url = global.XE.URI(global.request_uri).normalizeHostname().normalizePort().normalizePathname();
					base_url = base_url.hostname() + base_url.directory();
				}
				target_url = target_url.hostname() + target_url.directory();

				return target_url.indexOf(base_url) === 0;
			}
		};
	}) (jQuery, window || global);

	/* jQuery(document).ready() */
	(function($, global){
		var isChrome = window.navigator.userAgent.indexOf('Chrome/') > -1;

		$(function() {
		$('a[target]').each(function() {
			var $this = $(this);
			var href = String($this.attr('href')).trim();
			var target = String($this.attr('target')).trim();

			if(!target || !href) return;
			if(!href.match(/^(https?:\/\/)/)) return;

			if(target === '_top' || target === '_self' || target === '_parent') {
				$this.data('noopener', false);
				return;
			}

			if(!global.XE.isSameHost(href)) {
				var rel = $this.attr('rel');

				$this.data('noopener', true);

				if(typeof rel == 'string') {
					$this.attr('rel', rel + ' noopener');
				} else {
					$this.attr('rel', 'noopener');
				}
			}
		});

		$('body').on('click', 'a[target]', function(e) {
			var $this = $(this);
			var href = String($this.attr('href')).trim();

			if(!href) return;
			if(!href.match(/^(https?:\/\/)/)) return;

			if($this.data('noopener') !== false && !window.XE.isSameHost(href)) {
				var rel = $this.attr('rel');

				if(typeof rel == 'string') {
					$this.attr('rel', rel + ' noopener');
				} else {
					$this.attr('rel', 'noopener');
				}

				if(!isChrome) {
					e.preventDefault();
					blankshield.open(href);
				}
			}
		});

		/* select - option의 disabled=disabled 속성을 IE에서도 체크하기 위한 함수 */
		if($.browser.msie) {
			$('select').each(function(i, sels) {
				var disabled_exists = false;
				var first_enable = [];

				for(var j=0; j < sels.options.length; j++) {
					if(sels.options[j].disabled) {
						sels.options[j].style.color = '#CCCCCC';
						disabled_exists = true;
					}else{
						first_enable[i] = (first_enable[i] > -1) ? first_enable[i] : j;
					}
				}

				if(!disabled_exists) return;

				sels.oldonchange = sels.onchange;
				sels.onchange = function() {
					if(this.options[this.selectedIndex].disabled) {

						this.selectedIndex = first_enable[i];
						/*
						if(this.options.length<=1) this.selectedIndex = -1;
						else if(this.selectedIndex < this.options.length - 1) this.selectedIndex++;
						else this.selectedIndex--;
						*/

					} else {
						if(this.oldonchange) this.oldonchange();
					}
				};

				if(sels.selectedIndex >= 0 && sels.options[ sels.selectedIndex ].disabled) sels.onchange();

			});
		}

		/* 단락에디터 fold 컴포넌트 펼치기/접기 */
		var drEditorFold = $('.xe_content .fold_button');
		if(drEditorFold.size()) {
			var fold_container = $('div.fold_container', drEditorFold);
			$('button.more', drEditorFold).click(function() {
				$(this).hide().next('button').show().parent().next(fold_container).show();
			});
			$('button.less', drEditorFold).click(function() {
				$(this).hide().prev('button').show().parent().next(fold_container).hide();
			});
		}

		jQuery('input[type="submit"],button[type="submit"]').click(function(ev){
			var $el = jQuery(ev.currentTarget);

			setTimeout(function(){
				return function(){
					$el.attr('disabled', 'disabled');
				};
			}(), 0);

			setTimeout(function(){
				return function(){
					$el.removeAttr('disabled');
				};
			}(), 3000);
		});
	});
	})(jQuery, window || global);

	(function(global){ // String extension methods
		/**
		 * @brief location.href에서 특정 key의 값을 return
		 **/
		String.prototype.getQuery = function(key) {
			var url = global.XE.URI(this);
			var queries = url.search(true);

			if(typeof queries[key] == 'undefined') {
				return '';
			}

			return queries[key];
		};

		/**
		 * @brief location.href에서 특정 key의 값을 return
		 **/
		String.prototype.setQuery = function(key, val) {
			var uri = global.XE.URI(this);

			if(typeof key != 'undefined') {
				if(typeof val == "undefined" || val === '' || val === null) {
					uri.removeSearch(key);
				} else {
					uri.setSearch(key, String(val));
				}
			}

			return normailzeUri(uri).toString();
		};

		/**
		 * @brief string prototype으로 trim 함수 추가
		 **/
		if(!String.prototype.trim) {
			String.prototype.trim = function() {
				return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
			};
		}

		function normailzeUri(uri) {
			var query = uri.search(true);
			var filename = uri.filename() || 'index.php';
			var protocol = (global.enforce_ssl === true) ? 'https' : 'http';
			var port = 80;

			if(global.XE.isSameHost(uri.toString())) {
				if(jQuery.isEmptyObject(query)) filename = '';
			}

			if(protocol !== 'https' && query.act && jQuery.inArray(query.act, global.ssl_actions) !== -1) {
				protocol = 'https';
			}

			port = (protocol === 'http') ? global.http_port : global.https_port;

			return uri.protocol(protocol)
				.port(port || null)
				.filename(filename)
				.normalizePort();
		}
		/**
		 * @brief 문자열의 HTML 특수문자를 이스케이프
		 **/
		String.prototype.escape = function() {
			return this
				.replace(/&amp;(amp|lt|gt|quot|#39);/g, '&$1;')
				.replace(/&(?!amp;|lt;|gt;|quot;|#39;)/g, '&amp;')
				.replace(/[<>"']/g, function(match) {
					switch (match) {
						case '<': return '&lt;';
						case '>': return '&gt;';
						case '"': return '&quot;';
						case "'": return '&#39;';
					}
				});
		};
	})(window || global);

	/**
	 * @brief xSleep(micro time)
	 **/
	function xSleep(sec) {
		sec = sec / 1000;
		var now = new Date();
		var sleep = new Date();
		while( sleep.getTime() - now.getTime() < sec) {
			sleep = new Date();
		}
	}

	/**
	 * @brief 주어진 인자가 하나라도 defined되어 있지 않으면 false return
	 **/
	function isDef() {
		for(var i=0; i < arguments.length; ++i) {
			if(typeof(arguments[i]) == "undefined") return false;
		}
		return true;
	}

	/**
	 * @brief 윈도우 오픈
	 * 열려진 윈도우의 관리를 통해 window.focus()등을 FF에서도 비슷하게 구현함
	 **/
	var winopen_list = [];
	function winopen(url, target, attribute) {
		if(typeof xeVid != 'undefined' && url.indexOf(request_uri) >- 1 && !url.getQuery('vid')) {
			url = url.setQuery('vid',xeVid);
		}

		try {
			if(target != '_blank' && winopen_list[target]) {
				winopen_list[target].close();
				winopen_list[target] = null;
			}
		} catch(e) {
		}

		if(typeof target == 'undefined') target = '_blank';
		if(typeof attribute == 'undefined') attribute = '';

		var win;

		if(!window.XE.isSameHost(url)) {
			win = window.open(url, target, attribute);
			if(win) {
				win.opener = null;
			}
		} else {
			win = window.open(url, target, attribute);
			if(win) {
				win.focus();
			}
			if(target != '_blank') winopen_list[target] = win;
		}

	}

	/**
	 * @brief 팝업으로만 띄우기
	 * common/tpl/popup_layout.html이 요청되는 XE내의 팝업일 경우에 사용
	 **/
	function popopen(url, target) {
		winopen(url, target, "width=800,height=600,scrollbars=yes,resizable=yes,toolbars=no");
	}

	/**
	 * @brief 메일 보내기용
	 **/
	function sendMailTo(to) {
		location.href="mailto:"+to;
	}

	/**
	 * @brief url이동 (open_window 값이 N 가 아니면 새창으로 띄움)
	 **/
	function move_url(url, open_window) {
		if(!url) return false;

		if(/^\./.test(url)) url = window.request_uri + url;

		if(typeof open_window == 'undefined' || open_window == 'N') {
			location.href = url;
		} else {
			winopen(url);
		}

		return false;
	}

	/**
	 * @brief 멀티미디어 출력용 (IE에서 플래쉬/동영상 주변에 점선 생김 방지용)
	 **/
	function displayMultimedia(src, width, height, options) {
		/*jslint evil: true */
		var html = _displayMultimedia(src, width, height, options);
		if(html) document.writeln(html);
	}
	function _displayMultimedia(src, width, height, options) {
		if(src.indexOf('files') === 0) src = request_uri + src;

		var defaults = {
			wmode : 'transparent',
			allowScriptAccess : 'never',
			quality : 'high',
			flashvars : '',
			autostart : false
		};

		var params = jQuery.extend(defaults, options || {});
		var autostart = (params.autostart && params.autostart != 'false') ? 'true' : 'false';
		delete(params.autostart);

		var clsid = "";
		var codebase = "";
		var html = "";

		if(/\.(gif|jpg|jpeg|bmp|png)$/i.test(src)){
			html = '<img src="'+src+'" width="'+width+'" height="'+height+'" />';
		} else if(/\.flv$/i.test(src) || /\.mov$/i.test(src) || /\.moov$/i.test(src) || /\.m4v$/i.test(src)) {
			html = '<embed src="'+request_uri+'common/img/flvplayer.swf" allowfullscreen="true" allowscriptaccess="never" autostart="'+autostart+'" width="'+width+'" height="'+height+'" flashvars="&file='+src+'&width='+width+'&height='+height+'&autostart='+autostart+'" wmode="'+params.wmode+'" />';
		} else if(/\.swf/i.test(src)) {
			clsid = 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000';

			if(typeof(enforce_ssl)!='undefined' && enforce_ssl){ codebase = "https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0"; }
			else { codebase = "http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0"; }
			html = '<object classid="'+clsid+'" codebase="'+codebase+'" width="'+width+'" height="'+height+'" flashvars="'+params.flashvars+'">';
			html += '<param name="movie" value="'+src+'" />';
			for(var name in params) {
				if(params[name] != 'undefined' && params[name] !== '') {
					html += '<param name="'+name+'" value="'+params[name]+'" />';
				}
			}
			html += '' + '<embed src="'+src+'" allowscriptaccess="never" autostart="'+autostart+'"  width="'+width+'" height="'+height+'" flashvars="'+params.flashvars+'" wmode="'+params.wmode+'"></embed>' + '</object>';
		}  else {
			if (jQuery.browser.mozilla || jQuery.browser.opera) {
				// firefox and opera uses 0 or 1 for autostart parameter.
				autostart = (params.autostart && params.autostart != 'false') ? '1' : '0';
			}

			html = '<embed src="'+src+'" allowscriptaccess="never" autostart="'+autostart+'" width="'+width+'" height="'+height+'"';
			if(params.wmode == 'transparent') {
				html += ' windowlessvideo="1"';
			}
			html += '></embed>';
		}
		return html;
	}

	/**
	 * @brief 에디터에서 사용되는 내용 여닫는 코드 (고정, zbxe용)
	 **/
	function zbxe_folder_open(id) {
		jQuery("#folder_open_"+id).hide();
		jQuery("#folder_close_"+id).show();
		jQuery("#folder_"+id).show();
	}
	function zbxe_folder_close(id) {
		jQuery("#folder_open_"+id).show();
		jQuery("#folder_close_"+id).hide();
		jQuery("#folder_"+id).hide();
	}

	/**
	 * @brief 팝업의 경우 내용에 맞춰 현 윈도우의 크기를 조절해줌
	 * 팝업의 내용에 맞게 크기를 늘리는 것은... 쉽게 되지는 않음.. ㅡ.ㅜ
	 * popup_layout 에서 window.onload 시 자동 요청됨.
	 **/
	function setFixedPopupSize() {
		var $ = jQuery, $win = $(window), $pc = $('body>.popup'), w, h, dw, dh, offset;

		offset = $pc.css({overflow:'scroll'}).offset();

		w = $pc.width(10).height(10000).get(0).scrollWidth + offset.left*2;
		h = $pc.height(10).width(10000).get(0).scrollHeight + offset.top*2;

		if(w < 800) w = 800 + offset.left*2;

		dw = $win.width();
		dh = $win.height();

		if(w != dw) window.resizeBy(w - dw, 0);
		if(h != dh) window.resizeBy(0, h - dh);

		$pc.width(w-offset.left*2).css({overflow:'',height:''});
	}

	/**
	 * @brief 추천/비추천,스크랩,신고기능등 특정 srl에 대한 특정 module/action을 호출하는 함수
	 **/
	function doCallModuleAction(module, action, target_srl) {
		var params = {
			target_srl : target_srl,
			cur_mid    : current_mid,
			mid        : current_mid
		};
		exec_xml(module, action, params, completeCallModuleAction);
	}

	function completeCallModuleAction(ret_obj, response_tags) {
		if(ret_obj.message!='success') alert(ret_obj.message);
		location.reload();
	}

	function completeMessage(ret_obj) {
		alert(ret_obj.message);
		location.reload();
	}



	/* 언어코드 (lang_type) 쿠키값 변경 */
	function doChangeLangType(obj) {
		if(typeof(obj) == "string") {
			setLangType(obj);
		} else {
			var val = obj.options[obj.selectedIndex].value;
			setLangType(val);
		}
		location.href = location.href.setQuery('l', '');
	}
	function setLangType(lang_type) {
		var expire = new Date();
		expire.setTime(expire.getTime()+ (7000 * 24 * 3600000));
		setCookie('lang_type', lang_type, expire, '/');
	}

	/* 미리보기 */
	function doDocumentPreview(obj) {
		var fo_obj = obj;
		while(fo_obj.nodeName != "FORM") {
			fo_obj = fo_obj.parentNode;
		}
		if(fo_obj.nodeName != "FORM") return;
		var editor_sequence = fo_obj.getAttribute('editor_sequence');

		var content = editorGetContent(editor_sequence);

		var win = window.open("", "previewDocument","toolbars=no,width=700px;height=800px,scrollbars=yes,resizable=yes");

		var dummy_obj = jQuery("#previewDocument");

		if(!dummy_obj.length) {
			jQuery(
				'<form id="previewDocument" target="previewDocument" method="post" action="'+request_uri+'">'+
				'<input type="hidden" name="module" value="document" />'+
				'<input type="hidden" name="act" value="dispDocumentPreview" />'+
				'<input type="hidden" name="content" />'+
				'</form>'
			).appendTo(document.body);

			dummy_obj = jQuery("#previewDocument")[0];
		} else {
			dummy_obj = dummy_obj[0];
		}

		if(dummy_obj) {
			dummy_obj.content.value = content;
			dummy_obj.submit();
		}
	}

	/* 게시글 저장 */
	function doDocumentSave(obj) {
		var editor_sequence = obj.form.getAttribute('editor_sequence');
		var prev_content = editorRelKeys[editor_sequence].content.value;
		if(typeof(editor_sequence)!='undefined' && editor_sequence && typeof(editorRelKeys)!='undefined' && typeof(editorGetContent)=='function') {
			var content = editorGetContent(editor_sequence);
			editorRelKeys[editor_sequence].content.value = content;
		}

		var params={}, responses=['error','message','document_srl'], elms=obj.form.elements, data=jQuery(obj.form).serializeArray();
		jQuery.each(data, function(i, field){
			var val = jQuery.trim(field.value);
			if(!val) return true;
			if(/\[\]$/.test(field.name)) field.name = field.name.replace(/\[\]$/, '');
			if(params[field.name]) params[field.name] += '|@|'+val;
			else params[field.name] = field.value;
		});

		exec_xml('document','procDocumentTempSave', params, completeDocumentSave, responses, params, obj.form);

		editorRelKeys[editor_sequence].content.value = prev_content;
		return false;
	}

	function completeDocumentSave(ret_obj) {
		jQuery('input[name=document_srl]').eq(0).val(ret_obj.document_srl);
		alert(ret_obj.message);
	}

	/* 저장된 게시글 불러오기 */
	var objForSavedDoc = null;
	function doDocumentLoad(obj) {
		// 저장된 게시글 목록 불러오기
		objForSavedDoc = obj.form;
		popopen(request_uri.setQuery('module','document').setQuery('act','dispTempSavedList'));
	}

	/* 저장된 게시글의 선택 */
	function doDocumentSelect(document_srl, module) {
		if(!opener || !opener.objForSavedDoc) {
			window.close();
			return;
		}

		if(module===undefined) {
			module = 'document';
		}

		// 게시글을 가져와서 등록하기
		switch(module) {
			case 'page' :
				var url = opener.current_url;
				url = url.setQuery('document_srl', document_srl);

				if(url.getQuery('act') === 'dispPageAdminMobileContentModify')
				{
					url = url.setQuery('act', 'dispPageAdminMobileContentModify');
				}
				else
				{
					url = url.setQuery('act', 'dispPageAdminContentModify');
				}
				opener.location.href = url;
				break;
			default :
				opener.location.href = opener.current_url.setQuery('document_srl', document_srl).setQuery('act', 'dispBoardWrite');
				break;
		}
		window.close();
	}


	/* 스킨 정보 */
	function viewSkinInfo(module, skin) {
		popopen("./?module=module&act=dispModuleSkinInfo&selected_module="+module+"&skin="+skin, 'SkinInfo');
	}


	/* 관리자가 문서를 관리하기 위해서 선택시 세션에 넣음 */
	var addedDocument = [];
	function doAddDocumentCart(obj) {
		var srl = obj.value;
		addedDocument[addedDocument.length] = srl;
		setTimeout(function() { callAddDocumentCart(addedDocument.length); }, 100);
	}

	function callAddDocumentCart(document_length) {
		if(addedDocument.length<1 || document_length != addedDocument.length) return;
		var params = [];
		params.srls = addedDocument.join(",");
		exec_xml("document","procDocumentAddCart", params, null);
		addedDocument = [];
	}

	/* ff의 rgb(a,b,c)를 #... 로 변경 */
	function transRGB2Hex(value) {
		if(!value) return value;
		if(value.indexOf('#') > -1) return value.replace(/^#/, '');

		if(value.toLowerCase().indexOf('rgb') < 0) return value;
		value = value.replace(/^rgb\(/i, '').replace(/\)$/, '');
		value_list = value.split(',');

		var hex = '';
		for(var i = 0; i < value_list.length; i++) {
			var color = parseInt(value_list[i], 10).toString(16);
			if(color.length == 1) color = '0'+color;
			hex += color;
		}
		return hex;
	}

	/* 보안 로그인 모드로 전환 */
	function toggleSecuritySignIn() {
		var href = location.href;
		if(/https:\/\//i.test(href)) location.href = href.replace(/^https/i,'http');
		else location.href = href.replace(/^http/i,'https');
	}

	function reloadDocument() {
		location.reload();
	}


	/**
	*
	* Base64 encode / decode
	* http://www.webtoolkit.info/
	*
	**/

	var Base64 = {

		// private property
		_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

		// public method for encoding
		encode : function (input) {
			var output = "";
			var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
			var i = 0;

			input = Base64._utf8_encode(input);

			while (i < input.length) {

				chr1 = input.charCodeAt(i++);
				chr2 = input.charCodeAt(i++);
				chr3 = input.charCodeAt(i++);

				enc1 = chr1 >> 2;
				enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
				enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
				enc4 = chr3 & 63;

				if (isNaN(chr2)) {
					enc3 = enc4 = 64;
				} else if (isNaN(chr3)) {
					enc4 = 64;
				}

				output = output +
				this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
				this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

			}

			return output;
		},

		// public method for decoding
		decode : function (input) {
			var output = "";
			var chr1, chr2, chr3;
			var enc1, enc2, enc3, enc4;
			var i = 0;

			input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

			while (i < input.length) {
				enc1 = this._keyStr.indexOf(input.charAt(i++));
				enc2 = this._keyStr.indexOf(input.charAt(i++));
				enc3 = this._keyStr.indexOf(input.charAt(i++));
				enc4 = this._keyStr.indexOf(input.charAt(i++));

				chr1 = (enc1 << 2) | (enc2 >> 4);
				chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
				chr3 = ((enc3 & 3) << 6) | enc4;

				output = output + String.fromCharCode(chr1);

				if (enc3 != 64) {
					output = output + String.fromCharCode(chr2);
				}
				if (enc4 != 64) {
					output = output + String.fromCharCode(chr3);
				}
			}

			output = Base64._utf8_decode(output);

			return output;

		},

		// private method for UTF-8 encoding
		_utf8_encode : function (string) {
			string = string.replace(/\r\n/g,"\n");
			var utftext = "";

			for (var n = 0; n < string.length; n++) {
				var c = string.charCodeAt(n);

				if (c < 128) {
					utftext += String.fromCharCode(c);
				}
				else if((c > 127) && (c < 2048)) {
					utftext += String.fromCharCode((c >> 6) | 192);
					utftext += String.fromCharCode((c & 63) | 128);
				}
				else {
					utftext += String.fromCharCode((c >> 12) | 224);
					utftext += String.fromCharCode(((c >> 6) & 63) | 128);
					utftext += String.fromCharCode((c & 63) | 128);
				}
			}

			return utftext;
		},

		// private method for UTF-8 decoding
		_utf8_decode : function (utftext) {
			var string = "";
			var i = 0;
			var c = 0, c1 = 0, c2 = 0, c3 = 0;

			while ( i < utftext.length ) {
				c = utftext.charCodeAt(i);

				if (c < 128) {
					string += String.fromCharCode(c);
					i++;
				}
				else if((c > 191) && (c < 224)) {
					c2 = utftext.charCodeAt(i+1);
					string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
					i += 2;
				}
				else {
					c2 = utftext.charCodeAt(i+1);
					c3 = utftext.charCodeAt(i+2);
					string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
					i += 3;
				}
			}

			return string;
		}
	};






	/* ----------------------------------------------
	 * DEPRECATED
	 * 하위호환용으로 남겨 놓음
	 * ------------------------------------------- */

	if(typeof(resizeImageContents) == 'undefined') {
		window.resizeImageContents = function() {};
	}

	if(typeof(activateOptionDisabled) == 'undefined') {
		window.activateOptionDisabled = function() {};
	}

	var objectExtend = jQuery.extend;

	/**
	 * @brief 특정 Element의 display 옵션 토글
	 **/
	function toggleDisplay(objId) {
		jQuery('#'+objId).toggle();
	}

	/**
	 * @brief 에디터에서 사용하되 내용 여닫는 코드 (zb5beta beta 호환용으로 남겨 놓음)
	 **/
	function svc_folder_open(id) {
		jQuery("#_folder_open_"+id).hide();
		jQuery("#_folder_close_"+id).show();
		jQuery("#_folder_"+id).show();
	}
	function svc_folder_close(id) {
		jQuery("#_folder_open_"+id).show();
		jQuery("#_folder_close_"+id).hide();
		jQuery("#_folder_"+id).hide();
	}

	/**
	 * @brief 날짜 선택 (달력 열기)
	 **/
	function open_calendar(fo_id, day_str, callback_func) {
		if(typeof(day_str)=="undefined") day_str = "";

		var url = "./common/tpl/calendar.php?";
		if(fo_id) url+="fo_id="+fo_id;
		if(day_str) url+="&day_str="+day_str;
		if(callback_func) url+="&callback_func="+callback_func;

		popopen(url, 'Calendar');
	}

	var loaded_popup_menus = XE.loaded_popup_menus;
	function createPopupMenu() {}
	function chkPopupMenu() {}
	function displayPopupMenu(ret_obj, response_tags, params) {
		XE.displayPopupMenu(ret_obj, response_tags, params);
	}

	function GetObjLeft(obj) {
		return jQuery(obj).offset().left;
	}
	function GetObjTop(obj) {
		return jQuery(obj).offset().top;
	}

	function replaceOuterHTML(obj, html) {
		jQuery(obj).replaceWith(html);
	}

	function getOuterHTML(obj) {
		return jQuery(obj).html().trim();
	}

	function setCookie(name, value, expire, path) {
		var s_cookie = name + "=" + escape(value) +
			((!expire) ? "" : ("; expires=" + expire.toGMTString())) +
			"; path=" + ((!path) ? "/" : path);

		document.cookie = s_cookie;
	}

	function getCookie(name) {
		var match = document.cookie.match(new RegExp(name+'=(.*?)(?:;|$)'));
		if(match) return unescape(match[1]);
	}

	function is_def(v) {
		return (typeof(v)!='undefined');
	}

	function ucfirst(str) {
		return str.charAt(0).toUpperCase() + str.slice(1);
	}

	function get_by_id(id) {
		return document.getElementById(id);
	}

	jQuery(function($){
		// display popup menu that contains member actions and document actions
		$(document).on('click', function(evt) {
			var $area = $('#popup_menu_area');
			if(!$area.length) $area = $('<div id="popup_menu_area" tabindex="0" style="display:none;z-index:9999" />').appendTo(document.body);

			// 이전에 호출되었을지 모르는 팝업메뉴 숨김
			$area.hide();

			var $target = $(evt.target).filter('a,div,span');
			if(!$target.length) $target = $(evt.target).closest('a,div,span');
			if(!$target.length) return;

			// 객체의 className값을 구함
			var cls = $target.attr('class'), match;
			if(cls) match = cls.match(new RegExp('(?:^| )((document|comment|member)_([1-9]\\d*))(?: |$)',''));
			if(!match) return;

			// mobile에서 touchstart에 의한 동작 시 pageX, pageY 위치를 구함
			if(evt.pageX===undefined || evt.pageY===undefined)
			{
				var touch = evt.originalEvent.touches[0];
				if(touch!==undefined || !touch)
				{
					touch = evt.originalEvent.changedTouches[0];
				}
				evt.pageX = touch.pageX;
				evt.pageY = touch.pageY;
			}

			var action = 'get'+ucfirst(match[2])+'Menu';
			var params = {
				mid        : current_mid,
				cur_mid    : current_mid,
				menu_id    : match[1],
				target_srl : match[3],
				cur_act    : current_url.getQuery('act'),
				page_x     : evt.pageX,
				page_y     : evt.pageY
			};
			var response_tags = 'error message menus'.split(' ');

			// prevent default action
			evt.preventDefault();
			evt.stopPropagation();

			if(is_def(window.xeVid)) params.vid = xeVid;
			if(is_def(XE.loaded_popup_menus[params.menu_id])) return XE.displayPopupMenu(params, response_tags, params);

			show_waiting_message = false;
			exec_xml('member', action, params, XE.displayPopupMenu, response_tags, params);
			show_waiting_message = true;
		});

		/**
		 * Create popup windows automatically.
		 * Find anchors that have the '_xe_popup' class, then add popup script to them.
		 */
		$('body').on('click', 'a._xe_popup', function(event) {
			var $this = $(this);
			var name = $this.attr('name');
			var href = $this.attr('href');
			var win;

			if(!name) name = '_xe_popup_' + Math.floor(Math.random() * 1000);

			var features = 'left=10,top=10,width=10,height=10,resizable=no,scrollbars=no,toolbars=no';

			if(window.XE.isSameHost(href)) {
				win = window.open(href, name, features);
				if(win) win.focus();
			} else {
				win = window.open(href, name, features);
				if(win) win.opener = null;
			}

			event.preventDefault();
			return false;
		});

		// date picker default settings
		if($.datepicker) {
			$.datepicker.setDefaults({
				dateFormat : 'yy-mm-dd'
			});
		}
	});
}

(function($){
	var _xe_base, _app_base, _plugin_base;
	var _apps = [];

	_xe_base = {
		/**
		 * @brief return the name of Core module
		 */
		getName : function() {
			return 'Core';
		},

		/**
		 * @brief Create an application class
		 */
		createApp : function(sName, oDef) {
			var _base = getTypeBase();

			$.extend(_base.prototype, _app_base, oDef);

			_base.prototype.getName = function() {
				return sName;
			};

			return _base;
		},

		/**
		 * @brief Create a plugin class
		 */
		createPlugin : function(sName, oDef) {
			var _base = getTypeBase();

			$.extend(_base.prototype, _plugin_base, oDef);

			_base.prototype.getName = function() {
				return sName;
			};

			return _base;
		},

		/**
		 * @brief Get the array of applications
		 */
		getApps : function() {
			return $.makeArray(_apps);
		},

		/**
		 * @brief Get one application
		 */
		getApp : function(indexOrName) {
			indexOrName = (indexOrName||'').toLowerCase();
			if(typeof _apps[indexOrName] != 'undefined') {
				return _apps[indexOrName];
			} else {
				return null;
			}
		},

		/**
		 * @brief Register an application instance
		 */
		registerApp : function(oApp) {
			var sName = oApp.getName().toLowerCase();

			_apps.push(oApp);
			if (!$.isArray(_apps[sName])) {
				_apps[sName] = [];
			}
			_apps[sName].push(oApp);

			oApp.parent = this;

			// register event
			if ($.isFunction(oApp.activate)) oApp.activate();
		},

		/**
		 * @brief Unregister an application instance
		 */
		unregisterApp : function(oApp) {
			var sName  = oApp.getName().toLowerCase();
			var nIndex = $.inArray(oApp, _apps);

			if (nIndex >= 0) _apps.splice(nIndex, 1);

			if ($.isArray(_apps[sName])) {
				nIndex = $.inArray(oApp, _apps[sName]);
				if (nIndex >= 0) _apps[sName].splice(nIndex, 1);
			}

			// unregister event
			if ($.isFunction(oApp.deactivate)) oApp.deactivate();
		},

		/**
		 * @brief overrides broadcast method
		 */
		broadcast : function(msg, params) {
			this._broadcast(this, msg, params);
		},

		_broadcast : function(sender, msg, params) {
			for(var i=0; i < _apps.length; i++) {
				_apps[i]._cast(sender, msg, params);
			}


			// cast to child plugins
			this._cast(sender, msg, params);
		}
	};

	_app_base = {
		_plugins  : [],
		_messages : {},

		/**
		 * @brief get plugin
		 */
		getPlugin : function(sPluginName) {
			sPluginName = sPluginName.toLowerCase();
			if ($.isArray(this._plugins[sPluginName])) {
				return this._plugins[sPluginName];
			} else {
				return [];
			}
		},

		/**
		 * @brief register a plugin instance
		 */
		registerPlugin : function(oPlugin) {
			var self  = this;
			var sName = oPlugin.getName().toLowerCase();

			// check if the plugin is already registered
			if ($.inArray(oPlugin, this._plugins) >= 0) return false;

			// push the plugin into the _plugins array
			this._plugins.push(oPlugin);

			if (!$.isArray(this._plugins[sName])) this._plugins[sName] = [];
			this._plugins[sName].push(oPlugin);

			// register method pool
			$.each(oPlugin._binded_fn, function(api, fn){ self.registerHandler(api, fn); });

			// binding
			oPlugin.oApp = this;

			// registered event
			if ($.isFunction(oPlugin.activate)) oPlugin.activate();

			return true;
		},

		/**
		 * @brief register api message handler
		 */
		registerHandler : function(api, func) {
			var msgs = this._messages; api = api.toUpperCase();
			if (!$.isArray(msgs[api])) msgs[api] = [];
			msgs[api].push(func);
		},

		cast : function(msg, params) {
			return this._cast(this, msg, params || []);
		},

		broadcast : function(sender, msg, params) {
			if (this.parent && this.parent._broadcast) {
				this.parent._broadcast(sender, msg, params);
			}
		},

		_cast : function(sender, msg, params) {
			var i, len;
			var aMsg = this._messages;

			msg = msg.toUpperCase();

			// BEFORE hooker
			if (aMsg['BEFORE_'+msg] || this['API_BEFORE_'+msg]) {
				var bContinue = this._cast(sender, 'BEFORE_'+msg, params);
				if (!bContinue) return;
			}

			// main api function
			var vRet = [], sFn = 'API_'+msg;
			if ($.isArray(aMsg[msg])) {
				for(i=0; i < aMsg[msg].length; i++) {
					vRet.push( aMsg[msg][i](sender, params) );
				}
			}
			if (vRet.length < 2) vRet = vRet[0];

			// AFTER hooker
			if (aMsg['AFTER_'+msg] || this['API_AFTER_'+msg]) {
				this._cast(sender, 'AFTER_'+msg, params);
			}

			if (!/^(?:AFTER|BEFORE)_/.test(msg)) { // top level function
				return vRet;
			} else {
				return $.isArray(vRet)?($.inArray(false, vRet)<0):((typeof vRet=='undefined')?true:!!vRet);
			}
		}
	};

	_plugin_base = {
		oApp : null,

		cast : function(msg, params) {
			if (this.oApp && this.oApp._cast) {
				return this.oApp._cast(this, msg, params || []);
			}
		},

		broadcast : function(msg, params) {
			if (this.oApp && this.oApp.broadcast) {
				this.oApp.broadcast(this, msg, params || []);
			}
		}
	};

	function getTypeBase() {
		var _base = function() {
			var self = this;
			var pool = null;

			if ($.isArray(this._plugins)) this._plugins   = [];
			if (this._messages) this._messages = {};
			else this._binded_fn = {};

			// bind functions
			$.each(this, function(key, val){
				if (!$.isFunction(val)) return true;
				if (!/^API_([A-Z0-9_]+)$/.test(key)) return true;

				var api = RegExp.$1;
				var fn  = function(sender, params){ return self[key](sender, params); };

				if (self._messages) self._messages[api] = [fn];
				else self._binded_fn[api] = fn;
			});

			if ($.isFunction(this.init)) this.init.apply(this, arguments);
		};

		return _base;
	}

	window.xe = $.extend(_app_base, _xe_base);
	window.xe.lang = {}; // language repository

	// domready event
	$(function(){ xe.broadcast('ONREADY'); });

	// load event
	$(window).load(function(){ xe.broadcast('ONLOAD'); });
})(jQuery);

(function (root, factory) {
     if (typeof define === "function" && define.amd) {
         define([], factory);
     } else if (typeof exports === "object") {
         module.exports = factory();
     } else {
         root.X2JS = factory();
     }
 }(this, function () {
	return function (config) {
		'use strict';

		var VERSION = "1.2.0";

		config = config || {};
		initConfigDefaults();
		initRequiredPolyfills();

		function initConfigDefaults() {
			if(config.escapeMode === undefined) {
				config.escapeMode = true;
			}

			config.attributePrefix = config.attributePrefix || "_";
			config.arrayAccessForm = config.arrayAccessForm || "none";
			config.emptyNodeForm = config.emptyNodeForm || "text";

			if(config.enableToStringFunc === undefined) {
				config.enableToStringFunc = true;
			}
			config.arrayAccessFormPaths = config.arrayAccessFormPaths || [];
			if(config.skipEmptyTextNodesForObj === undefined) {
				config.skipEmptyTextNodesForObj = true;
			}
			if(config.stripWhitespaces === undefined) {
				config.stripWhitespaces = true;
			}
			config.datetimeAccessFormPaths = config.datetimeAccessFormPaths || [];

			if(config.useDoubleQuotes === undefined) {
				config.useDoubleQuotes = false;
			}

			config.xmlElementsFilter = config.xmlElementsFilter || [];
			config.jsonPropertiesFilter = config.jsonPropertiesFilter || [];

			if(config.keepCData === undefined) {
				config.keepCData = false;
			}
		}

		var DOMNodeTypes = {
			ELEMENT_NODE 	   : 1,
			TEXT_NODE    	   : 3,
			CDATA_SECTION_NODE : 4,
			COMMENT_NODE	   : 8,
			DOCUMENT_NODE 	   : 9
		};

		function initRequiredPolyfills() {
		}

		function getNodeLocalName( node ) {
			var nodeLocalName = node.localName;
			if(nodeLocalName == null) // Yeah, this is IE!!
				nodeLocalName = node.baseName;
			if(nodeLocalName == null || nodeLocalName=="") // =="" is IE too
				nodeLocalName = node.nodeName;
			return nodeLocalName;
		}

		function getNodePrefix(node) {
			return node.prefix;
		}

		function escapeXmlChars(str) {
			if(typeof(str) == "string")
				return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
			else
				return str;
		}

		function unescapeXmlChars(str) {
			return str.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'").replace(/&amp;/g, '&');
		}

		function checkInStdFiltersArrayForm(stdFiltersArrayForm, obj, name, path) {
			var idx = 0;
			for(; idx < stdFiltersArrayForm.length; idx++) {
				var filterPath = stdFiltersArrayForm[idx];
				if( typeof filterPath === "string" ) {
					if(filterPath == path)
						break;
				}
				else
				if( filterPath instanceof RegExp) {
					if(filterPath.test(path))
						break;
				}
				else
				if( typeof filterPath === "function") {
					if(filterPath(obj, name, path))
						break;
				}
			}
			return idx!=stdFiltersArrayForm.length;
		}

		function toArrayAccessForm(obj, childName, path) {
			switch(config.arrayAccessForm) {
				case "property":
					if(!(obj[childName] instanceof Array))
						obj[childName+"_asArray"] = [obj[childName]];
					else
						obj[childName+"_asArray"] = obj[childName];
					break;
				/*case "none":
					break;*/
			}

			if(!(obj[childName] instanceof Array) && config.arrayAccessFormPaths.length > 0) {
				if(checkInStdFiltersArrayForm(config.arrayAccessFormPaths, obj, childName, path)) {
					obj[childName] = [obj[childName]];
				}
			}
		}

		function fromXmlDateTime(prop) {
			// Implementation based up on http://stackoverflow.com/questions/8178598/xml-datetime-to-javascript-date-object
			// Improved to support full spec and optional parts
			var bits = prop.split(/[-T:+Z]/g);

			var d = new Date(bits[0], bits[1]-1, bits[2]);
			var secondBits = bits[5].split("\.");
			d.setHours(bits[3], bits[4], secondBits[0]);
			if(secondBits.length>1)
				d.setMilliseconds(secondBits[1]);

			// Get supplied time zone offset in minutes
			if(bits[6] && bits[7]) {
				var offsetMinutes = bits[6] * 60 + Number(bits[7]);
				var sign = /\d\d-\d\d:\d\d$/.test(prop)? '-' : '+';

				// Apply the sign
				offsetMinutes = 0 + (sign == '-'? -1 * offsetMinutes : offsetMinutes);

				// Apply offset and local timezone
				d.setMinutes(d.getMinutes() - offsetMinutes - d.getTimezoneOffset())
			}
			else
				if(prop.indexOf("Z", prop.length - 1) !== -1) {
					d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate(), d.getHours(), d.getMinutes(), d.getSeconds(), d.getMilliseconds()));
				}

			// d is now a local time equivalent to the supplied time
			return d;
		}

		function checkFromXmlDateTimePaths(value, childName, fullPath) {
			if(config.datetimeAccessFormPaths.length > 0) {
				var path = fullPath.split("\.#")[0];
				if(checkInStdFiltersArrayForm(config.datetimeAccessFormPaths, value, childName, path)) {
					return fromXmlDateTime(value);
				}
				else
					return value;
			}
			else
				return value;
		}

		function checkXmlElementsFilter(obj, childType, childName, childPath) {
			if( childType == DOMNodeTypes.ELEMENT_NODE && config.xmlElementsFilter.length > 0) {
				return checkInStdFiltersArrayForm(config.xmlElementsFilter, obj, childName, childPath);
			}
			else
				return true;
		}

		function parseDOMChildren( node, path ) {
			if(node.nodeType == DOMNodeTypes.DOCUMENT_NODE) {
				var result = new Object;
				var nodeChildren = node.childNodes;
				// Alternative for firstElementChild which is not supported in some environments
				for(var cidx=0; cidx <nodeChildren.length; cidx++) {
					var child = nodeChildren.item(cidx);
					if(child.nodeType == DOMNodeTypes.ELEMENT_NODE) {
						var childName = getNodeLocalName(child);
						result[childName] = parseDOMChildren(child, childName);
					}
				}
				return result;
			}
			else
			if(node.nodeType == DOMNodeTypes.ELEMENT_NODE) {
				var result = new Object;
				result.__cnt=0;

				var nodeChildren = node.childNodes;

				// Children nodes
				for(var cidx=0; cidx <nodeChildren.length; cidx++) {
					var child = nodeChildren.item(cidx); // nodeChildren[cidx];
					var childName = getNodeLocalName(child);

					if(child.nodeType!= DOMNodeTypes.COMMENT_NODE) {
						var childPath = path+"."+childName;
						if (checkXmlElementsFilter(result,child.nodeType,childName,childPath)) {
							result.__cnt++;
							if(result[childName] == null) {
								result[childName] = parseDOMChildren(child, childPath);
								toArrayAccessForm(result, childName, childPath);
							}
							else {
								if(result[childName] != null) {
									if( !(result[childName] instanceof Array)) {
										result[childName] = [result[childName]];
										toArrayAccessForm(result, childName, childPath);
									}
								}
								(result[childName])[result[childName].length] = parseDOMChildren(child, childPath);
							}
						}
					}
				}

				// Attributes
				for(var aidx=0; aidx <node.attributes.length; aidx++) {
					var attr = node.attributes.item(aidx); // [aidx];
					result.__cnt++;
					result[config.attributePrefix+attr.name]=attr.value;
				}

				// Node namespace prefix
				var nodePrefix = getNodePrefix(node);
				if(nodePrefix!=null && nodePrefix!="") {
					result.__cnt++;
					result.__prefix=nodePrefix;
				}

				if(result["#text"]!=null) {
					result.__text = result["#text"];
					if(result.__text instanceof Array) {
						result.__text = result.__text.join("\n");
					}
					//if(config.escapeMode)
					//	result.__text = unescapeXmlChars(result.__text);
					if(config.stripWhitespaces)
						result.__text = result.__text.trim();
					delete result["#text"];
					if(config.arrayAccessForm=="property")
						delete result["#text_asArray"];
					result.__text = checkFromXmlDateTimePaths(result.__text, childName, path+"."+childName);
				}
				if(result["#cdata-section"]!=null) {
					result.__cdata = result["#cdata-section"];
					delete result["#cdata-section"];
					if(config.arrayAccessForm=="property")
						delete result["#cdata-section_asArray"];
				}

				if( result.__cnt == 0 && config.emptyNodeForm=="text" ) {
					result = '';
				}
				else
				if( result.__cnt == 1 && result.__text!=null  ) {
					result = result.__text;
				}
				else
				if( result.__cnt == 1 && result.__cdata!=null && !config.keepCData  ) {
					result = result.__cdata;
				}
				else
				if ( result.__cnt > 1 && result.__text!=null && config.skipEmptyTextNodesForObj) {
					if( (config.stripWhitespaces && result.__text=="") || (result.__text.trim()=="")) {
						delete result.__text;
					}
				}
				delete result.__cnt;

				if( config.enableToStringFunc && (result.__text!=null || result.__cdata!=null )) {
					result.toString = function() {
						return (this.__text!=null? this.__text:'')+( this.__cdata!=null ? this.__cdata:'');
					};
				}

				return result;
			}
			else
			if(node.nodeType == DOMNodeTypes.TEXT_NODE || node.nodeType == DOMNodeTypes.CDATA_SECTION_NODE) {
				return node.nodeValue;
			}
		}

		function startTag(jsonObj, element, attrList, closed) {
			var resultStr = "<"+ ( (jsonObj!=null && jsonObj.__prefix!=null)? (jsonObj.__prefix+":"):"") + element;
			if(attrList!=null) {
				for(var aidx = 0; aidx < attrList.length; aidx++) {
					var attrName = attrList[aidx];
					var attrVal = jsonObj[attrName];
					if(config.escapeMode)
						attrVal=escapeXmlChars(attrVal);
					resultStr+=" "+attrName.substr(config.attributePrefix.length)+"=";
					if(config.useDoubleQuotes)
						resultStr+='"'+attrVal+'"';
					else
						resultStr+="'"+attrVal+"'";
				}
			}
			if(!closed)
				resultStr+=">";
			else
				resultStr+="/>";
			return resultStr;
		}

		function endTag(jsonObj,elementName) {
			return "</"+ (jsonObj.__prefix!=null? (jsonObj.__prefix+":"):"")+elementName+">";
		}

		function endsWith(str, suffix) {
			return str.indexOf(suffix, str.length - suffix.length) !== -1;
		}

		function jsonXmlSpecialElem ( jsonObj, jsonObjField ) {
			if((config.arrayAccessForm=="property" && endsWith(jsonObjField.toString(),("_asArray")))
					|| jsonObjField.toString().indexOf(config.attributePrefix)==0
					|| jsonObjField.toString().indexOf("__")==0
					|| (jsonObj[jsonObjField] instanceof Function) )
				return true;
			else
				return false;
		}

		function jsonXmlElemCount ( jsonObj ) {
			var elementsCnt = 0;
			if(jsonObj instanceof Object ) {
				for( var it in jsonObj  ) {
					if(jsonXmlSpecialElem ( jsonObj, it) )
						continue;
					elementsCnt++;
				}
			}
			return elementsCnt;
		}

		function checkJsonObjPropertiesFilter(jsonObj, propertyName, jsonObjPath) {
			return config.jsonPropertiesFilter.length == 0
				|| jsonObjPath==""
				|| checkInStdFiltersArrayForm(config.jsonPropertiesFilter, jsonObj, propertyName, jsonObjPath);
		}

		function parseJSONAttributes ( jsonObj ) {
			var attrList = [];
			if(jsonObj instanceof Object ) {
				for( var ait in jsonObj  ) {
					if(ait.toString().indexOf("__")== -1 && ait.toString().indexOf(config.attributePrefix)==0) {
						attrList.push(ait);
					}
				}
			}
			return attrList;
		}

		function parseJSONTextAttrs ( jsonTxtObj ) {
			var result ="";

			if(jsonTxtObj.__cdata!=null) {
				result+="<![CDATA["+jsonTxtObj.__cdata+"]]>";
			}

			if(jsonTxtObj.__text!=null) {
				if(config.escapeMode)
					result+=escapeXmlChars(jsonTxtObj.__text);
				else
					result+=jsonTxtObj.__text;
			}
			return result;
		}

		function parseJSONTextObject ( jsonTxtObj ) {
			var result ="";

			if( jsonTxtObj instanceof Object ) {
				result+=parseJSONTextAttrs ( jsonTxtObj );
			}
			else
				if(jsonTxtObj!=null) {
					if(config.escapeMode)
						result+=escapeXmlChars(jsonTxtObj);
					else
						result+=jsonTxtObj;
				}

			return result;
		}

		function getJsonPropertyPath(jsonObjPath, jsonPropName) {
			if (jsonObjPath==="") {
				return jsonPropName;
			}
			else
				return jsonObjPath+"."+jsonPropName;
		}

		function parseJSONArray ( jsonArrRoot, jsonArrObj, attrList, jsonObjPath ) {
			var result = "";
			if(jsonArrRoot.length == 0) {
				result+=startTag(jsonArrRoot, jsonArrObj, attrList, true);
			}
			else {
				for(var arIdx = 0; arIdx < jsonArrRoot.length; arIdx++) {
					result+=startTag(jsonArrRoot[arIdx], jsonArrObj, parseJSONAttributes(jsonArrRoot[arIdx]), false);
					result+=parseJSONObject(jsonArrRoot[arIdx], getJsonPropertyPath(jsonObjPath,jsonArrObj));
					result+=endTag(jsonArrRoot[arIdx],jsonArrObj);
				}
			}
			return result;
		}

		function parseJSONObject ( jsonObj, jsonObjPath ) {
			var result = "";

			var elementsCnt = jsonXmlElemCount ( jsonObj );

			if(elementsCnt > 0) {
				for( var it in jsonObj ) {

					if(jsonXmlSpecialElem ( jsonObj, it) || (jsonObjPath!="" && !checkJsonObjPropertiesFilter(jsonObj, it, getJsonPropertyPath(jsonObjPath,it))) )
						continue;

					var subObj = jsonObj[it];

					var attrList = parseJSONAttributes( subObj )

					if(subObj == null || subObj == undefined) {
						result+=startTag(subObj, it, attrList, true);
					}
					else
					if(subObj instanceof Object) {

						if(subObj instanceof Array) {
							result+=parseJSONArray( subObj, it, attrList, jsonObjPath );
						}
						else if(subObj instanceof Date) {
							result+=startTag(subObj, it, attrList, false);
							result+=subObj.toISOString();
							result+=endTag(subObj,it);
						}
						else {
							var subObjElementsCnt = jsonXmlElemCount ( subObj );
							if(subObjElementsCnt > 0 || subObj.__text!=null || subObj.__cdata!=null) {
								result+=startTag(subObj, it, attrList, false);
								result+=parseJSONObject(subObj, getJsonPropertyPath(jsonObjPath,it));
								result+=endTag(subObj,it);
							}
							else {
								result+=startTag(subObj, it, attrList, true);
							}
						}
					}
					else {
						result+=startTag(subObj, it, attrList, false);
						result+=parseJSONTextObject(subObj);
						result+=endTag(subObj,it);
					}
				}
			}
			result+=parseJSONTextObject(jsonObj);

			return result;
		}

		this.parseXmlString = function(xmlDocStr) {
			var isIEParser = window.ActiveXObject || "ActiveXObject" in window;
			if (xmlDocStr === undefined) {
				return null;
			}
			var xmlDoc;
			if (window.DOMParser) {
				var parser=new window.DOMParser();
				var parsererrorNS = null;
				// IE9+ now is here
				if(!isIEParser) {
					try {
						parsererrorNS = parser.parseFromString("INVALID", "text/xml").getElementsByTagName("parsererror")[0].namespaceURI;
					}
					catch(err) {
						parsererrorNS = null;
					}
				}
				try {
					xmlDoc = parser.parseFromString( xmlDocStr, "text/xml" );
					if( parsererrorNS!= null && xmlDoc.getElementsByTagNameNS(parsererrorNS, "parsererror").length > 0) {
						//throw new Error('Error parsing XML: '+xmlDocStr);
						xmlDoc = null;
					}
				}
				catch(err) {
					xmlDoc = null;
				}
			}
			else {
				// IE :(
				if(xmlDocStr.indexOf("<?")==0) {
					xmlDocStr = xmlDocStr.substr( xmlDocStr.indexOf("?>") + 2 );
				}
				xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
				xmlDoc.async="false";
				xmlDoc.loadXML(xmlDocStr);
			}
			return xmlDoc;
		};

		this.asArray = function(prop) {
			if (prop === undefined || prop == null)
				return [];
			else
			if(prop instanceof Array)
				return prop;
			else
				return [prop];
		};

		this.toXmlDateTime = function(dt) {
			if(dt instanceof Date)
				return dt.toISOString();
			else
			if(typeof(dt) === 'number' )
				return new Date(dt).toISOString();
			else
				return null;
		};

		this.asDateTime = function(prop) {
			if(typeof(prop) == "string") {
				return fromXmlDateTime(prop);
			}
			else
				return prop;
		};

		this.xml2json = function (xmlDoc) {
			return parseDOMChildren ( xmlDoc );
		};

		this.xml_str2json = function (xmlDocStr) {
			var xmlDoc = this.parseXmlString(xmlDocStr);
			if(xmlDoc!=null)
				return this.xml2json(xmlDoc);
			else
				return null;
		};

		this.json2xml_str = function (jsonObj) {
			return parseJSONObject ( jsonObj, "" );
		};

		this.json2xml = function (jsonObj) {
			var xmlDocStr = this.json2xml_str (jsonObj);
			return this.parseXmlString(xmlDocStr);
		};

		this.getVersion = function () {
			return VERSION;
		};
	}
}))

// xml handler을 이용하는 user function
var show_waiting_message = true;

(function($){
	var x2js = new X2JS();

	/**
	* @brief exec_xml
	* @author XEHub (developers@xpressengine.com)
	**/
	$.exec_xml = window.exec_xml = function(module, act, params, callback_func, response_tags, callback_func_arg, fo_obj) {
		var xml_path = request_uri+"index.php";
		if(!params) params = {};

		// {{{ set parameters
		if($.isArray(params)) params = arr2obj(params);
		params.module = module;
		params.act    = act;

		if(typeof(xeVid)!='undefined') params.vid = xeVid;
		if(typeof(response_tags) == "undefined" || response_tags.length<1) {
			response_tags = ['error','message'];
		} else {
			response_tags.push('error', 'message');
		}
		// }}} set parameters

		// use ssl?
		if ($.isArray(ssl_actions) && params.act && $.inArray(params.act, ssl_actions) >= 0) {
			var url    = default_url || request_uri;
			var port   = window.https_port || 443;
			var _ul    = $('<a>').attr('href', url)[0];
			var target = 'https://' + _ul.hostname.replace(/:\d+$/, '');

			if(port != 443) target += ':'+port;
			if(_ul.pathname[0] != '/') target += '/';

			target += _ul.pathname;
			xml_path = target.replace(/\/$/, '')+'/index.php';
		}

		var _u1 = $('<a>').attr('href', location.href)[0];
		var _u2 = $('<a>').attr('href', xml_path)[0];

		// 현 url과 ajax call 대상 url의 schema 또는 port가 다르면 직접 form 전송
		if(_u1.protocol != _u2.protocol || _u1.port != _u2.port) return send_by_form(xml_path, params);

		var xml = [];
		var xmlHelper = function(params) {
			var stack = [];

			if ($.isArray(params)) {
				$.each(params, function(key, val) {
					stack.push('<value type="array">' + xmlHelper(val) + '</value>');
				});
			}
			else if ($.isPlainObject(params)) {
				$.each(params, function(key, val) {
					var safeKey = String(key).replace(/[^A-Za-z0-9_\-:.]/g, '');
					if(!safeKey) return;
					stack.push('<' + safeKey + '>' + xmlHelper(val) + '</' + safeKey + '>');
				});
			}
			else if (!$.isFunction(params)) {
					stack.push(String(params).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'));
			}

			return stack.join('\n');
		};

		xml.push('<?xml version="1.0" encoding="utf-8" ?>');
		xml.push('<methodCall>');
		xml.push('<params>');
		xml.push(xmlHelper(params));
		xml.push('</params>');
		xml.push('</methodCall>');

		var _xhr = null;
		if (_xhr && _xhr.readyState !== 0) _xhr.abort();

		// 전송 성공시
		function onsuccess(data, textStatus, xhr) {
			var resp_xml = $(data).find('response')[0];
			var resp_obj;
			var txt = '';
			var ret = {};
			var tags = {};

			waiting_obj.css('display', 'none').trigger('cancel_confirm');

			if(!resp_xml) {
				alert(_xhr.responseText);
				return null;
			}

			resp_obj = x2js.xml2json(data).response;

			if (typeof(resp_obj)=='undefined') {
				ret.error = -1;
				ret.message = 'Unexpected error occured.';
				try {
					if(typeof(txt=resp_xml.childNodes[0].firstChild.data)!='undefined') {
						ret.message += '\r\n'+txt;
					}
				} catch(e){}

				return ret;
			}

			$.each(response_tags, function(key, val){
				tags[val] = true;
			});
			tags.redirect_url = true;
			tags.act = true;
			$.each(resp_obj, function(key, val){ 
				if(tags[key]) ret[key] = val;
			});

			if(ret.error != '0') {
				if ($.isFunction($.exec_xml.onerror)) {
					return $.exec_xml.onerror(module, act, ret, callback_func, response_tags, callback_func_arg, fo_obj);
				}

				alert( (ret.message || 'An unknown error occured while loading ['+module+'.'+act+']').replace(/\\n/g, '\n') );

				return null;
			}

			if(ret.redirect_url) {
				location.href = ret.redirect_url.replace(/&amp;/g, '&');
				return null;
			}

			if($.isFunction(callback_func)) callback_func(ret, response_tags, callback_func_arg, fo_obj);
		}

		// 모든 xml데이터는 POST방식으로 전송. try-catch문으로 오류 발생시 대처
		try {
			$.ajax({
				url         : xml_path,
				type        : 'POST',
				dataType    : 'xml',
				data        : xml.join('\n'),
				contentType : 'text/plain',
				beforeSend  : function(xhr){ _xhr = xhr; },
				success     : onsuccess,
				error       : function(xhr, textStatus) {
					waiting_obj.css('display', 'none');

					var msg = '';

					if (textStatus == 'parsererror') {
						msg  = 'The result is not valid XML :\n-------------------------------------\n';

						if(xhr.responseText === "") return;

						msg += xhr.responseText.replace(/<[^>]+>/g, '');
					} else {
						msg = textStatus;
					}

					try{
						console.log(msg);
					} catch(ee){}
				}
			});
		} catch(e) {
			alert(e);
			return;
		}

		// ajax 통신중 대기 메세지 출력 (show_waiting_message값을 false로 세팅시 보이지 않음)
		var waiting_obj = $('.wfsr');
		if(show_waiting_message && waiting_obj.length) {

			var timeoutId = $(".wfsr").data('timeout_id');
			if(timeoutId) clearTimeout(timeoutId);
			$(".wfsr").css('opacity', 0.0);
			$(".wfsr").data('timeout_id', setTimeout(function(){
				$(".wfsr").css('opacity', '');
			}, 1000));

			waiting_obj.html(waiting_message).show();
		}
	};

	function send_by_form(url, params) {
		var frame_id = 'xeTmpIframe';
		var form_id  = 'xeVirtualForm';

		if (!$('#'+frame_id).length) {
			$('<iframe name="%id%" id="%id%" style="position:absolute;left:-1px;top:1px;width:1px;height:1px"></iframe>'.replace(/%id%/g, frame_id)).appendTo(document.body);
		}

		$('#'+form_id).remove();
		var form = $('<form id="%id%"></form>'.replace(/%id%/g, form_id)).attr({
			'id'     : form_id,
			'method' : 'post',
			'action' : url,
			'target' : frame_id
		});

		params.xeVirtualRequestMethod = 'xml';
		params.xeRequestURI           = location.href.replace(/#(.*)$/i,'');
		params.xeVirtualRequestUrl    = request_uri;

		$.each(params, function(key, value){
			$('<input type="hidden">').attr('name', key).attr('value', value).appendTo(form);
		});

		form.appendTo(document.body).submit();
	}

	function arr2obj(arr) {
		var ret = {};
		for(var key in arr) {
			if(arr.hasOwnProperty(key)) ret[key] = arr[key];
		}

		return ret;
	}


	/**
	* @brief exec_json (exec_xml와 같은 용도)
	**/
	$.exec_json = window.exec_json = function(action, data, callback_sucess, callback_error){
		if(typeof(data) == 'undefined') data = {};

		action = action.split('.');

		if(action.length == 2) {
			// The cover can be disturbing if it consistently blinks (because ajax call usually takes very short time). So make it invisible for the 1st 0.5 sec and then make it visible.
			var timeoutId = $(".wfsr").data('timeout_id');

			if(timeoutId) clearTimeout(timeoutId);

			$(".wfsr").css('opacity', 0.0);
			$(".wfsr").data('timeout_id', setTimeout(function(){
				$(".wfsr").css('opacity', '');
			}, 1000));

			if(show_waiting_message) $(".wfsr").html(waiting_message).show();

			$.extend(data,{module:action[0],act:action[1]});

			if(typeof(xeVid)!='undefined') $.extend(data,{vid:xeVid});

			try {
				$.ajax({
					type: "POST",
					dataType: "json",
					url: request_uri,
					contentType: "application/json",
					data: $.param(data),
					success: function(data) {
						$(".wfsr").hide().trigger('cancel_confirm');
						if(data.error != '0' && data.error > -1000) {
							if(data.error == -1 && data.message == 'msg_is_not_administrator') {
								alert('You are not logged in as an administrator');
								if($.isFunction(callback_error)) callback_error(data);

								return;
							} else {
								alert(data.message);
								if($.isFunction(callback_error)) callback_error(data);

								return;
							}
						}

						if($.isFunction(callback_sucess)) callback_sucess(data);
					},
					error: function(xhr, textStatus) {
						$(".wfsr").hide();

						var msg = '';

						if (textStatus == 'parsererror') {
							msg  = 'The result is not valid JSON :\n-------------------------------------\n';

							if(xhr.responseText === "") return;

							msg += xhr.responseText.replace(/<[^>]+>/g, '');
						} else {
							msg = textStatus;
						}

						try{
							console.log(msg);
						} catch(ee){}
					}
				});
			} catch(e) {
				alert(e);
				return;
			}
		}
	};

	$.fn.exec_html = function(action,data,type,func,args){
		if(typeof(data) == 'undefined') data = {};
		if(!$.inArray(type, ['html','append','prepend'])) type = 'html';

		var self = $(this);
		action = action.split(".");
		if(action.length == 2){
			var timeoutId = $(".wfsr").data('timeout_id');
			if(timeoutId) clearTimeout(timeoutId);
			$(".wfsr").css('opacity', 0.0);
			$(".wfsr").data('timeout_id', setTimeout(function(){
				$(".wfsr").css('opacity', '');
			}, 1000));
			if(show_waiting_message) $(".wfsr").html(waiting_message).show();

			$.extend(data,{module:action[0],act:action[1]});
			try {
				$.ajax({
					type:"POST",
					dataType:"html",
					url:request_uri,
					data:$.param(data),
					success : function(html){
						$(".wfsr").hide().trigger('cancel_confirm');
						self[type](html);
						if($.isFunction(func)) func(args);
					},
					error: function(xhr, textStatus) {
						$(".wfsr").hide();

						var msg = '';

						if (textStatus == 'parsererror') {
							msg  = 'The result is not valid page :\n-------------------------------------\n';

							if(xhr.responseText === "") return;

							msg += xhr.responseText.replace(/<[^>]+>/g, '');
						} else {
							msg = textStatus;
						}

						try{
							console.log(msg);
						} catch(ee){}
					}

				});

			} catch(e) {
				alert(e);
				return;
			}
		}
	};

	function beforeUnloadHandler(){
	}

	$(function($){
		$(document)
			.ajaxStart(function(){
				$(window).on('beforeunload', beforeUnloadHandler);
			})
			.bind('ajaxStop cancel_confirm', function(){
				$(window).off('beforeunload', beforeUnloadHandler);
			});
	});

})(jQuery);

(function($){

	var messages  = [];
	var rules     = [];
	var filters   = {};
	var callbacks = [];
	var extras    = {};
	var conditionFnCache = {};

	var Validator = xe.createApp('Validator', {
		init : function() {
			// {{{ add filters
			// email
			var regEmail = /^[\w-]+((?:\.|\+|\~)[\w-]+)*@[\w-]+(\.[\w-]+)+$/;
			this.cast('ADD_RULE', ['email', regEmail]);
			this.cast('ADD_RULE', ['email_address', regEmail]);

			// userid
			var regUserid = /^[a-z]+[\w-]*[a-z0-9_]+$/i;
			this.cast('ADD_RULE', ['userid', regUserid]);
			this.cast('ADD_RULE', ['user_id', regUserid]);

			// url
			var regUrl = /^(https?|ftp|mms):\/\/[0-9a-z-]+(\.[_0-9a-z-]+)+(:\d+)?/;
			this.cast('ADD_RULE', ['url', regUrl]);
			this.cast('ADD_RULE', ['homepage', regUrl]);

			// korean
			var regKor = new RegExp('^[\uAC00-\uD7A3]*$');
			this.cast('ADD_RULE', ['korean', regKor]);

			// korean_number
			var regKorNum = new RegExp('^[\uAC00-\uD7A30-9]*$');
			this.cast('ADD_RULE', ['korean_number', regKorNum]);

			// alpha
			var regAlpha = /^[a-z]*$/i;
			this.cast('ADD_RULE', ['alpha', regAlpha]);

			// alpha_number
			var regAlphaNum = /^[a-z][a-z0-9_]*$/i;
			this.cast('ADD_RULE', ['alpha_number', regAlphaNum]);

			// number
			var regNum = /^[0-9]*$/;
			this.cast('ADD_RULE', ['number', regNum]);

			// float
			var regFloat = /^\d+(\.\d+)?$/;
			this.cast('ADD_RULE', ['float', regFloat]);
			// }}} add filters
		},
		// run validator
		run : function(oForm) {
			var filter = '';

			if (oForm._filter) filter = oForm._filter.value;

			var params = [oForm, filter];
			var result = this.cast('VALIDATE', params);
			if (typeof result == 'undefined') result = false;

			return result;
		},
		API_ONREADY : function() {
			var self = this;

			// hook form submit event
			$('form')
				.each(function(){
					if (this.onsubmit) {
						this['xe:onsubmit'] = this.onsubmit;
						this.onsubmit = null;
					}
				})
				.submit(function(e){
					var legacyFn = this['xe:onsubmit'];
					var hasLegacyFn = $.isFunction(legacyFn);
					var bResult = hasLegacyFn?legacyFn.apply(this):self.run(this);

					if(!bResult)
					{
						e.stopImmediatePropagation();
					}
					return bResult;
				});
		},
		API_VALIDATE : function(sender, params) {
			var result = true, form = params[0], elems = form.elements, filter, filter_to_add, ruleset, callback;
			var fields, names, name, el, val, mod, len, lenb, max, min, maxb, minb, rules, e_el, e_val, i, c, r, if_, fn, f;

			if(elems.ruleset) {
				filter = form.elements.ruleset.value;
			} else if(elems._filter) {
				filter = form.elements._filter.value;
			}

			if(!filter) return true;

			if($.isFunction(callbacks[filter])) callback = callbacks[filter];
			filter = $.extend({}, filters[filter.toLowerCase()] || {}, extras);

			function regex_quote(str) {
				return str.replace(/([\.\+\-\[\]\{\}\(\)\\])/g, '\\$1');
			}

			// get form names
			fields = [];
			for(i=0,c=form.elements.length; i < c; i++) {
				el   = elems[i];
				name = el.name;

				if(!name || !elems[name]) continue;
				if(!elems[name].length || elems[name][0] === el) fields.push(name);
			}
			fields = fields.join('\n');

			// get field names matching patterns
			filter_to_add = {};
			for(name in filter) {
				if(!filter.hasOwnProperty(name)) continue;

				names = [];
				if(name.substr(0,1) == '^') {
					names = fields.match( (new RegExp('^'+regex_quote(name.substr(1))+'.*$','gm')) );
				} else {
					continue;
				}
				if(!names) names = [];

				for(i=0,c=names.length; i < c; i++) {
					filter_to_add[names[i]]= filter[name];
				}

				filter[name] = null;
				delete filter[name];
			}

			filter = $.extend(filter, filter_to_add);

			for(name in filter) {
				if(!filter.hasOwnProperty(name)) continue;

				f   = filter[name];
				el  = elems[name];
				if(!el)
				{
					el = elems[name + '[]'];
				}
				val = el?$.trim(get_value($(el))):'';
				mod = (f.modifier||'')+',';


				if(!el || el.disabled) continue;

				if(f['if']) {
					if(!$.isArray(f['if'])) f['if'] = [f['if']];
					for(i=0;i<f['if'].length;i++) {
						/*jslint evil: true */
						if_ = f['if'][i];
						var compiledTest = if_.test.replace(/\$(\w+)/g, '(jQuery(\'[name=$1]\').is(\':radio, :checkbox\') ? jQuery(\'[name=$1]:checked\').val() : jQuery(\'[name=$1]\').val())');
						if(!conditionFnCache[compiledTest]) {
							conditionFnCache[compiledTest] = new Function('el', 'return !!(' + compiledTest + ')');
						}
						fn  = conditionFnCache[compiledTest];
						//fn  = new Function('el', 'return !!(' + (if_.test.replace(/\$(\w+)/g, 'el["$1"].value')) +')');
						if(fn(elems)) f[if_.attr] = if_.value;
						else delete f[if_.attr];

					}
				}

				if(!val) {
					if(f['default']) val = f['default'];
					if(f.required) return this.cast('ALERT', [form, name, 'isnull']) && false;
					else continue;
				}

				min  = parseInt(f.minlength) || 0;
				max  = parseInt(f.maxlength) || 0;
				minb = /b$/.test(f.minlength||'');
				maxb = /b$/.test(f.maxlength||'');
				len  = val.length;
				if(minb || maxb) lenb = get_bytes(val);
				if((min && min > (minb?lenb:len)) || (max && max < (maxb?lenb:len))) {
					return this.cast('ALERT', [form, name, 'outofrange', min, max]) && false;
				}

				if(f.equalto) {
					e_el  = elems[f.equalto];
					e_val = e_el?$.trim(get_value($(e_el))):'';
					if(e_el && e_val !== val) {
						return this.cast('ALERT', [form, name, 'equalto']) && false;
					}
				}

				rules = (f.rule || '').split(',');
				for(i=0,c=rules.length; i < c; i++) {
					if(!(r = rules[i])) continue;

					result = this.cast('APPLY_RULE', [r, val]);
					if(mod.indexOf('not,') > -1) result = !result;
					if(!result) {
						return this.cast('ALERT', [form, name, 'invalid_'+r]) && false;
					}
				}
			}

			if($.isFunction(callback)) return callback(form);

			return true;
		},
		API_ADD_RULE : function(sender, params) {
			var name = params[0].toLowerCase();
			rules[name] = params[1];
		},
		API_DEL_RULE : function(sender, params) {
			var name = params[0].toLowerCase();
			delete rules[name];
		},
		API_GET_RULE : function(sender, params) {
			var name = params[0].toLowerCase();

			if (rules[name]) {
				return rules[name];
			} else {
				return null;
			}
		},
		API_ADD_FILTER : function(sender, params) {
			var name   = params[0].toLowerCase();
			var filter = params[1];

			filters[name] = filter;
		},
		API_DEL_FILTER : function(sender, params) {
			var name = params[0].toLowerCase();
			delete filters[name];
		},
		API_GET_FILTER : function(sender, params) {
			var name = params[0].toLowerCase();

			if (filters[name]) {
				return filters[name];
			} else {
				return null;
			}
		},
		API_ADD_EXTRA_FIELD : function(sender, params) {
			var name = params[0].toLowerCase();
			var prop = params[1];

			extras[name] = prop;
		},
		API_GET_EXTRA_FIELD : function(sender, params) {
			var name = params[0].toLowerCase();
			return extras[name];
		},
		API_DEL_EXTRA_FIELD : function(sender, params) {
			var name = params[0].toLowerCase();
			delete extras[name];
		},
		API_APPLY_RULE : function(sender, params) {
			var name  = params[0];
			var value = params[1];

			if(typeof(rules[name]) == 'undefined') return true; // no filter
			if($.isFunction(rules[name])) return rules[name](value);
			if(rules[name] instanceof RegExp) return rules[name].test(value);
			if($.isArray(rules[name])) return ($.inArray(value, rules[name]) > -1);

			return true;
		},
		API_ALERT : function(sender, params) {
			var form = params[0];
			var field_name = params[1];
			var msg_code = params[2];
			var minlen   = params[3];
			var maxlen   = params[4];

			var field_msg = this.cast('GET_MESSAGE', [field_name]);
			var msg = this.cast('GET_MESSAGE', [msg_code]);

			if (msg != msg_code) msg = (msg.indexOf('%s')<0)?(field_msg+msg):(msg.replace('%s',field_msg));
			if (minlen||maxlen) msg +=  '('+(minlen||'')+'~'+(maxlen||'')+')';

			this.cast('SHOW_ALERT', [msg]);

			// set focus
			$(form.elements[field_name]).focus();
		},
		API_SHOW_ALERT : function(sender, params) {
			alert(params[0]);
		},
		API_ADD_MESSAGE : function(sender, params) {
			var msg_code = params[0];
			var msg_str  = params[1];

			messages[msg_code] = msg_str;
		},
		API_GET_MESSAGE : function(sender, params) {
			var msg_code = params[0];

			return messages[msg_code] || msg_code;
		},
		API_ADD_CALLBACK : function(sender, params) {
			var name = params[0];
			var func = params[1];

			callbacks[name] = func;
		},
		API_REMOVE_CALLBACK : function(sender, params) {
			var name = params[0];

			delete callbacks[name];
		}
	});

	var oValidator = new Validator();

	// register validator
	xe.registerApp(oValidator);

	// 호환성을 위해 추가한 플러그인 - 에디터에서 컨텐트를 가져와서 설정한다.
	var EditorStub = xe.createPlugin('editor_stub', {
		API_BEFORE_VALIDATE : function(sender, params) {
			var form = params[0];
			var seq  = form.getAttribute('editor_sequence');

			// bug fix for IE6,7
			if (seq && typeof seq == 'object') seq = seq.value;

			if (seq) {
				try {
					editorRelKeys[seq].content.value = editorRelKeys[seq].func(seq) || '';
				} catch(e) { }
			}
		}
	});
	oValidator.registerPlugin(new EditorStub());

	// functions
	function get_value($elem) {
		var vals = [];
		if ($elem.is(':radio')){
			return $elem.filter(':checked').val();
		} else if ($elem.is(':checkbox')) {
			$elem.filter(':checked').each(function(){
				vals.push(this.value);
			});
			return vals.join('|@|');
		} else {
			return $elem.val();
		}
	}

	function get_bytes(str) {
		str += '';
		if(!str.length) return 0;

		str = encodeURI(str);
		var c = str.split('%').length - 1;

		return str.length - c*2;
	}

})(jQuery);

/**
 * @function filterAlertMessage
 * @brief ajax로 서버에 요청후 결과를 처리할 callback_function을 지정하지 않았을 시 호출되는 기본 함수
 **/
function filterAlertMessage(ret_obj) {
	var error = ret_obj.error;
	var message = ret_obj.message;
	var act = ret_obj.act;
	var redirect_url = ret_obj.redirect_url;
	var url = location.href;

	if(typeof(message) != "undefined" && message && message != "success") alert(message);

	if(typeof(act)!="undefined" && act) url = current_url.setQuery("act", act);
	else if(typeof(redirect_url) != "undefined" && redirect_url) url = redirect_url;

	if(url == location.href) url = url.replace(/#(.*)$/,'');

	location.href = url;
}

/**
 * @brief Function to process filters
 * @deprecated
 */
function procFilter(form, filter_func) {
	filter_func(form);
	return false;
}

function legacy_filter(filter_name, form, module, act, callback, responses, confirm_msg, rename_params) {
	var v = xe.getApp('Validator')[0], $ = jQuery, args = [];

	if (!v) return false;

	if (!form.elements._filter) $(form).prepend('<input type="hidden" name="_filter" />');
	form.elements._filter.value = filter_name;

	args[0] = filter_name;
	args[1] = function(f) {
		var params = {}, res = [], elms = f.elements, data = $(f).serializeArray();
		$.each(data, function(i, field) {
			var v = $.trim(field.value), n = field.name;
			if(!v || !n) return true;
			if(rename_params[n]) n = rename_params[n];

			if(/\[\]$/.test(n)) n = n.replace(/\[\]$/, '');
			if(params[n]) {
				params[n] += '|@|'+v;
			} else {
				params[n] = field.value;
			}
		});

		if (confirm_msg && !confirm(confirm_msg)) return false;

		exec_xml(module, act, params, callback, responses, params, form);
	};

	v.cast('ADD_CALLBACK', args);
	v.cast('VALIDATE', [form, filter_name]);

	return false;
}
