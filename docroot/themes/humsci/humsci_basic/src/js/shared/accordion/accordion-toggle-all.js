/**
 * Loops through a list of accordions and either opens or closes all items
 *
 * @param {array} expects a list of accordion elements
 * @param {string} expects a string that specifies if all accordions should be opened or closed
 */
function toggleAllAccordions(accordionList, command) {
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
  toggleButton.classList.add('hb-link');
  toggleButton.classList.add('hb-accordion-toggle-all');

  return toggleButton;
}

/**
 * Updates the all toggle buttons when one has been clicked depending on whether
 * or not all accordions are being opened or closed.
 * @param {array} expects the list of all accordion toggle buttons on the page
 * @param {string} expects a string that specifies if all accordions should be opened or closed
 */
function updateToggle(toggleList, command) {
  toggleList.forEach((toggleButton) => {
    if (command === 'closeAll') {
      toggleButton.innerText = 'Expand All';
    } else {
      toggleButton.innerText = 'Collapse All';
    }
  });
}

// Create a list of all accordions on the page
const accordionList = [...document.querySelectorAll('details')];

if (accordionList.length >= 1) {
  let allExpanded = false;

  // Loop through each accordion item
  // If the toggle all class is present create a toggle button and place it above
  // the accordion instance.
  accordionList.forEach((accordion) => {
    if (accordion.classList.contains('hb-accordion_toggle-all')) {
      const toggleButton = createToggle();
      accordion.parentNode.insertBefore(toggleButton, accordion);
    }
  });

  // Create a list of all toggle buttons generated on the page. This has to run
  // after the block of code that loops through the accordion lists and creates
  // the buttons.
  const allToggleButtons = [...document.querySelectorAll('.hb-accordion-toggle-all')];

  allToggleButtons.forEach((toggleButton) => {
    toggleButton.addEventListener('click', (e) => {
      e.preventDefault();
      if (allExpanded) {
        toggleAllAccordions(accordionList, 'closeAll');
        updateToggle(allToggleButtons, 'closeAll');
        allExpanded = false;
      } else {
        toggleAllAccordions(accordionList, 'openAll');
        updateToggle(allToggleButtons, 'openAll');
        allExpanded = true;
      }
      toggleButton.scrollIntoView(true);
    });
  });
}

const searchQuery = new URLSearchParams(window.location.search);
const params = Object.fromEntries(searchQuery.entries());

function toggleAccordionFromSearch() {
  const searchTerm = params.search.toLowerCase();

  accordionList.forEach((accordion) => {
    if (accordion.textContent.toLowerCase().includes(searchTerm)) {
      accordion.setAttribute('open', '');
    }
  });
}

if (Object.keys(params).length && Object.prototype.hasOwnProperty.call(params, 'search')) {
  toggleAccordionFromSearch();
}
