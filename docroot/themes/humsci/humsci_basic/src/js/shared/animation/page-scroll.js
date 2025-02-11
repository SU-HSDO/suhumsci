(function (Drupal, once) {
  Drupal.behaviors.pageScrollBehavior = {
    attach(context) {
      // Selectors for elements
      const animationEnhancements = context.querySelector(
        '.hb-has-animation-enhancements',
      );
      const experimentalFeaturesClass = context.querySelector('.hb-experimental');
      const experimentalClassesToAnimate = ['.hs-font-lead'];

      // If the animation enhancements are not enabled, do nothing.
      if (!animationEnhancements) {
        return;
      }

      // The classes of items we want to add animations to
      const classesToAnimate = [
        '.hb-gradient-hero',
        '.hb-gradient-hero__text',
        '.hb-gradient-hero__image-wrapper',
        '.field-hs-gradient-hero-image',
        '.hb-hero-overlay',
        '.hb-hero-overlay__text',
        '.hb-hero-overlay__image-wrapper',
        '.field-hs-hero-image',
        '.hs-font-splash',
      ];

      if (experimentalFeaturesClass) {
        classesToAnimate.push(experimentalClassesToAnimate);
      }

      const elementsToAnimate = once(
        'page-scroll-animate',
        classesToAnimate.join(','),
        context,
      );

      // check if top of element is in viewport
      const isElementVisible = new IntersectionObserver((items) => {
        items.forEach((item) => {
          if (item.intersectionRatio > 0) {
            item.target.classList.add('animate');
          }
        });
      });

      elementsToAnimate.forEach((element) => {
        isElementVisible.observe(element);
      });
    },
  };
}(Drupal, once));
