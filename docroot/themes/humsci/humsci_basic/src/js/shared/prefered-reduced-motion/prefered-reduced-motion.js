(function ($, Drupal, window) {
  Drupal.behaviors.mySlickBehavior = {
    // eslint-disable-next-line no-unused-vars
    attach(context, settings) {
      // Check for reduced motion preference
      const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

      function handleReducedMotionPreference() {
        const slickInstance = context.querySelector('.slick__slider');

        // Check if the user prefers reduced motion
        if (!mediaQuery || mediaQuery.matches) {
          $(slickInstance).on('beforeChange', (event, slick) => {
            slick.slickSetOption('cssEase', 'none', true);
          });
        } else {
          $(slickInstance).on('beforeChange', (event, slick) => {
            slick.slickSetOption('cssEase', 'ease', true);
          });
        }
      }

      // Initial check on page load
      handleReducedMotionPreference();

      // Add a listener to react to changes in the user's preference
      mediaQuery.addEventListener('change', handleReducedMotionPreference);
    },
  };
// eslint-disable-next-line no-undef
}(jQuery, Drupal, window, document));
