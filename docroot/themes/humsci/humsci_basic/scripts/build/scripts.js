!function(e){var t={};function n(r){if(t[r])return t[r].exports;var a=t[r]={i:r,l:!1,exports:{}};return e[r].call(a.exports,a,a.exports,n),a.l=!0,a.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var a in e)n.d(r,a,function(t){return e[t]}.bind(null,a));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=3)}([function(e,t){function n(e){var t=document.createElement("div");t.className="hb-table-wrap",e.parentNode.insertBefore(t,e),t.appendChild(e)}for(var r=document.querySelectorAll("table"),a=document.querySelectorAll(".hb-table-pattern"),i=0;i<r.length;i++)n(r[i]);for(var o=0;o<a.length;o++)n(a[o])},function(e,t){function n(e,t){for(var n=0;n<e.length;n++)e[n].setAttribute("scope",t)}n(document.querySelectorAll("thead th"),"col"),n(document.querySelectorAll("tbody th"),"row")},function(e,t){var n=document.querySelectorAll("".concat("div.hb-table-pattern__header > div.hb-table-pattern__row > div",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > span",", ").concat("div.hb-table-pattern__header > div.hb-table-pattern__row > p")),r=document.querySelectorAll(".hb-table-row");if(r)for(var a=0;a<r.length;a+=1)for(var i=r[a].querySelectorAll(".hb-table-row__heading"),o=0;o<i.length;o+=1)i[o].innerHTML=n[o].innerHTML},function(e,t,n){"use strict";n.r(t);n(0),n(1),n(2);var r,a,i=function(e,t,n){e.setAttribute("aria-expanded",n),t.setAttribute("aria-hidden",!n)},o=document.querySelector(".hb-main-nav__toggle"),l=document.querySelector(".hb-main-nav__menu-lv1");o&&(o.addEventListener("click",(function(e){var t="true"===o.getAttribute("aria-expanded");i(o,l,!t)})),window.addEventListener("resize",(function(e){(r=window.innerWidth)>=992&&!a&&(i(o,l,!0),a=!0),r<992&&a&&(i(o,l,!1),a=!1)})));var c=document.querySelectorAll(".hb-nested-toggler"),u=window.innerWidth;if(c)for(var d=function(e){var t=c[e],n=t.getAttribute("id"),r=document.querySelector('[aria-labelledby="'.concat(n,'"]')),a=t.parentNode;if(!r)return"continue";t.addEventListener("click",(function(e){e.preventDefault();var n="true"===e.target.getAttribute("aria-expanded");i(t,r,!n)})),t.addEventListener("keydown",(function(e){if(32===e.which){e.preventDefault();var n="true"===e.target.getAttribute("aria-expanded");i(t,r,!n)}})),window.addEventListener("resize",(function(){(u=window.innerWidth)>=992&&i(t,r,!1)})),["focusin","click"].forEach((function(e){document.body.addEventListener(e,(function(e){u>=992&&!a.contains(e.target)&&i(t,r,!1)}),!1)}))},f=0;f<c.length;f+=1)d(f);var v=document.querySelector(".hb-main-nav__toggle"),s=document.querySelector(".hb-main-nav__menu-lv1"),b=document.querySelectorAll(".hb-nested-toggler"),h=window.innerWidth<992;if(h&&i(v,s,!1),b)for(var p=0;p<b.length;p+=1){var m=b[p],y=m.getAttribute("id"),g=document.querySelector('[aria-labelledby="'.concat(y,'"]')),_=!!m.parentNode.classList.contains("hb-main-nav__item--active-trail");if(g)i(m,g,!(!_||!h))}document.querySelector(".hb-main-nav--is-still-loading").classList.remove("hb-main-nav--is-still-loading");var S=document.querySelectorAll(".hb-secondary-toggler");if(S)for(var w=function(e){var t=S[e],n=t.getAttribute("id"),r=document.querySelector('[aria-labelledby="'.concat(n,'"]')),a=t.parentNode.classList.contains("hb-secondary-nav__item--active-trail");if(!r)return"continue";a||i(t,r,!1),t.addEventListener("click",(function(e){e.preventDefault();var n="true"===e.target.getAttribute("aria-expanded");i(t,r,!n)}))},A=0;A<S.length;A+=1)w(A);var q=function(e,t){if(!(document.getElementsByClassName(t).length>0))return null;var n=document.getElementsByClassName(e);if(n.length>0){var r=Array.prototype.map.call(n,(function(e){return e.scrollHeight}));r.filter((function(e,t,n){return n.indexOf(e)==t})).length>1&&setTimeout((function(){var e=Math.max.apply(null,r),t=r.indexOf(e);Array.prototype.forEach.call(n,(function(n,r){r!=t&&(n.style.height="".concat(e,"px"))}))}),200)}};q("hb-vertical-linked-card__title","hb-stretch-vertical-linked-cards"),q("hb-vertical-linked-card","hb-stretch-vertical-linked-cards");n(4)},function(e,t){var n=document.querySelectorAll(".field-media-oembed-video");if(n)for(var r=0;r<n.length;r++){var a=n[r].parentNode.parentNode;a.classList.contains("caption")&&(a.style.width="100%")}}]);