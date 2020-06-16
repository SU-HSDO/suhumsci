'use strict';

var debounce = function debounce(func, delay) {
  var inDebounce;
  return function() {
    var context = this;
    var args = arguments;
    clearTimeout(inDebounce);
    inDebounce = setTimeout(function() {
      return func.apply(context, args);
    }, delay);
  };
};

function showBackToTop() {
  var topOfContent = document.querySelector('#main-content');
  var button = document.querySelector('.hs-back-to-top');
  var mainContentPosition = topOfContent.offsetTop;
  var yPosition = window.scrollY || window.pageYOffset;

  if (topOfContent && button) {
    if (mainContentPosition > yPosition) {
      button.setAttribute('hidden', '');
    } else {
      button.removeAttribute('hidden');
    }
  }
}

window.addEventListener('load', showBackToTop);
window.addEventListener('scroll', debounce(showBackToTop, 100));
