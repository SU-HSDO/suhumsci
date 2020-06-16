"use strict";

var debounce = function debounce(func, delay) {
  var inDebounce;
  return function () {
    var context = this;
    var args = arguments;
    clearTimeout(inDebounce);
    inDebounce = setTimeout(function () {
      return func.apply(context, args);
    }, delay);
  };
};

function showBackToTop() {
  var topOfContent = document.querySelector('#main-content');
  var button = document.querySelector('.hs-back-to-top');
  var mainContentPosition = topOfContent.offsetTop;

  if (button) {
    button.setAttribute('hidden', '');
  }

  if (topOfContent && button) {
    if (mainContentPosition > window.scrollY) {
      button.setAttribute('hidden', '');
    } else {
      button.removeAttribute('hidden');
    }
  }
}

window.addEventListener('load', showBackToTop);
window.addEventListener('scroll', debounce(showBackToTop, 100));
