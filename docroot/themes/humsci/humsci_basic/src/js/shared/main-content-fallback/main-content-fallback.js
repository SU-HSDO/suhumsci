(function (Drupal, once) {
  Drupal.behaviors.addMainContentFallback = {
    attach(context) {
      const [mainElement] = once('main-content-fallback', 'main', context);

      // No main element or behavior already executed.
      if (!mainElement) {
        return;
      }

      // Return if main content target is found, nothing to do.
      if (document.querySelector('#main-content')) {
        return;
      }

      mainElement.insertAdjacentHTML(
        'afterbegin',
        '<div id="main-content" class="visually-hidden" tabindex="-1">Main content start</div>',
      );
    },
  };
}(Drupal, once));
