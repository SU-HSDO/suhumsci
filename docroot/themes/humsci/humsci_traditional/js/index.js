(()=>{var e={418:()=>{function e(e){return function(e){if(Array.isArray(e))return t(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||function(e,r){if(e){if("string"==typeof e)return t(e,r);var n=Object.prototype.toString.call(e).slice(8,-1);return"Object"===n&&e.constructor&&(n=e.constructor.name),"Map"===n||"Set"===n?Array.from(e):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?t(e,r):void 0}}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function t(e,t){(null==t||t>e.length)&&(t=e.length);for(var r=0,n=new Array(t);r<t;r++)n[r]=e[r];return n}function r(e,t){"closeAll"===t?e.forEach((function(e){e.removeAttribute("open")})):e.forEach((function(e){e.setAttribute("open","")}))}function n(e,t){e.forEach((function(e){e.innerText="closeAll"===t?"Expand All":"Collapse All"}))}var o=e(document.querySelectorAll("details"));if(o.length>=1){var a=!1;o.forEach((function(e){if(e.classList.contains("hb-accordion_toggle-all")){var t=function(){var e=document.createElement("Button");return e.innerText="Expand All",e.classList.add("hb-link"),e.classList.add("hb-accordion-toggle-all"),e}();e.parentNode.insertBefore(t,e)}}));var i=e(document.querySelectorAll(".hb-accordion-toggle-all"));i.forEach((function(e){e.addEventListener("click",(function(t){t.preventDefault(),a?(r(o,"closeAll"),n(i,"closeAll"),a=!1):(r(o,"openAll"),n(i,"openAll"),a=!0),e.scrollIntoView(!0)}))}))}},356:()=>{var e=document.querySelector(".hb-has-animation-enhancements"),t=document.querySelector(".hb-experimental"),r=[document.querySelectorAll(".hs-font-lead")],n=[document.querySelectorAll(".hb-gradient-hero"),document.querySelectorAll(".hb-gradient-hero__text"),document.querySelectorAll(".hb-gradient-hero__image-wrapper"),document.querySelectorAll(".field-hs-gradient-hero-image"),document.querySelectorAll(".hb-hero-overlay"),document.querySelectorAll(".hb-hero-overlay__text"),document.querySelectorAll(".hb-hero-overlay__image-wrapper"),document.querySelectorAll(".field-hs-hero-image"),document.querySelectorAll(".hs-font-splash")];t&&n.push(r);var o=new IntersectionObserver((function(e){e.forEach((function(e){e.intersectionRatio>0&&e.target.classList.add("animate")}))}));e&&n.forEach((function(e){e&&e.forEach((function(e){o.observe(e)}))}))},642:()=>{function e(e,t){(null==t||t>e.length)&&(t=e.length);for(var r=0,n=new Array(t);r<t;r++)n[r]=e[r];return n}var t,r=document.querySelectorAll(".paragraph--type--hs-carousel, .paragraph--type--hs-gradient-hero-slider, .paragraph--type--hs-sptlght-slder"),n=function(e,t){return e.setAttribute("style","min-height: ".concat(t,"px"))},o=function(){var t,o;r.forEach((function(r){t=[0];var a,i=r.querySelectorAll(".hb-hero-overlay__text, .hb-gradient-hero__text, .hb-spotlight__text");i.forEach((function(e){e.removeAttribute("style");var r=e.offsetHeight;r=parseInt(r,10),t.push(r)})),o=Math.max.apply(Math,function(t){if(Array.isArray(t))return e(t)}(a=t)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(a)||function(t,r){if(t){if("string"==typeof t)return e(t,r);var n=Object.prototype.toString.call(t).slice(8,-1);return"Object"===n&&t.constructor&&(n=t.constructor.name),"Map"===n||"Set"===n?Array.from(t):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?e(t,r):void 0}}(a)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()),i.forEach((function(e){return n(e,o)})),i.forEach((function(e){return function(e){return e.classList.contains("hb-spotlight__text")}(e)&&n(e,o)}))}))};r.length>0&&(o(),window.addEventListener("resize",(function(){clearTimeout(t),t=setTimeout(o,250)})))},808:()=>{var e=document.querySelectorAll(".field-media-oembed-video");if(e&&e.length>0)for(var t=0;t<e.length;t++){var r=e[t];if(r.parentNode&&r.parentNode.parentNode&&"FIGURE"===r.parentNode.parentNode.nodeName){var n=r.parentNode.parentNode;n.classList.contains("caption")&&(n.style.width="100%")}}},743:()=>{var e=document.querySelector(".js-megamenu");if(e){var t=document.querySelector(".js-megamenu__mobile-btn"),r=e.querySelector(".js-megamenu__list--main"),n=e.querySelectorAll(".js-megamenu__toggle"),o=window.innerWidth,a=o<992;window.addEventListener("resize",(function(){o=window.innerWidth,a=o<=992}));var i=function(e){e.nextElementSibling.classList.remove("is-expanded"),e.classList.remove("is-expanded"),e.setAttribute("aria-expanded","false")};document.body.addEventListener("mousedown",(function(t){e.contains(t.target)||n.forEach((function(e){i(e)}))}));var l=function(e,t){var r=e.getAttribute("aria-".concat(t));r="true"===r?"false":"true",e.setAttribute("aria-".concat(t),r)};t&&(l(t,"expanded"),a&&function(){var e=document.querySelector(".js-megamenu__active-trail");if(e){var t=e.nextElementSibling;l(e,"expanded"),e.classList.add("is-expanded"),t.classList.add("is-expanded")}}(),t.addEventListener("click",(function(){l(t,"expanded"),r&&r.classList.toggle("is-active")}))),n.forEach((function(e){e.addEventListener("click",(function(t){var r=t.currentTarget,o=t.currentTarget.parentElement.querySelector(".js-megamenu__expanded-container");a||function(e){n.forEach((function(t){t!==e&&i(t)}))}(r),l(r,"expanded"),e.classList.toggle("is-expanded"),o.classList.toggle("is-expanded")}))}))}},638:()=>{function e(e,t){for(var r=0;r<e.length;r++)e[r].setAttribute("scope",t)}e(document.querySelectorAll("thead th"),"col"),e(document.querySelectorAll("tbody th"),"row")},965:()=>{var e=document.querySelectorAll("".concat("div.hb-table-pattern__header > div.hb-table-pattern__row > div",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > span",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > p")),t=document.querySelectorAll(".hb-table-row");if(t)for(var r=0;r<t.length;r+=1)for(var n=t[r].querySelectorAll(".hb-table-row__heading"),o=0;o<n.length;o+=1)""!==n[o].innerHTML&&(n[o].innerHTML=e[o].innerHTML)},42:()=>{function e(e){var t=document.createElement("div");t.className="hb-table-wrap",e.parentNode.insertBefore(t,e),t.appendChild(e)}for(var t=document.querySelectorAll("table"),r=document.querySelectorAll(".hb-table-pattern"),n=0;n<t.length;n++)e(t[n]);for(var o=0;o<r.length;o++)e(r[o])},227:()=>{document.querySelectorAll(".hb-timeline__collapsed").forEach((function(e){e.querySelectorAll(".hb-timeline-item").forEach((function(e){e.removeAttribute("open")})),e.querySelectorAll(".hb-timeline-item__summary").forEach((function(e){e.setAttribute("aria-expanded","false"),e.setAttribute("aria-pressed","false")}))}));var e=document.querySelectorAll(".hb-timeline-item");e&&e.forEach((function(e){var t=e.querySelector(".hb-timeline-item__summary"),r=t.getAttribute("aria-expanded");t.addEventListener("click",(function(){"true"===r?(t.setAttribute("aria-expanded","false"),t.setAttribute("aria-pressed","false")):(t.setAttribute("aria-expanded","true"),t.setAttribute("aria-pressed","true")),r=t.getAttribute("aria-expanded")}))}))}},t={};function r(n){var o=t[n];if(void 0!==o)return o.exports;var a=t[n]={exports:{}};return e[n](a,a.exports,r),a.exports}(()=>{"use strict";r(642),r(42),r(638),r(965);const e=function(e,t,r){e.setAttribute("aria-expanded",r),t.setAttribute("aria-hidden",!r)};var t,n,o=document.querySelector(".hb-main-nav__toggle"),a=document.querySelector(".hb-main-nav__menu-lv1");o&&(o.addEventListener("click",(function(){var t="true"===o.getAttribute("aria-expanded");e(o,a,!t)})),window.addEventListener("resize",(function(){(t=window.innerWidth)>=992&&!n&&(e(o,a,!0),n=!0),t<992&&n&&(e(o,a,!1),n=!1)})));const i=function(t,r,n){t.preventDefault();var o="true"===t.target.getAttribute("aria-expanded");e(r,n,!o)};var l=document.querySelectorAll(".hb-nested-toggler");if(l)for(var c=function(){var t=window.innerWidth,r=l[u],n=r.getAttribute("id"),o=document.querySelector('[aria-labelledby="'.concat(n,'"]')),a=r.parentNode;if(!o)return"continue";r.addEventListener("click",(function(e){return i(e,r,o)})),r.addEventListener("keydown",(function(t){if(32===t.which){t.preventDefault();var n="true"===t.target.getAttribute("aria-expanded");e(r,o,!n)}})),window.addEventListener("resize",(function(){(t=window.innerWidth)>=992&&e(r,o,!1)})),["focusin","click"].forEach((function(n){document.body.addEventListener(n,(function(n){t>=992&&!a.contains(n.target)&&e(r,o,!1)}),!1)}))},u=0;u<l.length;u+=1)c();var d=document.querySelector(".hb-main-nav__toggle"),s=document.querySelector(".hb-main-nav__menu-lv1"),f=document.querySelectorAll(".hb-nested-toggler"),h=window.innerWidth<992;if(h&&d&&e(d,s,!1),f)for(var m=0;m<f.length;m+=1){var p=f[m],v=p.getAttribute("id"),b=document.querySelector('[aria-labelledby="'.concat(v,'"]')),y=!!p.parentNode.classList.contains("hb-main-nav__item--active-trail");b&&e(p,b,!(!y||!h))}d&&document.querySelector(".hb-main-nav--is-still-loading").classList.remove("hb-main-nav--is-still-loading");var g=document.querySelectorAll(".hb-secondary-toggler");if(g)for(var A=function(){var t=g[S],r=t.getAttribute("id"),n=document.querySelector('[aria-labelledby="'.concat(r,'"]')),o=t.parentNode.classList.contains("hb-secondary-nav__item--active-trail");if(!n)return"continue";o||e(t,n,!1),t.addEventListener("click",(function(e){return i(e,t,n)}))},S=0;S<g.length;S+=1)A();const _=function(e){if(e.length>0){var t=Array.prototype.map.call(e,(function(e){return e.scrollHeight}));return new Promise((function(r){var n=Math.max.apply(null,t),o=t.indexOf(n);Array.prototype.forEach.call(e,(function(e,t){t!==o&&(e.style.minHeight="".concat(n,"px"))})),r()}))}},q=function(e){e.forEach((function(e){e.style.minHeight="auto"}))};function w(e){return function(e){if(Array.isArray(e))return E(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||function(e,t){if(e){if("string"==typeof e)return E(e,t);var r=Object.prototype.toString.call(e).slice(8,-1);return"Object"===r&&e.constructor&&(r=e.constructor.name),"Map"===r||"Set"===r?Array.from(e):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?E(e,t):void 0}}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function E(e,t){(null==t||t>e.length)&&(t=e.length);for(var r=0,n=new Array(t);r<t;r++)n[r]=e[r];return n}var x=function(){var e=document.querySelector(".hb-stretch-vertical-linked-cards"),t=w(document.querySelectorAll(".hb-vertical-linked-card__title")),r=w(document.querySelectorAll(".ptype-hs-collection"));Array.prototype.forEach.call(r,(function(r){var n=w(r.querySelectorAll(".hb-vertical-linked-card"));console.log(n),e&&n.length>0&&q(n),e&&t.length>0&&q(t),e&&window.innerWidth>=576&&(t.length>0?_(t).then((function(){return _(n)})).catch((function(e){return console.error("issue loading equal height cards",e)})):n.length>0&&_(n))}))};setTimeout((function(){x()}),1e3),window.addEventListener("resize",(function(){setTimeout((function(){x()}),500)})),r(808),r(356),r(227),r(418),r(743)})()})();