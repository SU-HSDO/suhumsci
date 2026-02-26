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
        if (block.offsetHeight <= maxHeight) {
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
          const isCollapsed = block.classList.toggle('is-collapsed');

          button.textContent = isCollapsed ? 'Show More' : 'Show Less';
          button.setAttribute('aria-expanded', !isCollapsed);
        });

        button.setAttribute('aria-expanded', 'false');
      }
    },
  };
})(Drupal);