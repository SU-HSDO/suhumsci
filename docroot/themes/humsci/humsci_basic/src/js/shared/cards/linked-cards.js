import addCardEvents from './event-handlers';

(function (Drupal, once) {
  Drupal.behaviors.linkedCardsBehavior = {
    attach(context) {
      // find all hb-vertical-card elements
      const cards = once(
        'linked-cards-events',
        '.hb-vertical-card, .hb-card--date-stacked, .hb-vertical-linked-card',
        context,
      );

      // Loop through each card
      cards.forEach((card) => {
        // Find the main link within each card
        let mainLink = '';

        // Logic for vertical card and date stacked card.
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
    },
  };
}(Drupal, once));
