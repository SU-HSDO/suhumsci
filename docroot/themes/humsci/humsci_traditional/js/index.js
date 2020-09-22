/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/js/traditional/traditional.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/js/shared/equal-height-grid/equal-height-grid.js":
/*!**************************************************************!*\
  !*** ./src/js/shared/equal-height-grid/equal-height-grid.js ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n// This function takes two parameters:\n// The first parameter should be whatever type of element you'd like to be the same height, and\n// ideally these types of elements are laid out in a grid that you'd like the height to be the same\n// The second parameter is the special class that will make this function execute and turn elements \n// into the same height. If the special class isn't present, we won't run this function.\nvar equalHeightGrid = function equalHeightGrid(element, specialWrapperClass) {\n  var equalGridWrapper = document.getElementsByClassName(specialWrapperClass); // If the wrapper class for specifying making things the same height is present,\n  // keep going\n\n  if (equalGridWrapper.length > 0) {\n    var elementList = document.getElementsByClassName(element);\n\n    if (elementList.length > 0) {\n      // Create array with all of the heights of each element\n      var elementHeights = Array.prototype.map.call(elementList, function (el) {\n        return el.scrollHeight;\n      }); // Create array with _unique_ height values\n\n      var uniqueHeights = elementHeights.filter(function (height, index, array) {\n        return array.indexOf(height) == index;\n      }); // If there is only 1 unique value, then all elements are the same height,\n      // and in that case, we don't need to change the height at all\n\n      if (uniqueHeights.length > 1) {\n        return new Promise(function (resolve, reject) {\n          var maxHeight = Math.max.apply(null, elementHeights);\n          var tallestElementIndex = elementHeights.indexOf(maxHeight);\n          Array.prototype.forEach.call(elementList, function (el, index) {\n            // Ignore the tallest element as it is already set to the right height\n            if (index != tallestElementIndex) {\n              el.style.height = \"\".concat(maxHeight, \"px\");\n            }\n          });\n          resolve();\n        });\n      }\n    }\n  } else {\n    return null;\n  }\n};\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (equalHeightGrid);\n\n//# sourceURL=webpack:///./src/js/shared/equal-height-grid/equal-height-grid.js?");

/***/ }),

/***/ "./src/js/shared/equal-height-grid/index.js":
/*!**************************************************!*\
  !*** ./src/js/shared/equal-height-grid/index.js ***!
  \**************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _equal_height_grid__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./equal-height-grid */ \"./src/js/shared/equal-height-grid/equal-height-grid.js\");\n // Wait a 1 sec for page to load in before setting heights\n// Make the vertical linked card titles AND cards the same max height\n\nsetTimeout(function () {\n  Object(_equal_height_grid__WEBPACK_IMPORTED_MODULE_0__[\"default\"])('hb-vertical-linked-card__title', 'hb-stretch-vertical-linked-cards').then(function (result) {\n    return Object(_equal_height_grid__WEBPACK_IMPORTED_MODULE_0__[\"default\"])('hb-vertical-linked-card', 'hb-stretch-vertical-linked-cards');\n  })[\"catch\"](function (result) {\n    return console.error('issue loading equal height cards', result);\n  });\n}, 1000);\n\n//# sourceURL=webpack:///./src/js/shared/equal-height-grid/index.js?");

/***/ }),

/***/ "./src/js/shared/index.js":
/*!********************************!*\
  !*** ./src/js/shared/index.js ***!
  \********************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _tables_wrap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./tables/wrap */ \"./src/js/shared/tables/wrap.js\");\n/* harmony import */ var _tables_wrap__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_tables_wrap__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _tables_scope__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./tables/scope */ \"./src/js/shared/tables/scope.js\");\n/* harmony import */ var _tables_scope__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_tables_scope__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _tables_table_pattern__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./tables/table-pattern */ \"./src/js/shared/tables/table-pattern.js\");\n/* harmony import */ var _tables_table_pattern__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_tables_table_pattern__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _navigation_index__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./navigation/index */ \"./src/js/shared/navigation/index.js\");\n/* harmony import */ var _equal_height_grid_index__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./equal-height-grid/index */ \"./src/js/shared/equal-height-grid/index.js\");\n/* harmony import */ var _media_video_with_caption__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./media/video-with-caption */ \"./src/js/shared/media/video-with-caption.js\");\n/* harmony import */ var _media_video_with_caption__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_media_video_with_caption__WEBPACK_IMPORTED_MODULE_5__);\n\n\n\n\n\n\n\n//# sourceURL=webpack:///./src/js/shared/index.js?");

/***/ }),

/***/ "./src/js/shared/media/video-with-caption.js":
/*!***************************************************!*\
  !*** ./src/js/shared/media/video-with-caption.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("// In order to style the figcaption, figure elements have a display: table.\n// This causes issues when there is a video in a figure because the video no longer\n// fills the entire space of the container.\n// This JS sets a width of 100% to figures that contain videos.\nvar videos = document.querySelectorAll('.field-media-oembed-video');\n\nif (videos && videos.length > 0) {\n  for (var i = 0; i < videos.length; i++) {\n    var video = videos[i];\n\n    if (video.parentNode && video.parentNode.parentNode && video.parentNode.parentNode.nodeName == 'FIGURE') {\n      var figure = video.parentNode.parentNode;\n\n      if (figure.classList.contains('caption')) {\n        figure.style.width = '100%';\n      }\n    }\n  }\n}\n\n//# sourceURL=webpack:///./src/js/shared/media/video-with-caption.js?");

/***/ }),

/***/ "./src/js/shared/navigation/change-nav.js":
/*!************************************************!*\
  !*** ./src/js/shared/navigation/change-nav.js ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\nvar changeNav = function changeNav(toggle, parent, isOpen) {\n  toggle.setAttribute('aria-expanded', isOpen);\n  parent.setAttribute('aria-hidden', !isOpen);\n};\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (changeNav);\n\n//# sourceURL=webpack:///./src/js/shared/navigation/change-nav.js?");

/***/ }),

/***/ "./src/js/shared/navigation/collapse-main-menu.js":
/*!********************************************************!*\
  !*** ./src/js/shared/navigation/collapse-main-menu.js ***!
  \********************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _change_nav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./change-nav */ \"./src/js/shared/navigation/change-nav.js\");\n // The main menu is expanded by default, which allows users who have JavaScript disabled to navigate.\n// This script collapses the pre-expanded menus so it's ready to use for those w/ JavaScript enabled.\n\nvar mainToggle = document.querySelector('.hb-main-nav__toggle');\nvar mainNavContent = document.querySelector('.hb-main-nav__menu-lv1');\nvar nestedTogglers = document.querySelectorAll('.hb-nested-toggler');\nvar isBelowMobileNavBreakpoint = window.innerWidth < 992; // Collapse the main hamburger nav on mobile.\n\nif (isBelowMobileNavBreakpoint) {\n  Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(mainToggle, mainNavContent, false);\n} // Collapse the subnavs at all screen sizes.\n\n\nif (nestedTogglers) {\n  for (var i = 0; i < nestedTogglers.length; i += 1) {\n    var toggler = nestedTogglers[i];\n    var togglerID = toggler.getAttribute('id');\n    var togglerContent = document.querySelector(\"[aria-labelledby=\\\"\".concat(togglerID, \"\\\"]\"));\n    var subnavIsActive = toggler.parentNode.classList.contains('hb-main-nav__item--active-trail') ? true : false;\n\n    if (!togglerContent) {\n      continue;\n    } // On page load, all menus in the active section should be expanded on mobile. All other menus should be hidden.\n\n\n    var isExpanded = subnavIsActive && isBelowMobileNavBreakpoint ? true : false;\n    Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(toggler, togglerContent, isExpanded);\n  }\n} // Now that we've manually collapsed the main nav and subnavs,\n// we can remove the \"still loading\" class and disable the CSS-powered menu suppression.\n\n\ndocument.querySelector('.hb-main-nav--is-still-loading').classList.remove('hb-main-nav--is-still-loading');\n\n//# sourceURL=webpack:///./src/js/shared/navigation/collapse-main-menu.js?");

/***/ }),

/***/ "./src/js/shared/navigation/index.js":
/*!*******************************************!*\
  !*** ./src/js/shared/navigation/index.js ***!
  \*******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _main_menu_toggle__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./main-menu-toggle */ \"./src/js/shared/navigation/main-menu-toggle.js\");\n/* harmony import */ var _main_menu_nested_toggler__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./main-menu-nested-toggler */ \"./src/js/shared/navigation/main-menu-nested-toggler.js\");\n/* harmony import */ var _collapse_main_menu__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./collapse-main-menu */ \"./src/js/shared/navigation/collapse-main-menu.js\");\n/* harmony import */ var _secondary_toggler__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./secondary-toggler */ \"./src/js/shared/navigation/secondary-toggler.js\");\n\n\n\n\n\n//# sourceURL=webpack:///./src/js/shared/navigation/index.js?");

/***/ }),

/***/ "./src/js/shared/navigation/main-menu-nested-toggler.js":
/*!**************************************************************!*\
  !*** ./src/js/shared/navigation/main-menu-nested-toggler.js ***!
  \**************************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _change_nav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./change-nav */ \"./src/js/shared/navigation/change-nav.js\");\n\nvar togglers = document.querySelectorAll('.hb-nested-toggler');\nvar mobileNavBreakpoint = 992;\nvar windowWidth = window.innerWidth;\n\nif (togglers) {\n  var _loop = function _loop(i) {\n    var toggler = togglers[i];\n    var togglerID = toggler.getAttribute('id');\n    var togglerContent = document.querySelector(\"[aria-labelledby=\\\"\".concat(togglerID, \"\\\"]\"));\n    var togglerParent = toggler.parentNode; // Togglers should always have content but in the event that they don't we\n    // don't want the rest of the togglers on the page to break.\n\n    if (!togglerContent) {\n      return \"continue\";\n    }\n\n    toggler.addEventListener('click', function (e) {\n      e.preventDefault();\n      var isExpanded = e.target.getAttribute('aria-expanded') === \"true\";\n      Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(toggler, togglerContent, !isExpanded);\n    }); // Some togglers will be anchor tags instead of buttons and they should behave\n    // like a button when the spacebar is pressed\n\n    toggler.addEventListener('keydown', function (e) {\n      // 32 is the keycode for the spacebar\n      if (e.which !== 32) {\n        return;\n      }\n\n      e.preventDefault();\n      var isExpanded = e.target.getAttribute('aria-expanded') === \"true\";\n      Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(toggler, togglerContent, !isExpanded);\n    }); // At larger screen sizes:\n    // =========================================================================\n    // All menus collapse when resizing larger than the lg breakpoint\n\n    window.addEventListener('resize', function () {\n      windowWidth = window.innerWidth; // When resizing from mobile to desktop, show the navigation\n\n      if (windowWidth >= mobileNavBreakpoint) {\n        Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(toggler, togglerContent, false);\n      }\n    }); // We want to close open dropdowns on desktop when the following events happen\n    // on the body, outside of the toggler component:\n    // 1. (focusin) When tabbing through the navigation the previously opened dropdown closes\n    // 2. (click) When clicking outside of the dropdown area it will close\n\n    [\"focusin\", \"click\"].forEach(function (event) {\n      document.body.addEventListener(event, function (e) {\n        if (windowWidth >= mobileNavBreakpoint && !togglerParent.contains(e.target)) {\n          Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(toggler, togglerContent, false);\n        }\n      }, false);\n    });\n  };\n\n  for (var i = 0; i < togglers.length; i += 1) {\n    var _ret = _loop(i);\n\n    if (_ret === \"continue\") continue;\n  }\n}\n\n//# sourceURL=webpack:///./src/js/shared/navigation/main-menu-nested-toggler.js?");

/***/ }),

/***/ "./src/js/shared/navigation/main-menu-toggle.js":
/*!******************************************************!*\
  !*** ./src/js/shared/navigation/main-menu-toggle.js ***!
  \******************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _change_nav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./change-nav */ \"./src/js/shared/navigation/change-nav.js\");\n\nvar menuToggle = document.querySelector('.hb-main-nav__toggle');\nvar mainMenu = document.querySelector('.hb-main-nav__menu-lv1');\nvar mobileNavBreakpoint = 992;\nvar windowWidth;\nvar wasDesktopSize;\n\nif (menuToggle) {\n  // Toggle the nav when the the button is clicked\n  menuToggle.addEventListener('click', function (e) {\n    var isExpanded = menuToggle.getAttribute('aria-expanded') === \"true\";\n    Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(menuToggle, mainMenu, !isExpanded);\n  }); // Handle the showing/hiding of the nav when resizing the browser\n\n  window.addEventListener('resize', function (e) {\n    windowWidth = window.innerWidth; // When resizing from mobile to desktop, ensure navigation is displayed, not hidden\n    // If wasDesktopSize is false, it means we haven't gotten there yet and will to run this check\n    // Otherwise, if wasDesktopSize is true, we are above the mobileNavBreakpoint and don't need to keep showingNav\n\n    if (windowWidth >= mobileNavBreakpoint && !wasDesktopSize) {\n      Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(menuToggle, mainMenu, true);\n      wasDesktopSize = true;\n    } // When resizing from desktop to mobile, hide the navigation\n\n\n    if (windowWidth < mobileNavBreakpoint && wasDesktopSize) {\n      Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(menuToggle, mainMenu, false); // This keeps the navigation from collapsing every time the screen is resized\n      // below remains the mobileNavBreakpoint\n      // After the first time we resize to below the mobileNavBreakpoint, reset wasDesktopSize var\n\n      wasDesktopSize = false;\n    }\n  });\n}\n\n//# sourceURL=webpack:///./src/js/shared/navigation/main-menu-toggle.js?");

/***/ }),

/***/ "./src/js/shared/navigation/secondary-toggler.js":
/*!*******************************************************!*\
  !*** ./src/js/shared/navigation/secondary-toggler.js ***!
  \*******************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _change_nav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./change-nav */ \"./src/js/shared/navigation/change-nav.js\");\n\nvar togglers = document.querySelectorAll('.hb-secondary-toggler');\n\nif (togglers) {\n  var _loop = function _loop(i) {\n    var toggler = togglers[i];\n    var togglerID = toggler.getAttribute('id');\n    var togglerContent = document.querySelector(\"[aria-labelledby=\\\"\".concat(togglerID, \"\\\"]\"));\n    var togglerParent = toggler.parentNode;\n    var activeTrail = togglerParent.classList.contains('hb-secondary-nav__item--active-trail'); // Togglers should always have content but in the event that they don't we\n    // don't want the rest of the togglers on the page to break.\n\n    if (!togglerContent) {\n      return \"continue\";\n    }\n\n    if (!activeTrail) {\n      Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(toggler, togglerContent, false);\n    }\n\n    toggler.addEventListener('click', function (e) {\n      e.preventDefault();\n      var isExpanded = e.target.getAttribute('aria-expanded') === \"true\";\n      Object(_change_nav__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(toggler, togglerContent, !isExpanded);\n    });\n  };\n\n  for (var i = 0; i < togglers.length; i += 1) {\n    var _ret = _loop(i);\n\n    if (_ret === \"continue\") continue;\n  }\n}\n\n//# sourceURL=webpack:///./src/js/shared/navigation/secondary-toggler.js?");

/***/ }),

/***/ "./src/js/shared/tables/scope.js":
/*!***************************************!*\
  !*** ./src/js/shared/tables/scope.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/**\n * Add scope attribute to column/row headers on every table\n * This improves table accessibility\n */\n\n/**\n * Set a specific scope attribute value on each element\n * @param elements\n * @param scope\n */\nfunction setScopeOnElements(elements, scope) {\n  for (var i = 0; i < elements.length; i++) {\n    elements[i].setAttribute('scope', scope);\n  }\n} // set scope attribute on column headers\n\n\nvar columnEls = document.querySelectorAll('thead th');\nsetScopeOnElements(columnEls, 'col'); // set scope attribute on row headers\n\nvar rowEls = document.querySelectorAll('tbody th');\nsetScopeOnElements(rowEls, 'row');\n\n//# sourceURL=webpack:///./src/js/shared/tables/scope.js?");

/***/ }),

/***/ "./src/js/shared/tables/table-pattern.js":
/*!***********************************************!*\
  !*** ./src/js/shared/tables/table-pattern.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("// account for different ways in which a table heading may be declared\nvar div = \"div.hb-table-pattern__header > div.hb-table-pattern__row > div\";\nvar span = \"div.hb-table-pattern__header > div.hb-table-pattern__row > span\";\nvar paragraph = \"div.hb-table-pattern__header > div.hb-table-pattern__row > p\"; // retrieve table column headings\n\nvar columnHeaders = document.querySelectorAll(\"\".concat(div, \", \").concat(span, \", \").concat(paragraph)); // retrieve all rows\n\nvar tableRows = document.querySelectorAll('.hb-table-row');\n\nif (tableRows) {\n  // For each row in the table\n  for (var i = 0; i < tableRows.length; i += 1) {\n    // find the row headers in each cell\n    var tableRowHeaders = tableRows[i].querySelectorAll('.hb-table-row__heading'); // we need h to step through columnHeaders and get the correct heading text\n\n    for (var h = 0; h < tableRowHeaders.length; h += 1) {\n      tableRowHeaders[h].innerHTML = columnHeaders[h].innerHTML;\n    }\n  }\n}\n\n//# sourceURL=webpack:///./src/js/shared/tables/table-pattern.js?");

/***/ }),

/***/ "./src/js/shared/tables/wrap.js":
/*!**************************************!*\
  !*** ./src/js/shared/tables/wrap.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/**\n * Wrap every table in a class that will allow us to create more responsive styling\n */\n\n/**\n * Wrap each element in a new parent\n * @param elements\n * @param wrapper\n */\nfunction wrapElement(element) {\n  // Create a new div with a special class name\n  var wrapper = document.createElement('div');\n  wrapper.className = 'hb-table-wrap';\n  element.parentNode.insertBefore(wrapper, element);\n  wrapper.appendChild(element);\n} // Select every table element\n\n\nvar elements = document.querySelectorAll('table');\nvar uiPatternTable = document.querySelectorAll('.hb-table-pattern'); // Wrap every table element\n\nfor (var i = 0; i < elements.length; i++) {\n  wrapElement(elements[i]);\n} // Wrap every table UI pattern\n\n\nfor (var _i = 0; _i < uiPatternTable.length; _i++) {\n  wrapElement(uiPatternTable[_i]);\n}\n\n//# sourceURL=webpack:///./src/js/shared/tables/wrap.js?");

/***/ }),

/***/ "./src/js/traditional/traditional.js":
/*!*******************************************!*\
  !*** ./src/js/traditional/traditional.js ***!
  \*******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _shared_index_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../shared/index.js */ \"./src/js/shared/index.js\");\n //Add Traditional specific JS below\n\n//# sourceURL=webpack:///./src/js/traditional/traditional.js?");

/***/ })

/******/ });