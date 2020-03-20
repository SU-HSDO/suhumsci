/**
 * Wrap every table in a class that will allow us to create more responsive styling
 */

/**
 * Wrap each element in a new parent
 * @param elements
 * @param wrapper
 */
function wrapElement(element) {
  // Create a new div with a special class name
  const wrapper = document.createElement('div');
  wrapper.className = 'hb-table-wrap';

  element.parentNode.insertBefore(wrapper, element);
  wrapper.appendChild(element);
}

// Select every table element
const elements = document.querySelectorAll('table');
const uiPatternTable = document.querySelectorAll('.hb-table-pattern');
console.log(elements);
console.log(uiPatternTable);

// Wrap every table element
for (let i = 0; i < elements.length; i++) {
  wrapElement(elements[i]);
}

// Wrap every table UI pattern
for (let i = 0; i < uiPatternTable.length; i++) {
  wrapElement(uiPatternTable[i]);
}
