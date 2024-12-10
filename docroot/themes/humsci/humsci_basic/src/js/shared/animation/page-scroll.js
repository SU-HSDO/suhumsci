(function (Drupal, once) {
  Drupal.behaviors.pageScrollBehavior = {
    attach(context) {
      // Selectors for elements
      const animationEnhancements = once('animation-enhancements', '.hb-has-animation-enhancements', context);
      const experimentalFeaturesClass = once('experimental-features', '.hb-experimental', context);
      const experimentalClassesToAnimate = once('experimental-classes', '.hs-font-lead', context);

      // The classes of items we want to add animations to
      const classesToAnimate = [
        once('gradient-hero', '.hb-gradient-hero', context),
        once('gradient-hero-text', '.hb-gradient-hero__text', context),
        once('gradient-hero-image-wrapper', '.hb-gradient-hero__image-wrapper', context),
        once('gradient-hero-image', '.field-hs-gradient-hero-image', context),
        once('hero-overlay', '.hb-hero-overlay', context),
        once('hero-overlay-text', '.hb-hero-overlay__text', context),
        once('hero-overlay-image-wrapper', '.hb-hero-overlay__image-wrapper', context),
        once('hero-image', '.field-hs-hero-image', context),
        once('font-splash', '.hs-font-splash', context),
      ];

      if (experimentalFeaturesClass) {
        classesToAnimate.push(experimentalClassesToAnimate);
      }

      // check if top of element is in viewport
      const isElementVisible = new IntersectionObserver((items) => {
        items.forEach((item) => {
          if (item.intersectionRatio > 0) {
            item.target.classList.add('animate');
          }
        });
      });

      if (animationEnhancements) {
        classesToAnimate.forEach((items) => {
          if (items) {
            items.forEach((item) => {
              isElementVisible.observe(item);
            });
          }
        });
      }
    },
  };
// eslint-disable-next-line no-undef
}(Drupal, once));
