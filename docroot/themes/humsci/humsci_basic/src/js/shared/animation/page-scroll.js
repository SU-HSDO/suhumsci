(function (Drupal) {
  Drupal.behaviors.pageScrollBehavior = {
    attach(context) {
      const animationEnhancements = context.querySelector('.hb-has-animation-enhancements');
      const experimentalFeaturesClass = context.querySelector('.hb-experimental');
      const experimentalClassesToAnimate = [context.querySelectorAll('.hs-font-lead')];

      // The classes of items we want to add animations to
      const classesToAnimate = [
        context.querySelectorAll('.hb-gradient-hero'),
        context.querySelectorAll('.hb-gradient-hero__text'),
        context.querySelectorAll('.hb-gradient-hero__image-wrapper'),
        context.querySelectorAll('.field-hs-gradient-hero-image'),
        context.querySelectorAll('.hb-hero-overlay'),
        context.querySelectorAll('.hb-hero-overlay__text'),
        context.querySelectorAll('.hb-hero-overlay__image-wrapper'),
        context.querySelectorAll('.field-hs-hero-image'),
        context.querySelectorAll('.hs-font-splash'),
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
}(Drupal));
