import equalHeightGrid from './equal-height-grid';

// Wait a 1 sec for page to load in before setting heights
// Make the vertical linked card titles AND cards the same max height
setTimeout(() => {
  equalHeightGrid('hb-vertical-linked-card__title', 'hb-stretch-vertical-linked-cards')
  .then((result) => equalHeightGrid('hb-vertical-linked-card', 'hb-stretch-vertical-linked-cards'))
  .catch((result) => console.error('issue loading equal height cards', result));
}, 1000);
