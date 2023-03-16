import equalHeightGrid from './equal-height-grid';
import resetHeightGrid from './reset-height-grid';

const applyStretchClass = () => {
  const hasStretchClass = document.querySelector('.hb-stretch-vertical-linked-cards');
  const verticalLinkedCardTitles = [...document.querySelectorAll('.hb-vertical-linked-card__title')];
  const verticalLinkedCards = [...document.querySelectorAll('.hb-vertical-linked-card')];

  // Matches the $su-breakpoint-sm variable. Screen sizes smaller than this variable
  // stack all grid columns making it unnecessary to set a height on cards.
  // See: https://github.com/SU-SWS/decanter/blob/master/core/src/scss/utilities/variables/core/_breakpoints.scss
  const smallScreenBreakpoint = 576;

  // Reset any min-heights that were previously set.
  // We need to do this so cards will not have a height set when resizing to small
  // screen sizes.
  if (hasStretchClass && verticalLinkedCards.length > 0) {
    resetHeightGrid(verticalLinkedCards);
  }

  // Reset any min-heights that were previously set.
  // Because not all Vertical Linked Cards will have a title, this needs a separate
  // if statement.
  if (hasStretchClass && verticalLinkedCardTitles.length > 0) {
    resetHeightGrid(verticalLinkedCardTitles);
  }

  // Only set heights for certain screen sizes
  if (hasStretchClass && window.innerWidth >= smallScreenBreakpoint) {
    if (verticalLinkedCardTitles.length > 0) {
      // Make the vertical linked card titles AND cards the same max height.
      // The title height has to be set first because it influences the final
      // height of the card.
      equalHeightGrid(verticalLinkedCardTitles)
        .then()
        .catch((result) => console.error('issue loading equal height cards', result));
    }
  }
};

// Wait a 1 sec for page to load in before setting heights
setTimeout(() => {
  applyStretchClass();
}, 1000);

// Recalculate when the window is resized
window.addEventListener('resize', () => {
  // Wait a half of a second before setting the heights
  setTimeout(() => {
    applyStretchClass();
  }, 500);
});
