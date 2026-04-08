import addCardEvents from './event-handlers';
import addContextualImageLinkEvents from './image-link-handler';

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
         * Handles click behavior for image links inside cards that may contain:
         * - Drupal contextual controls (for authenticated users)
         * - Caption toggle UI (for all users)
         *
         * When interactive elements exist inside the <a>, we need to prevent the link
         * from navigating when those elements are clicked, while still allowing their
         * own behavior (e.g. opening contextual menu or toggling caption).
         */
        const isAuthenticated = drupalSettings?.user?.uid > 0;

        const cardImageLink = card.querySelector(
          '.hb-card__img a, .hb-vertical-linked-card__img a',
        );

        if (cardImageLink) {
          const contextualRegion = cardImageLink.querySelector('article.contextual-region');
          const caption = cardImageLink.querySelector('.field-media-image-caption');

          /**
           * Run if:
           * - Authenticated user with contextual UI
           * - OR any user with caption toggle
           */
          if ((isAuthenticated && contextualRegion) || caption) {
            addContextualImageLinkEvents(cardImageLink);
          }
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
// eslint-disable-next-line no-undef
}(Drupal, once, drupalSettings));
