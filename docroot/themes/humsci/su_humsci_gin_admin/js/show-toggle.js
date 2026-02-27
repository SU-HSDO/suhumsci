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
            const buttonHeight = button.offsetHeight;

            block.style.maxHeight = fullHeight + buttonHeight + 'px';
            block.style.height = fullHeight + buttonHeight + 'px';
            block.classList.remove('is-collapsed');

            button.textContent = 'Show Less';
            button.setAttribute('aria-expanded', 'true');

          } else {
            // COLLAPSE
            block.style.height = '100%';

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