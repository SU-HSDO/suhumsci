(function (Drupal) {
  Drupal.behaviors.addMainContentFallback = {
    attach(context) {
      // Return if main content target is found, nothing to do.
      if (context.querySelector('#main-content')) {
        return;
      }

      const mainElement = context.querySelector('main');
      if (mainElement) {
        mainElement.insertAdjacentHTML(
          'afterbegin',
          '<div id="main-content" class="visually-hidden" tabindex="-1">Main content start</div>',
        );
      }
    },
  };
}(Drupal));
