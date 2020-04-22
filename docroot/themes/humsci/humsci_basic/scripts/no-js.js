/**
 * Add .js class, and remove .no-js (if JavaScript loads on the page)
 * This allows us to apply specific styles for no-JS solutions.
 *
 * We load this as a standalone script so we can prioritize it to the
 * head of the document and avoid a flash of unstyled content. See:
 * https://stackoverflow.com/a/12410668/1154642
 */
document.documentElement.classList.add('js');
document.documentElement.classList.remove('no-js');
