import addCardEvents from './event-handlers';
import addContextualImageLinkEvents from './image-link-handler';

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
        /**
         * Handles click events on a card image link that contains a Drupal contextual
         * region. Prevents the image anchor from navigating when interacting with
         * contextual controls, while still allowing contextual links to work normally.
         *
         */
        const cardImageLink = card.querySelector('.hb-card__img a');
        if (cardImageLink) {
          const contextualRegion = cardImageLink.querySelector('article.contextual-region');
          if (!contextualRegion) return;

          addContextualImageLinkEvents(cardImageLink);
        }

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
