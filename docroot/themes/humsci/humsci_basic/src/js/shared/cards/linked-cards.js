import addCardEvents from './event-handlers';
import addImageLinkEvents from './image-link-handler';

(function (Drupal, once, drupalSettings) {
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
        /**
         * Target the card image link (if present) to enhance its behavior.
         * This enables proper interaction with Drupal contextual controls
         * and caption toggles without triggering unintended navigation.
         */
        const cardImageLink = card.querySelector(
          '.hb-card__img a, .hb-vertical-linked-card__img a',
        );

        if (!cardImageLink) return;

        addImageLinkEvents(cardImageLink, drupalSettings);

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
// eslint-disable-next-line no-undef
}(Drupal, once, drupalSettings));
