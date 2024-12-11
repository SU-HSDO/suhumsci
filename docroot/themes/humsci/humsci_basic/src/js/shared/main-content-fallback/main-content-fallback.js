(function (Drupal, once) {
  Drupal.behaviors.addMainContentFallback = {
    attach(context) {
      // Return if main content target is found, nothing to do.
      if (once('main-content-target', '#main-content', context)[0]) {
        return;
      }

      const mainElement = once('main-element', 'main', context)[0];
      if (mainElement) {
        mainElement.insertAdjacentHTML(
          'afterbegin',
          '<div id="main-content" class="visually-hidden" tabindex="-1">Main content start</div>',
        );
      }
    },
  };
// eslint-disable-next-line no-undef
}(Drupal, once));
