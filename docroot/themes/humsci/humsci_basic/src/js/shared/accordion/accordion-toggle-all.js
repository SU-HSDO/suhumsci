/**
 * Loops through a list of accordions and either opens or closes all items
 *
 * @param {array} expects a list of accordion elements
 * @param {string} expects a string that specifies if all accordions should be opened or closed
 */
function togglaAllAccordions(accordionList, command) {
  if (command === 'closeAll') {
    accordionList.forEach((accordion) => {
      accordion.removeAttribute('open');
    });
  } else {
    accordionList.forEach((accordion) => {
      accordion.setAttribute('open', '');
    });
  }
}

/**
 * Creates a button element that can act as a toggle for all accordions on a page.
 *
 * @return {element}
 */
function createToggle() {
  const toggleButton = document.createElement('Button');
  toggleButton.innerText = 'Expand All';

  return toggleButton;
}

/**
 * Updates the toggle button element depending on whether or
 * not all accordions are being opened or closed.
 * @param {element} expects the toggle button which toggles all accordions
 * @param {string} expects a string that specifies if all accordions should be opened or closed
 */
function updateToggle(toggleButton, command) {
  if (command === 'closeAll') {
    toggleButton.innerText = 'Expand All';
  } else {
    toggleButton.innerText = 'Collapse All';
  }
}

// /**
//  * Loops through the list of all accordions on a page. .
//  * If any accordion contains a specific class this function will return true.
//  * @param {array} expects a list of accordion elements
//  * @param {string} expects a classname
//  * @return {boolean}
//  */
function willToggleAll(className) {
  const findToggleClass = document.querySelector(`.${className}`);

  // If the findToggleClass exists then return true, else return false
  return !!findToggleClass;
}

// Create a list of all accordions on the page
const accordionList = [...document.querySelectorAll('details')];

if (accordionList.length > 1) {
  const toggleButton = createToggle();
  const toggleAll = willToggleAll('hb-accordion_toggle-all');
  let allExpanded = false;

  // If toggleAll is set to true, add a toggle button before the first instance of an accordion
  if (toggleAll) {
    accordionList[0].parentNode.insertBefore(toggleButton, accordionList[0]);
  }

  toggleButton.addEventListener('click', () => {
    if (allExpanded) {
      togglaAllAccordions(accordionList, 'closeAll');
      updateToggle(toggleButton, 'closeAll');
      allExpanded = false;
    } else {
      togglaAllAccordions(accordionList, 'openAll');
      updateToggle(toggleButton, 'openAll');
      allExpanded = true;
    }
  });
}
