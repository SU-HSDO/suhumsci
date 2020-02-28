/**
 * Add .js class, and remove .no-js (if javaScript loads on the page)
 * This allows us to apply specific styles for noJS solutions.
 * TODO: I tried adding this custom js instead of modernizr, but I dont think it's working
 */

/**
 * @param element
 */

function addJS(element) {
  element.classList.remove('no-js');
  element.classList.add('js');
}

// Select the html element
const element = document.querySelector('html');
addJS(element);
