import equalHeightGrid from './equal-height-grid';

const hasStretchClass = document.querySelector('.hb-stretch-vertical-linked-cards');
const verticalLinkedCardTitles = document.querySelectorAll('.hb-vertical-linked-card__title');
const verticalLinkedCards = document.querySelectorAll('.hb-vertical-linked-card');

// Wait a 1 sec for page to load in before setting heights
setTimeout(() => {
  if (hasStretchClass) {
    if (verticalLinkedCardTitles.length > 0) {
      // Make the vertical linked card titles AND cards the same max height.
      // The title height has to be set first because it influences the final
      // height of the card.
      equalHeightGrid(verticalLinkedCardTitles)
      .then(() => equalHeightGrid(verticalLinkedCards))
      .catch((result) => console.error('issue loading equal height cards', result));
    } else if (verticalLinkedCards.length > 0) {
      // Since card titles are not required we still want to run the equal height
      // function on remaining cards
      equalHeightGrid(verticalLinkedCards.length > 0);
    }
  }
}, 1000);
