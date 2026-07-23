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

        if (!linkUrl) {
          return;
        }

        const imageWrapper = card.querySelector(
          '.hb-card__img, .hb-vertical-linked-card__img',
        );

        const titleLink = card.querySelector(
          '.hb-vertical-linked-card__title__link, .hb-card__title a',
        );

        /**
         * Wrap the image only when no title link exists
         * and it isn't already wrapped in a link.
         */
        if (imageWrapper
          && !imageWrapper.querySelector('a')
          && !titleLink
        ) {
          const imageLink = document.createElement('a');
          imageLink.href = linkUrl;

          if (card.dataset.linkText) {
            imageLink.setAttribute('aria-label', card.dataset.linkText);
          }

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
        const imageLink = imageWrapper?.querySelector('a');
        if (imageLink) {
          addImageLinkEvents(imageLink, drupalSettings);
        }

        const linkElement = imageLink || titleLink;
        if (linkElement) {
          addCardEvents(card, linkUrl, linkElement);
        }
      });
    },
  };
// eslint-disable-next-line no-undef
}(Drupal, once, drupalSettings));
