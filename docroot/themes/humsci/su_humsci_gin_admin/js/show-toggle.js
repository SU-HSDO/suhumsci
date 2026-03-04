((Drupal, once) => {
  Drupal.behaviors.showToggle = {
    attach(context) {
      const blocks = once('show-toggle', '.dashboard--main-dashboard .block', context);

      if (!blocks.length) {
        return;
      }

      for (const block of blocks) {
        const maxHeight = 550;

        // Only apply if taller than 550px
        if (block.scrollHeight <= maxHeight) {
          block.classList.add('is-static');
          continue;
        }

        block.classList.add('is-collapsed');

        const button = document.createElement('button');
        button.className = 'show-toggle';
        button.type = 'button';
        button.textContent = 'Show More';
        button.setAttribute('aria-expanded', 'false');

        block.appendChild(button);

        button.addEventListener('click', () => {
          const isCollapsed = block.classList.toggle('is-collapsed');

          button.textContent = isCollapsed ? 'Show More' : 'Show Less';
          button.setAttribute('aria-expanded', String(!isCollapsed));
        });
      }
    },
  };
})(Drupal, once);