import addCardEvents from './event-handlers';

(() => {
  // find all hb-vertical-card elements
  const cards = document.querySelectorAll('.hb-vertical-card, .hb-card--date-stacked, .hb-vertical-linked-card');

  // Loop through each card
  cards.forEach((card) => {
    // Find the main link within each card
    let mainLink = '';

    // Logic for vertical card, date stacked card and structured card.
    if (card.querySelector('.hb-card__title a')) {
      mainLink = card.querySelector('.hb-card__title a');
    // Logic for vertical linked card.
    } else {
      mainLink = card.querySelector(
        '.hb-vertical-linked-card__title__link',
      );
    }

    if (!mainLink) {
      return;
    }

    addCardEvents(card, mainLink);
  });
})();
