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

        // Add event handler.
        toggleButton.addEventListener('click', () => {
          // Toggle the aria-expanded attribute.
          if (toggleButton.getAttribute('aria-expanded') === 'true') {
            toggleButton.setAttribute('aria-expanded', 'false');
            toggleText.textContent = Drupal.t('Expand');
            card.classList.remove('is-open');
          } else {
            toggleButton.setAttribute('aria-expanded', 'true');
            toggleText.textContent = Drupal.t('Collapse');
            card.classList.add('is-open');
          }
        });
      });
    },
  };
}(Drupal, once));
