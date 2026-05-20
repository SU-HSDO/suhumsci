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

      const update = (element, speedSource) => {
        const { swiper } = element;
        if (!swiper) return;
        swiper.params.speed = speedSource.matches ? 0 : swiper.originalParams.speed;
        swiper.update();
      };

      once('swiper-reduced-motion', '.swiper-container', context).forEach((element) => {
        setTimeout(() => {
          update(element, prefersReducedMotion);
        }, 0);

        prefersReducedMotion.addEventListener('change', (event) => update(element, event));
      });
    },
  };
}(Drupal, once));
