(()=>{var e={418:()=>{function e(e){return function(e){if(Array.isArray(e))return t(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||function(e,n){if(e){if("string"==typeof e)return t(e,n);var r=Object.prototype.toString.call(e).slice(8,-1);return"Object"===r&&e.constructor&&(r=e.constructor.name),"Map"===r||"Set"===r?Array.from(e):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?t(e,n):void 0}}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function t(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}function n(e,t){"closeAll"===t?e.forEach((function(e){e.removeAttribute("open")})):e.forEach((function(e){e.setAttribute("open","")}))}function r(e,t){e.forEach((function(e){e.innerText="closeAll"===t?"Expand All":"Collapse All"}))}var o=e(document.querySelectorAll("details"));if(o.length>=1){var a=!1;o.forEach((function(e){if(e.classList.contains("hb-accordion_toggle-all")){var t=function(){var e=document.createElement("Button");return e.innerText="Expand All",e.classList.add("hb-link"),e.classList.add("hb-accordion-toggle-all"),e}();e.parentNode.insertBefore(t,e)}}));var i=e(document.querySelectorAll(".hb-accordion-toggle-all"));i.forEach((function(e){e.addEventListener("click",(function(t){t.preventDefault(),a?(n(o,"closeAll"),r(i,"closeAll"),a=!1):(n(o,"openAll"),r(i,"openAll"),a=!0),e.scrollIntoView(!0)}))}))}var c,l=new URLSearchParams(window.location.search),u=Object.fromEntries(l.entries());Object.keys(u).length&&Object.prototype.hasOwnProperty.call(u,"search")&&(c=u.search.toLowerCase(),o.forEach((function(e){e.textContent.toLowerCase().includes(c)&&e.setAttribute("open","")})))},697:()=>{var e,t;e=document.querySelectorAll(".addtocal"),t=document.querySelector("body"),e.forEach((function(e){e.addEventListener("click",(function(){"true"===e.getAttribute("aria-expanded")?e.setAttribute("aria-expanded","false"):e.setAttribute("aria-expanded","true")}))})),t.addEventListener("click",(function(t){e.forEach((function(e){t.target!==e&&e.setAttribute("aria-expanded","false")}))}))},356:()=>{var e=document.querySelector(".hb-has-animation-enhancements"),t=document.querySelector(".hb-experimental"),n=[document.querySelectorAll(".hs-font-lead")],r=[document.querySelectorAll(".hb-gradient-hero"),document.querySelectorAll(".hb-gradient-hero__text"),document.querySelectorAll(".hb-gradient-hero__image-wrapper"),document.querySelectorAll(".field-hs-gradient-hero-image"),document.querySelectorAll(".hb-hero-overlay"),document.querySelectorAll(".hb-hero-overlay__text"),document.querySelectorAll(".hb-hero-overlay__image-wrapper"),document.querySelectorAll(".field-hs-hero-image"),document.querySelectorAll(".hs-font-splash")];t&&r.push(n);var o=new IntersectionObserver((function(e){e.forEach((function(e){e.intersectionRatio>0&&e.target.classList.add("animate")}))}));e&&r.forEach((function(e){e&&e.forEach((function(e){o.observe(e)}))}))},642:()=>{function e(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}var t,n=document.querySelectorAll(".paragraph--type--hs-carousel, .paragraph--type--hs-gradient-hero-slider, .paragraph--type--hs-sptlght-slder"),r=function(e,t){return e.setAttribute("style","min-height: ".concat(t,"px"))},o=function(){var t,o;n.forEach((function(n){t=[0];var a,i=n.querySelectorAll(".hb-hero-overlay__text, .hb-gradient-hero__text, .hb-spotlight__text");i.forEach((function(e){e.removeAttribute("style");var n=e.offsetHeight;n=parseInt(n,10),t.push(n)})),o=Math.max.apply(Math,function(t){if(Array.isArray(t))return e(t)}(a=t)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(a)||function(t,n){if(t){if("string"==typeof t)return e(t,n);var r=Object.prototype.toString.call(t).slice(8,-1);return"Object"===r&&t.constructor&&(r=t.constructor.name),"Map"===r||"Set"===r?Array.from(t):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?e(t,n):void 0}}(a)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()),i.forEach((function(e){return r(e,o)}));var c=n.querySelector(".slick__arrow");n.classList.contains("paragraph--type--hs-carousel")&&c&&r(c,o),i.forEach((function(e){return function(e){return e.classList.contains("hb-spotlight__text")}(e)&&r(e,o)}))}))};n.length>0&&(o(),window.addEventListener("resize",(function(){clearTimeout(t),t=setTimeout(o,100)})))},738:()=>{window.editoria11yOptionsOverride=!0,window.editoria11yOptions=function(e){return ed11yLang&&ed11yLang.en&&(ed11yLang.en.buttonOutlineContent="Heading Outline"),e},window.addEventListener("load",(function(){ed11yOnce&&new MutationObserver((function(e){e.forEach((function(e){if(e.addedNodes.length&&"ED11Y-ELEMENT-PANEL"===e.addedNodes[0].nodeName){var t=e.addedNodes[0].shadowRoot.querySelector("style");t.textContent="".concat(t.textContent,"\n        .shut.errors #toggle {\n          color: #000000;\n        }")}}))})).observe(document.body,{childList:!0})}))},808:()=>{var e=document.querySelectorAll(".field-media-oembed-video");if(e&&e.length>0)for(var t=0;t<e.length;t++){var n=e[t];if(n.parentNode&&n.parentNode.parentNode&&"FIGURE"===n.parentNode.parentNode.nodeName){var r=n.parentNode.parentNode;r.classList.contains("caption")&&(r.style.width="100%")}}},743:()=>{var e=document.querySelector(".js-megamenu");if(e){var t=document.querySelector(".js-megamenu__mobile-btn"),n=e.querySelector(".js-megamenu__list--main"),r=e.querySelectorAll(".js-megamenu__toggle"),o=window.innerWidth,a=o<992;window.addEventListener("resize",(function(){o=window.innerWidth,a=o<=992}));var i=function(e){e.nextElementSibling.classList.remove("is-expanded"),e.classList.remove("is-expanded"),e.setAttribute("aria-expanded","false")};document.body.addEventListener("mousedown",(function(t){e.contains(t.target)||r.forEach((function(e){i(e)}))}));var c=function(e,t){var n=e.getAttribute("aria-".concat(t));n="true"===n?"false":"true",e.setAttribute("aria-".concat(t),n)};t&&(c(t,"expanded"),a&&function(){var e=document.querySelector(".js-megamenu__active-trail");if(e){var t=e.nextElementSibling;c(e,"expanded"),e.classList.add("is-expanded"),t.classList.add("is-expanded")}}(),t.addEventListener("click",(function(){c(t,"expanded"),n&&n.classList.toggle("is-active")}))),r.forEach((function(e){e.addEventListener("click",(function(t){var n=t.currentTarget,o=t.currentTarget.parentElement.querySelector(".js-megamenu__expanded-container");a||function(e){r.forEach((function(t){t!==e&&i(t)}))}(n),c(n,"expanded"),e.classList.toggle("is-expanded"),o.classList.toggle("is-expanded")}))}))}},979:()=>{document.addEventListener("DOMContentLoaded",(function(){!function(e){if(e.getElementById("colorbox")){var t=e.getElementById("cboxPrevious"),n=e.getElementById("cboxNext"),r=e.getElementById("cboxSlideshow");t.textContent="« Prev",n.textContent="Next »",r.textContent="Slideshow"}}(document)}))},725:()=>{!function(e,t,n){t.behaviors.mySlickBehavior={attach:function(t){var r=n.matchMedia("(prefers-reduced-motion: reduce)");function o(){var n=t.querySelector(".slick__slider");!r||r.matches?e(n).on("afterChange",(function(e,t){t.slickSetOption("cssEase","none",!0)})):e(n).on("afterChange",(function(e,t){t.slickSetOption("cssEase","ease",!0)}))}o(),r.addEventListener("change",o)}}}(jQuery,Drupal,window,document)},638:()=>{function e(e,t){for(var n=0;n<e.length;n++)e[n].setAttribute("scope",t)}e(document.querySelectorAll("thead th"),"col"),e(document.querySelectorAll("tbody th"),"row")},965:()=>{var e=document.querySelectorAll("".concat("div.hb-table-pattern__header > div.hb-table-pattern__row > div",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > span",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > p")),t=document.querySelectorAll(".hb-table-row");if(t)for(var n=0;n<t.length;n+=1)for(var r=t[n].querySelectorAll(".hb-table-row__heading"),o=0;o<r.length;o+=1)""!==r[o].innerHTML&&(r[o].innerHTML=e[o].innerHTML)},42:()=>{function e(e){var t=document.createElement("div");t.className="hb-table-wrap",e.parentNode.insertBefore(t,e),t.appendChild(e)}for(var t=document.querySelectorAll("table"),n=document.querySelectorAll(".hb-table-pattern"),r=0;r<t.length;r++)e(t[r]);for(var o=0;o<n.length;o++)e(n[o])},227:()=>{document.querySelectorAll(".hb-timeline__collapsed").forEach((function(e){e.querySelectorAll(".hb-timeline-item").forEach((function(e){e.removeAttribute("open")})),e.querySelectorAll(".hb-timeline-item__summary").forEach((function(e){e.setAttribute("aria-expanded","false"),e.setAttribute("aria-pressed","false")}))}));var e=document.querySelectorAll(".hb-timeline-item");e&&e.forEach((function(e){var t=e.querySelector(".hb-timeline-item__summary"),n=t.getAttribute("aria-expanded");t.addEventListener("click",(function(){"true"===n?(t.setAttribute("aria-expanded","false"),t.setAttribute("aria-pressed","false")):(t.setAttribute("aria-expanded","true"),t.setAttribute("aria-pressed","true")),n=t.getAttribute("aria-expanded")}))}));var t,n=new URLSearchParams(window.location.search),r=Object.fromEntries(n.entries());Object.keys(r).length&&Object.prototype.hasOwnProperty.call(r,"search")&&(t=r.search.toLowerCase(),e.forEach((function(e){if(e.textContent.toLowerCase().includes(t)){var n=e.querySelector("summary");e.setAttribute("open",""),n.setAttribute("aria-expanded","true"),n.setAttribute("aria-pressed","true")}})))},592:()=>{document.querySelectorAll(".hb-vertical-card").forEach((function(e){var t=e.querySelector(".hb-card__title a");t&&(t.addEventListener("focus",(function(){e.classList.add("is-focused")})),t.addEventListener("blur",(function(){e.classList.remove("is-focused")})),e.addEventListener("click",(function(){t.click()})))}))},195:()=>{document.querySelectorAll(".hb-vertical-linked-card").forEach((function(e){var t=e.querySelector(".hb-vertical-linked-card__title__link");t&&(t.addEventListener("focus",(function(){e.classList.add("is-focused")})),t.addEventListener("blur",(function(){e.classList.remove("is-focused")})),e.addEventListener("click",(function(){t.click()})))}))},662:()=>{document.addEventListener("DOMContentLoaded",(function(){!function(){if(document.querySelector(".layout-builder-form")){var e=document.querySelector(".layout-builder-form details");e&&e.removeAttribute("open")}}(document)}))}},t={};function n(r){var o=t[r];if(void 0!==o)return o.exports;var a=t[r]={exports:{}};return e[r](a,a.exports,n),a.exports}(()=>{"use strict";n(642),n(42),n(638),n(965);const e=function(e,t,n){e.setAttribute("aria-expanded",n),t.setAttribute("aria-hidden",!n)};var t,r,o=document.querySelector(".hb-main-nav__toggle"),a=document.querySelector(".hb-main-nav__menu-lv1");o&&(o.addEventListener("click",(function(){var t="true"===o.getAttribute("aria-expanded");e(o,a,!t)})),window.addEventListener("resize",(function(){(t=window.innerWidth)>=992&&!r&&(e(o,a,!0),r=!0),t<992&&r&&(e(o,a,!1),r=!1)})));const i=function(t,n,r){t.preventDefault();var o="true"===t.target.getAttribute("aria-expanded");e(n,r,!o)};var c=document.querySelectorAll(".hb-nested-toggler");if(c)for(var l=function(){var t=window.innerWidth,n=c[u],r=n.getAttribute("id"),o=document.querySelector('[aria-labelledby="'.concat(r,'"]')),a=n.parentNode;if(!o)return"continue";n.addEventListener("click",(function(e){return i(e,n,o)})),n.addEventListener("keydown",(function(t){if(32===t.which){t.preventDefault();var r="true"===t.target.getAttribute("aria-expanded");e(n,o,!r)}})),window.addEventListener("resize",(function(){(t=window.innerWidth)>=992&&e(n,o,!1)})),["focusin","click"].forEach((function(r){document.body.addEventListener(r,(function(r){t>=992&&!a.contains(r.target)&&e(n,o,!1)}),!1)}))},u=0;u<c.length;u+=1)l();var d=document.querySelector(".hb-main-nav__toggle"),s=document.querySelector(".hb-main-nav__menu-lv1"),f=document.querySelectorAll(".hb-nested-toggler"),h=window.innerWidth<992;if(h&&d&&e(d,s,!1),f)for(var m=0;m<f.length;m+=1){var v=f[m],y=v.getAttribute("id"),b=document.querySelector('[aria-labelledby="'.concat(y,'"]')),p=!!v.parentNode.classList.contains("hb-main-nav__item--active-trail");b&&e(v,b,!(!p||!h))}d&&document.querySelector(".hb-main-nav--is-still-loading").classList.remove("hb-main-nav--is-still-loading");var g=document.querySelectorAll(".hb-secondary-toggler");if(g)for(var A=function(){var t=g[S],n=t.getAttribute("id"),r=document.querySelector('[aria-labelledby="'.concat(n,'"]')),o=t.parentNode.classList.contains("hb-secondary-nav__item--active-trail");if(!r)return"continue";o||e(t,r,!1),t.addEventListener("click",(function(e){return i(e,t,r)}))},S=0;S<g.length;S+=1)A();const E=function(e){if(e.length>0){var t=Array.prototype.map.call(e,(function(e){return e.scrollHeight}));return new Promise((function(n){var r=Math.max.apply(null,t),o=t.indexOf(r);Array.prototype.forEach.call(e,(function(e,t){t!==o&&(e.style.minHeight="".concat(r,"px"))})),n()}))}},w=function(e){e.forEach((function(e){e.style.minHeight="auto"}))};function _(e){return function(e){if(Array.isArray(e))return L(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||function(e,t){if(e){if("string"==typeof e)return L(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);return"Object"===n&&e.constructor&&(n=e.constructor.name),"Map"===n||"Set"===n?Array.from(e):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?L(e,t):void 0}}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function L(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}var q=function(){var e=document.querySelector(".hb-stretch-vertical-linked-cards"),t=_(document.querySelectorAll(".hb-vertical-linked-card__title")),n=document.querySelectorAll(".ptype-hs-collection, .ptype-hs-priv-collection");Array.prototype.forEach.call(n,(function(n){var r=_(n.querySelectorAll(".hb-vertical-linked-card"));e&&r.length>0&&w(r),e&&t.length>0&&w(t),e&&window.innerWidth>=576&&(t.length>0?E(t).then((function(){return E(r)})).catch((function(e){return console.error("issue loading equal height cards",e)})):r.length>0&&E(r))}))};setTimeout((function(){q()}),1e3),window.addEventListener("resize",(function(){setTimeout((function(){q()}),500)})),n(808),n(356),n(227),n(418),n(592),n(195),n(979),n(738),n(662),n(697),n(725),n(743)})()})();