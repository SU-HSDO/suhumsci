!function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=3)}([function(e,t){function n(e){var t=document.createElement("div");t.className="hb-table-wrap",e.parentNode.insertBefore(t,e),t.appendChild(e)}for(var r=document.querySelectorAll("table"),o=document.querySelectorAll(".hb-table-pattern"),a=0;a<r.length;a++)n(r[a]);for(var i=0;i<o.length;i++)n(o[i])},function(e,t){function n(e,t){for(var n=0;n<e.length;n++)e[n].setAttribute("scope",t)}n(document.querySelectorAll("thead th"),"col"),n(document.querySelectorAll("tbody th"),"row")},function(e,t){var n=document.querySelectorAll("".concat("div.hb-table-pattern__header > div.hb-table-pattern__row > div",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > span",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > p")),r=document.querySelectorAll(".hb-table-row");if(r)for(var o=0;o<r.length;o+=1)for(var a=r[o].querySelectorAll(".hb-table-row__heading"),i=0;i<a.length;i+=1)a[i].innerHTML=n[i].innerHTML},function(e,t,n){"use strict";n.r(t);n(0),n(1),n(2);var r,o,a=function(e,t,n){e.setAttribute("aria-expanded",n),t.setAttribute("aria-hidden",!n)},i=document.querySelector(".hb-main-nav__toggle"),l=document.querySelector(".hb-main-nav__menu-lv1");i&&(i.addEventListener("click",(function(e){var t="true"===i.getAttribute("aria-expanded");a(i,l,!t)})),window.addEventListener("resize",(function(e){(r=window.innerWidth)>=992&&!o&&(a(i,l,!0),o=!0),r<992&&o&&(a(i,l,!1),o=!1)})));var c=document.querySelectorAll(".hb-nested-toggler"),u=window.innerWidth;if(c)for(var d=function(e){var t=c[e],n=t.getAttribute("id"),r=document.querySelector('[aria-labelledby="'.concat(n,'"]')),o=t.parentNode;if(!r)return"continue";t.addEventListener("click",(function(e){e.preventDefault();var n="true"===e.target.getAttribute("aria-expanded");a(t,r,!n)})),t.addEventListener("keydown",(function(e){if(32===e.which){e.preventDefault();var n="true"===e.target.getAttribute("aria-expanded");a(t,r,!n)}})),window.addEventListener("resize",(function(){(u=window.innerWidth)>=992&&a(t,r,!1)})),["focusin","click"].forEach((function(e){document.body.addEventListener(e,(function(e){u>=992&&!o.contains(e.target)&&a(t,r,!1)}),!1)}))},f=0;f<c.length;f+=1)d(f);var b=document.querySelector(".hb-main-nav__toggle"),v=document.querySelector(".hb-main-nav__menu-lv1"),s=document.querySelectorAll(".hb-nested-toggler"),h=window.innerWidth<992;if(h&&a(b,v,!1),s)for(var m=0;m<s.length;m+=1){var g=s[m],y=g.getAttribute("id"),p=document.querySelector('[aria-labelledby="'.concat(y,'"]')),_=!!g.parentNode.classList.contains("hb-main-nav__item--active-trail");if(p)a(g,p,!(!_||!h))}document.querySelector(".hb-main-nav--is-still-loading").classList.remove("hb-main-nav--is-still-loading");var w=document.querySelectorAll(".hb-secondary-toggler");if(w)for(var S=function(e){var t=w[e],n=t.getAttribute("id"),r=document.querySelector('[aria-labelledby="'.concat(n,'"]')),o=t.parentNode.classList.contains("hb-secondary-nav__item--active-trail");if(!r)return"continue";o||a(t,r,!1),t.addEventListener("click",(function(e){e.preventDefault();var n="true"===e.target.getAttribute("aria-expanded");a(t,r,!n)}))},A=0;A<w.length;A+=1)S(A);function q(e){console.log(e),e.width=e.contentWindow.document.body.scrollWidth,e.height=e.contentWindow.document.body.scrollHeight}(function(e,t){if(!(document.getElementsByClassName(t).length>0))return null;var n=document.getElementsByClassName(e);if(n.length>0){var r=Array.prototype.map.call(n,(function(e){return e.scrollHeight}));if(r.filter((function(e,t,n){return n.indexOf(e)==t})).length>1){var o=Math.max.apply(null,r),a=r.indexOf(o);Array.prototype.forEach.call(n,(function(e,t){t!=a&&(e.style.height="".concat(o,"px"))}))}}})("hb-vertical-linked-card__title","hb-stretch-vertical-linked-cards"),window.addEventListener("DOMContentLoaded",(function(e){q(document.querySelector(".google-form"));for(var t=document.querySelectorAll("iframe"),n=0;n<t.length;n++)q(t[n])}))}]);