import addCardEvents from './event-handlers';

(function (Drupal, once) {
  Drupal.behaviors.structuredCardBehavior = {
    attach(context) {
      // find all structured-card elements
      const cards = once('structured-card-event', '.hb-card--structured', context);

      // Loop through each card
      cards.forEach((card) => {
        // Get the link inside .hb-card__title
        const titleLink = card.querySelector('.hb-card__title a');

        if (!titleLink) {
          return;
        }

        addCardEvents(card, titleLink);

        // Get title link text
        const titleText = titleLink.textContent.trim();

        // Select all links inside the card, except:
        // - The title link inside `.hb-card__title`
        // - Links with `mailto:` href
        const links = card.querySelectorAll(
          "a:not(.hb-card__title a):not([href^='mailto:'])",
        );

        links.forEach((link) => {
          // Add a visually hidden span to each link
          const hiddenSpan = document.createElement('span');
          hiddenSpan.classList.add('visually-hidden');
          hiddenSpan.textContent = `${titleText}: `;

          link.insertBefore(hiddenSpan, link.firstChild);
        });
      });
    },
  };
}(Drupal, once));
