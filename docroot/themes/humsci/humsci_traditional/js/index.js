(()=>{var e={418:()=>{function e(e){return function(e){if(Array.isArray(e)){for(var t=0,n=new Array(e.length);t<e.length;t++)n[t]=e[t];return n}}(e)||function(e){if(Symbol.iterator in Object(e)||"[object Arguments]"===Object.prototype.toString.call(e))return Array.from(e)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance")}()}function t(e,t){"closeAll"===t?e.forEach((function(e){e.removeAttribute("open")})):e.forEach((function(e){e.setAttribute("open","")}))}function n(e,t){e.forEach((function(e){e.innerText="closeAll"===t?"Expand All":"Collapse All"}))}var r=e(document.querySelectorAll("details"));if(r.length>=1){var a=!1;r.forEach((function(e){if(e.classList.contains("hb-accordion_toggle-all")){var t=function(){var e=document.createElement("Button");return e.innerText="Expand All",e.classList.add("hb-link"),e.classList.add("hb-accordion-toggle-all"),e}();e.parentNode.insertBefore(t,e)}}));var o=e(document.querySelectorAll(".hb-accordion-toggle-all"));o.forEach((function(e){e.addEventListener("click",(function(i){i.preventDefault(),a?(t(r,"closeAll"),n(o,"closeAll"),a=!1):(t(r,"openAll"),n(o,"openAll"),a=!0),e.scrollIntoView(!0)}))}))}},356:()=>{var e,t=!1,n=window.requestAnimationFrame||function(e){window.setTimeout(e,1e3/60)},r=function(e){if(Array.isArray(e)){for(var t=0,n=new Array(e.length);t<e.length;t++)n[t]=e[t];return n}}(e=document.querySelectorAll(".hb-experimental"))||function(e){if(Symbol.iterator in Object(e)||"[object Arguments]"===Object.prototype.toString.call(e))return Array.from(e)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance")}(),a=[".hb-hero-overlay",".hb-hero-overlay__text",".hb-hero-overlay__image-wrapper",".field-hs-hero-image",".hb-gradient-hero",".hb-gradient-hero__text",".hb-gradient-hero__image-wrapper",".field-hs-gradient-hero-image",".hs-font-splash"];r.length&&a.push([".hs-font-lead"]);var o=document.querySelectorAll(a);document.querySelectorAll(".hb-has-animation-enhancements").length&&function e(){o.forEach((function(e){var n,r;n=e.getBoundingClientRect(),r=n.bottom-.18*n.bottom,n.top>=0&&r<=(window.innerHeight||document.documentElement.clientHeight)&&e.classList.add("animate"),function(){for(var e=0;e<o.length;e++){if(!o[e].classList.contains("animate")){t=!1;break}t=!0}}(),t&&window.cancelAnimationFrame})),n(e)}()},642:()=>{var e,t=document.querySelectorAll(".paragraph--type--hs-carousel, .paragraph--type--hs-gradient-hero-slider, .paragraph--type--hs-sptlght-slder"),n=function(e,t){return e.setAttribute("style","min-height: ".concat(t,"px"))},r=function(){var e,r;t.forEach((function(t){e=[0];var a,o=t.querySelectorAll(".hb-hero-overlay__text, .hb-gradient-hero__text, .hb-spotlight__text");o.forEach((function(t){t.removeAttribute("style");var n=t.offsetHeight;n=parseInt(n,10),e.push(n)})),r=Math.max.apply(Math,function(e){if(Array.isArray(e)){for(var t=0,n=new Array(e.length);t<e.length;t++)n[t]=e[t];return n}}(a=e)||function(e){if(Symbol.iterator in Object(e)||"[object Arguments]"===Object.prototype.toString.call(e))return Array.from(e)}(a)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance")}()),window.innerWidth>768&&o.forEach((function(e){return n(e,r)})),o.forEach((function(e){return function(e){return e.classList.contains("hb-spotlight__text")}(e)&&n(e,r)}))}))};t.length>0&&(r(),window.addEventListener("resize",(function(){clearTimeout(e),e=setTimeout(r,250)})))},808:()=>{var e=document.querySelectorAll(".field-media-oembed-video");if(e&&e.length>0)for(var t=0;t<e.length;t++){var n=e[t];if(n.parentNode&&n.parentNode.parentNode&&"FIGURE"===n.parentNode.parentNode.nodeName){var r=n.parentNode.parentNode;r.classList.contains("caption")&&(r.style.width="100%")}}},743:()=>{var e=document.querySelector(".js-megamenu");if(e){var t=document.querySelector(".js-megamenu__mobile-btn"),n=e.querySelector(".js-megamenu__list--main"),r=e.querySelectorAll(".js-megamenu__toggle"),a=function(e){e.nextElementSibling.classList.remove("is-expanded"),e.classList.remove("is-expanded"),e.setAttribute("aria-expanded","false")};document.body.addEventListener("mousedown",(function(t){e.contains(t.target)||r.forEach((function(e){a(e)}))}));var o=function(e,t){var n=e.getAttribute("aria-".concat(t));n="true"===n?"false":"true",e.setAttribute("aria-".concat(t),n)};t&&(o(t,"expanded"),t.addEventListener("click",(function(){o(t,"expanded"),n&&n.classList.toggle("is-active")}))),r.forEach((function(e){e.addEventListener("click",(function(t){var n=t.currentTarget,i=t.currentTarget.parentElement.querySelector(".js-megamenu__expanded-container");!function(e){r.forEach((function(t){t!==e&&a(t)}))}(n),o(n,"expanded"),e.classList.toggle("is-expanded"),i.classList.toggle("is-expanded")}))}))}},638:()=>{function e(e,t){for(var n=0;n<e.length;n++)e[n].setAttribute("scope",t)}e(document.querySelectorAll("thead th"),"col"),e(document.querySelectorAll("tbody th"),"row")},965:()=>{var e=document.querySelectorAll("".concat("div.hb-table-pattern__header > div.hb-table-pattern__row > div",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > span",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > p")),t=document.querySelectorAll(".hb-table-row");if(t)for(var n=0;n<t.length;n+=1)for(var r=t[n].querySelectorAll(".hb-table-row__heading"),a=0;a<r.length;a+=1)r[a].innerHTML=e[a].innerHTML},42:()=>{function e(e){var t=document.createElement("div");t.className="hb-table-wrap",e.parentNode.insertBefore(t,e),t.appendChild(e)}for(var t=document.querySelectorAll("table"),n=document.querySelectorAll(".hb-table-pattern"),r=0;r<t.length;r++)e(t[r]);for(var a=0;a<n.length;a++)e(n[a])},227:()=>{document.querySelectorAll(".hb-timeline__collapsed").forEach((function(e){e.querySelectorAll(".hb-timeline-item").forEach((function(e){e.removeAttribute("open")})),e.querySelectorAll(".hb-timeline-item__summary").forEach((function(e){e.setAttribute("aria-expanded","false"),e.setAttribute("aria-pressed","false")}))}));var e=document.querySelectorAll(".hb-timeline-item");e&&e.forEach((function(e){var t=e.querySelector(".hb-timeline-item__summary"),n=t.getAttribute("aria-expanded");t.addEventListener("click",(function(){"true"===n?(t.setAttribute("aria-expanded","false"),t.setAttribute("aria-pressed","false")):(t.setAttribute("aria-expanded","true"),t.setAttribute("aria-pressed","true")),n=t.getAttribute("aria-expanded")}))}))}},t={};function n(r){var a=t[r];if(void 0!==a)return a.exports;var o=t[r]={exports:{}};return e[r](o,o.exports,n),o.exports}(()=>{"use strict";n(642),n(42),n(638),n(965);const e=function(e,t,n){e.setAttribute("aria-expanded",n),t.setAttribute("aria-hidden",!n)};var t,r,a=document.querySelector(".hb-main-nav__toggle"),o=document.querySelector(".hb-main-nav__menu-lv1");a&&(a.addEventListener("click",(function(){var t="true"===a.getAttribute("aria-expanded");e(a,o,!t)})),window.addEventListener("resize",(function(){(t=window.innerWidth)>=992&&!r&&(e(a,o,!0),r=!0),t<992&&r&&(e(a,o,!1),r=!1)})));const i=function(t,n,r){t.preventDefault();var a="true"===t.target.getAttribute("aria-expanded");e(n,r,!a)};var l=document.querySelectorAll(".hb-nested-toggler");if(l)for(var c=function(t){var n=window.innerWidth,r=l[t],a=r.getAttribute("id"),o=document.querySelector('[aria-labelledby="'.concat(a,'"]')),c=r.parentNode;if(!o)return"continue";r.addEventListener("click",(function(e){return i(e,r,o)})),r.addEventListener("keydown",(function(t){if(32===t.which){t.preventDefault();var n="true"===t.target.getAttribute("aria-expanded");e(r,o,!n)}})),window.addEventListener("resize",(function(){(n=window.innerWidth)>=992&&e(r,o,!1)})),["focusin","click"].forEach((function(t){document.body.addEventListener(t,(function(t){n>=992&&!c.contains(t.target)&&e(r,o,!1)}),!1)}))},u=0;u<l.length;u+=1)c(u);var d=document.querySelector(".hb-main-nav__toggle"),s=document.querySelector(".hb-main-nav__menu-lv1"),h=document.querySelectorAll(".hb-nested-toggler"),f=window.innerWidth<992;if(f&&d&&e(d,s,!1),h)for(var m=0;m<h.length;m+=1){var b=h[m],p=b.getAttribute("id"),v=document.querySelector('[aria-labelledby="'.concat(p,'"]')),g=!!b.parentNode.classList.contains("hb-main-nav__item--active-trail");v&&e(b,v,!(!g||!f))}d&&document.querySelector(".hb-main-nav--is-still-loading").classList.remove("hb-main-nav--is-still-loading");var y=document.querySelectorAll(".hb-secondary-toggler");if(y)for(var A=function(t){var n=y[t],r=n.getAttribute("id"),a=document.querySelector('[aria-labelledby="'.concat(r,'"]')),o=n.parentNode.classList.contains("hb-secondary-nav__item--active-trail");if(!a)return"continue";o||e(n,a,!1),n.addEventListener("click",(function(e){return i(e,n,a)}))},_=0;_<y.length;_+=1)A(_);const w=function(e){if(e.length>0){var t=Array.prototype.map.call(e,(function(e){return e.scrollHeight}));return new Promise((function(n){var r=Math.max.apply(null,t),a=t.indexOf(r);Array.prototype.forEach.call(e,(function(e,t){t!==a&&(e.style.minHeight="".concat(r,"px"))})),n()}))}},S=function(e){e.forEach((function(e){e.style.minHeight="auto"}))};function E(e){return function(e){if(Array.isArray(e)){for(var t=0,n=new Array(e.length);t<e.length;t++)n[t]=e[t];return n}}(e)||function(e){if(Symbol.iterator in Object(e)||"[object Arguments]"===Object.prototype.toString.call(e))return Array.from(e)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance")}()}var q=function(){var e=document.querySelector(".hb-stretch-vertical-linked-cards"),t=E(document.querySelectorAll(".hb-vertical-linked-card__title")),n=E(document.querySelectorAll(".hb-vertical-linked-card"));e&&n.length>0&&S(n),e&&t.length>0&&S(t),e&&window.innerWidth>=576&&(t.length>0?w(t).then((function(){return w(n)})).catch((function(e){return console.error("issue loading equal height cards",e)})):n.length>0&&w(n))};setTimeout((function(){q()}),1e3),window.addEventListener("resize",(function(){setTimeout((function(){q()}),500)})),n(808),n(356),n(227),n(418),n(743)})()})();