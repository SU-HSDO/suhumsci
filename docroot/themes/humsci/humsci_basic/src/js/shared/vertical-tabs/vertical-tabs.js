(function (Drupal) {
  Drupal.behaviors.addTableScopeAttributes = {
    // eslint-disable-next-line no-unused-vars
    attach(context) {
      function closeDetails() {
      // Close Revision Information Details element in Layout Builder by default.
        if (document.querySelector('.layout-builder-form')) {
          const details = document.querySelector('.layout-builder-form details');
          if (details) {
            details.removeAttribute('open');
          }
        }
      }

      document.addEventListener('DOMContentLoaded', () => {
        closeDetails(document);
      });
    },
  };
}(Drupal));
