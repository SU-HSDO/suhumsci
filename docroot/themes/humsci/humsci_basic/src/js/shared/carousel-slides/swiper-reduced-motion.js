/**
 * Disables Swiper slide transition speed when reduced motion is preferred.
 *
 * When a user has enabled "reduce motion" in their OS/browser settings,
 * Swiper's slide transition is set to 0ms so slides change instantly
 * without any sweeping motion, improving accessibility without
 * sacrificing navigation functionality.
 */
(function (Drupal, once) {
  Drupal.behaviors.swiperReducedMotion = {
    attach(context) {
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

      if (prefersReducedMotion.matches) {
        once('swiper-reduced-motion', '.swiper-container', context).forEach((swiper) => {
          setTimeout(() => {
            if (swiper) {
              swiper.swiper.params.speed = 0;
              swiper.swiper.update();
            }
          }, 0);
        });
      }
    },
  };
}(Drupal, once));
