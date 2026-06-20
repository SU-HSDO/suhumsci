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

      cards.forEach((card) => {
        // Find the main link within each card
        const { linkUrl } = card.dataset;

        const imageWrapper = card.querySelector(
          '.hb-card__img, .hb-vertical-linked-card__img',
        );
        const hasTitleLink = card.querySelector(
          '.hb-vertical-linked-card__title__link, .hb-card__title a',
        );

        /**
         * Wrap the image only when no title link exists
         * and it isn't already wrapped in a link.
         */
        if (
          linkUrl
          && imageWrapper
          && !imageWrapper.querySelector('a')
          && !hasTitleLink
        ) {
          const imageLink = document.createElement('a');
          imageLink.href = linkUrl;
          imageLink.className = 'hb-card__img__link';

          while (imageWrapper.firstChild) {
            imageLink.appendChild(imageWrapper.firstChild);
          }

          imageWrapper.appendChild(imageLink);
        }

        /**
         * Enhance the image link (pre-existing or just created).
         * This enables proper interaction with Drupal contextual controls
         * and caption toggles without triggering unintended navigation.
         */
        const cardImageLink = card.querySelector(
          '.hb-card__img a, .hb-vertical-linked-card__img a',
        );

        if (cardImageLink) {
          const image = cardImageLink.querySelector('img');

          // A linked image with no alt has no accessible name — fall back to the
          // link text passed from the template.
          if (image && !image.getAttribute('alt') && card.dataset.linkText) {
            image.alt = card.dataset.linkText;
          }

          addImageLinkEvents(cardImageLink, drupalSettings);
        }

        if (!linkUrl) {
          return;
        }

        addCardEvents(card, linkUrl);
      });
    },
  };
// eslint-disable-next-line no-undef
}(Drupal, once, drupalSettings));
