(function (Drupal, once) {
  Drupal.behaviors.accordionToggleAllBehavior = {
    attach(context) {
      function addToCalAria() {
        const addToCal = once('add-to-cal-event', '.addtocal', context);
        const body = document.querySelector('body');

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
        body.addEventListener('click', (e) => {
          addToCal.forEach((button) => {
            if (e.target !== button) {
              button.setAttribute('aria-expanded', 'false');
            }
          });
        });
      }

      addToCalAria();
    },
  };
// eslint-disable-next-line no-undef
}(Drupal, once));
