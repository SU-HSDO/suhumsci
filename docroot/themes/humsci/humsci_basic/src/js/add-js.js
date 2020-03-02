/**
 * Add .js class, and remove .no-js (if JavaScript loads on the page)
 * This allows us to apply specific styles for no-JS solutions.
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
