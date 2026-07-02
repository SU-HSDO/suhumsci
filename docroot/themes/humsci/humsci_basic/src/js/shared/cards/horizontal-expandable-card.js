(function (Drupal, once) {
  Drupal.behaviors.horizontalExpandableCard = {
    attach(context) {
      const cards = once(
        'horizontal-expandable-card',
        '.hb-horizontal-expandable-card',
        context,
      );

      cards.forEach((card) => {
        const toggleButton = card.querySelector(
          '.hb-horizontal-expandable-card__toggle-button',
        );

        if (!toggleButton) return;

        const toggleText = toggleButton.querySelector('.visually-hidden');

        // Set initial state.
        toggleButton.setAttribute('aria-expanded', 'false');

        // Add event handler.
        toggleButton.addEventListener('click', () => {
          // Toggle the aria-expanded attribute.
          if (toggleButton.getAttribute('aria-expanded') === 'true') {
            toggleButton.setAttribute('aria-expanded', 'false');
            toggleText.textContent = 'Expand';
            card.classList.remove('is-open');
          } else {
            toggleButton.setAttribute('aria-expanded', 'true');
            toggleText.textContent = 'Collapse';
            card.classList.add('is-open');
          }
        });
      });
    },
  };
}(Drupal, once));
