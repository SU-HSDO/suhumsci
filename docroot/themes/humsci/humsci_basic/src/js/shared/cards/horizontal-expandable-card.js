(function (Drupal, once) {
  Drupal.behaviors.horizontalExpandableCard = {
    attach(context) {
      const cards = once(
        'horizontal-expandable-card',
        '.hb-horizontal-expandable-card',
        context,
      );

      cards.forEach((card) => {
        const summary = card.querySelector('summary');
        const toggleButton = card.querySelector(
          '.hb-horizontal-expandable-card__toggle-button',
        );

        if (!summary || !toggleButton) {
          return;
        }

        const updateState = () => {
          const isOpen = card.hasAttribute('open');
          toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        // Set initial state
        updateState();

        // Listen for native toggle events on details element
        card.addEventListener('toggle', updateState);

        summary.addEventListener('click', (e) => {
          if (e.target.closest('a')) {
            return;
          }

          e.preventDefault();
        });

        // Handle the toggle button click manually
        toggleButton.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          if (card.hasAttribute('open')) {
            card.removeAttribute('open');
          } else {
            card.setAttribute('open', '');
          }
        });
      });
    },
  };
}(Drupal, once));
