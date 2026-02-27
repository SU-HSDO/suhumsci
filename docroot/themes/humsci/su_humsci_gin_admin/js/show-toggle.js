((Drupal) => {
  Drupal.behaviors.showToggle = {
    attach(context) {
      const blocks = context.querySelectorAll(
        '.dashboard--main-dashboard .block'
      );

      if (!blocks.length) {
        return;
      }

      for (const block of blocks) {
        const maxHeight = 550;

        // Only apply if taller than 550px
        if (block.scrollHeight <= maxHeight) {
          continue;
        }

        block.setAttribute('data-show-more-processed', '');
        block.classList.add('is-collapsed');

        // Create toggle button
        const button = document.createElement('button');
        button.className = 'button button--secondary show-toggle';
        button.type = 'button';
        button.textContent = 'Show More';

        block.appendChild(button);

        button.addEventListener('click', () => {
          const isCollapsed = block.classList.contains('is-collapsed');

          if (isCollapsed) {
            // EXPAND
            const fullHeight = block.scrollHeight;

            block.style.maxHeight = fullHeight + 'px';
            block.classList.remove('is-collapsed');

            button.textContent = 'Show Less';
            button.setAttribute('aria-expanded', 'true');

          } else {
            // COLLAPSE
            const currentHeight = block.scrollHeight;

            block.style.maxHeight = currentHeight + 'px';

            requestAnimationFrame(() => {
              block.style.maxHeight = '550px';
            });

            block.classList.add('is-collapsed');

            button.textContent = 'Show More';
            button.setAttribute('aria-expanded', 'false');
          }
        });
      }
    },
  };
})(Drupal);