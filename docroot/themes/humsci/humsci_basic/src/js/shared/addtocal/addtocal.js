(function (Drupal, once) {
  Drupal.behaviors.accordionToggleAllBehavior = {
    attach(context) {
      const addToCal = once('addtocal', '.addtocal', context);

      // Do nothing if no add to cal button found.
      if (addToCal.length === 0) {
        return;
      }

      // For each .addtocal button, when clicked, change aria-expanded to true.
      addToCal.forEach((button) => {
        button.addEventListener('click', () => {
          if (button.getAttribute('aria-expanded') === 'true') {
            button.setAttribute('aria-expanded', 'false');
          } else {
            button.setAttribute('aria-expanded', 'true');
          }
        });
      });

      // When the body except button is clicked, change aria-expanded to false.
      const body = document.querySelector('body');
      body.addEventListener('click', (e) => {
        addToCal.forEach((button) => {
          if (e.target !== button) {
            button.setAttribute('aria-expanded', 'false');
          }
        });
      });
    },
  };
}(Drupal, once));
