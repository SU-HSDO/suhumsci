(()=>{var e={356:()=>{var e=!1,t=document.querySelectorAll([".hb-hero-overlay",".hb-hero-overlay__text",".hb-hero-overlay__image-wrapper",".field-hs-hero-image",".hb-gradient-hero",".hb-gradient-hero__text",".hb-gradient-hero__image-wrapper",".field-hs-gradient-hero-image",".hs-font-splash"]),r=document.querySelectorAll(".hb-has-animation-enhancements"),n=function(){for(var r=0;r<t.length;r++){if(!t[r].classList.contains("animate")){e=!1;break}e=!0}},a=function r(){for(var a=0;a<t.length;a++)i=t[a],o=void 0,l=void 0,o=i.getBoundingClientRect(),l=o.bottom-.18*o.bottom,o.top>=0&&l<=(window.innerHeight||document.documentElement.clientHeight)&&t[a].classList.add("animate"),n(),e&&document.removeEventListener("scroll",r);var i,o,l;scroll(r)};r&&(a(),document.addEventListener("scroll",a))},642:()=>{var e,t=document.querySelectorAll(".paragraph--type--hs-carousel, .paragraph--type--hs-gradient-hero-slider, .paragraph--type--hs-sptlght-slder"),r=function(e,t){return e.setAttribute("style","min-height: ".concat(t,"px"))},n=function(){var e,n;t.forEach((function(t){e=[0];var a,i=t.querySelectorAll(".hb-hero-overlay__text, .hb-gradient-hero__text, .hb-spotlight__text");i.forEach((function(t){t.removeAttribute("style");var r=t.offsetHeight;r=parseInt(r,10),e.push(r)})),n=Math.max.apply(Math,function(e){if(Array.isArray(e)){for(var t=0,r=new Array(e.length);t<e.length;t++)r[t]=e[t];return r}}(a=e)||function(e){if(Symbol.iterator in Object(e)||"[object Arguments]"===Object.prototype.toString.call(e))return Array.from(e)}(a)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance")}()),window.innerWidth>768&&i.forEach((function(e){return r(e,n)})),i.forEach((function(e){return function(e){return e.classList.contains("hb-spotlight__text")}(e)&&r(e,n)}))}))};t.length>0&&(n(),window.addEventListener("resize",(function(){clearTimeout(e),e=setTimeout(n,250)})))},808:()=>{var e=document.querySelectorAll(".field-media-oembed-video");if(e&&e.length>0)for(var t=0;t<e.length;t++){var r=e[t];if(r.parentNode&&r.parentNode.parentNode&&"FIGURE"===r.parentNode.parentNode.nodeName){var n=r.parentNode.parentNode;n.classList.contains("caption")&&(n.style.width="100%")}}},638:()=>{function e(e,t){for(var r=0;r<e.length;r++)e[r].setAttribute("scope",t)}e(document.querySelectorAll("thead th"),"col"),e(document.querySelectorAll("tbody th"),"row")},965:()=>{var e=document.querySelectorAll("".concat("div.hb-table-pattern__header > div.hb-table-pattern__row > div",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > span",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > p")),t=document.querySelectorAll(".hb-table-row");if(t)for(var r=0;r<t.length;r+=1)for(var n=t[r].querySelectorAll(".hb-table-row__heading"),a=0;a<n.length;a+=1)n[a].innerHTML=e[a].innerHTML},42:()=>{function e(e){var t=document.createElement("div");t.className="hb-table-wrap",e.parentNode.insertBefore(t,e),t.appendChild(e)}for(var t=document.querySelectorAll("table"),r=document.querySelectorAll(".hb-table-pattern"),n=0;n<t.length;n++)e(t[n]);for(var a=0;a<r.length;a++)e(r[a])},227:()=>{document.querySelectorAll(".hb-timeline__collapsed").forEach((function(e){e.querySelectorAll(".hb-timeline-item").forEach((function(e){e.removeAttribute("open")})),e.querySelectorAll(".hb-timeline-item__summary").forEach((function(e){e.setAttribute("aria-expanded","false"),e.setAttribute("aria-pressed","false")}))}));var e=document.querySelectorAll(".hb-timeline-item");e&&e.forEach((function(e){var t=e.querySelector(".hb-timeline-item__summary"),r=t.getAttribute("aria-expanded");e.addEventListener("click",(function(){"true"===r?(t.setAttribute("aria-expanded","false"),t.setAttribute("aria-pressed","false")):(t.setAttribute("aria-expanded","true"),t.setAttribute("aria-pressed","true")),r=t.getAttribute("aria-expanded")}))}))}},t={};function r(n){var a=t[n];if(void 0!==a)return a.exports;var i=t[n]={exports:{}};return e[n](i,i.exports,r),i.exports}(()=>{"use strict";r(642),r(42),r(638),r(965);const e=function(e,t,r){e.setAttribute("aria-expanded",r),t.setAttribute("aria-hidden",!r)};var t,n,a=document.querySelector(".hb-main-nav__toggle"),i=document.querySelector(".hb-main-nav__menu-lv1");a&&(a.addEventListener("click",(function(){var t="true"===a.getAttribute("aria-expanded");e(a,i,!t)})),window.addEventListener("resize",(function(){(t=window.innerWidth)>=992&&!n&&(e(a,i,!0),n=!0),t<992&&n&&(e(a,i,!1),n=!1)})));const o=function(t,r,n){t.preventDefault();var a="true"===t.target.getAttribute("aria-expanded");e(r,n,!a)};var l=document.querySelectorAll(".hb-nested-toggler");if(l)for(var c=function(t){var r=window.innerWidth,n=l[t],a=n.getAttribute("id"),i=document.querySelector('[aria-labelledby="'.concat(a,'"]')),c=n.parentNode;if(!i)return"continue";n.addEventListener("click",(function(e){return o(e,n,i)})),n.addEventListener("keydown",(function(t){if(32===t.which){t.preventDefault();var r="true"===t.target.getAttribute("aria-expanded");e(n,i,!r)}})),window.addEventListener("resize",(function(){(r=window.innerWidth)>=992&&e(n,i,!1)})),["focusin","click"].forEach((function(t){document.body.addEventListener(t,(function(t){r>=992&&!c.contains(t.target)&&e(n,i,!1)}),!1)}))},u=0;u<l.length;u+=1)c(u);var d=document.querySelector(".hb-main-nav__toggle"),h=document.querySelector(".hb-main-nav__menu-lv1"),s=document.querySelectorAll(".hb-nested-toggler"),f=window.innerWidth<992;if(f&&e(d,h,!1),s)for(var m=0;m<s.length;m+=1){var v=s[m],b=v.getAttribute("id"),p=document.querySelector('[aria-labelledby="'.concat(b,'"]')),g=!!v.parentNode.classList.contains("hb-main-nav__item--active-trail");p&&e(v,p,!(!g||!f))}document.querySelector(".hb-main-nav--is-still-loading").classList.remove("hb-main-nav--is-still-loading");var y=document.querySelectorAll(".hb-secondary-toggler");if(y)for(var A=function(t){var r=y[t],n=r.getAttribute("id"),a=document.querySelector('[aria-labelledby="'.concat(n,'"]')),i=r.parentNode.classList.contains("hb-secondary-nav__item--active-trail");if(!a)return"continue";i||e(r,a,!1),r.addEventListener("click",(function(e){return o(e,r,a)}))},_=0;_<y.length;_+=1)A(_);const w=function(e){if(e.length>0){var t=Array.prototype.map.call(e,(function(e){return e.scrollHeight}));return new Promise((function(r){var n=Math.max.apply(null,t),a=t.indexOf(n);Array.prototype.forEach.call(e,(function(e,t){t!==a&&(e.style.minHeight="".concat(n,"px"))})),r()}))}},S=function(e){e.forEach((function(e){e.style.minHeight="auto"}))};function q(e){return function(e){if(Array.isArray(e)){for(var t=0,r=new Array(e.length);t<e.length;t++)r[t]=e[t];return r}}(e)||function(e){if(Symbol.iterator in Object(e)||"[object Arguments]"===Object.prototype.toString.call(e))return Array.from(e)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance")}()}var E=function(){var e=document.querySelector(".hb-stretch-vertical-linked-cards"),t=q(document.querySelectorAll(".hb-vertical-linked-card__title")),r=q(document.querySelectorAll(".hb-vertical-linked-card"));e&&r.length>0&&S(r),e&&t.length>0&&S(t),e&&window.innerWidth>=576&&(t.length>0?w(t).then((function(){return w(r)})).catch((function(e){return console.error("issue loading equal height cards",e)})):r.length>0&&w(r))};setTimeout((function(){E()}),1e3),window.addEventListener("resize",(function(){setTimeout((function(){E()}),500)})),r(808),r(356),r(227)})()})();