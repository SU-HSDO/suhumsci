/**
 * Position arrows/controls for Swiper slides on mobile.
 *
 */
(function (Drupal, once) {
  Drupal.behaviors.swiperControlsPosition = {
    attach(context) {
      const MOBILE_QUERY = '(max-width: 767px)';
      const mq = window.matchMedia(MOBILE_QUERY);

      once(
        'swiper-controls-position',
        '.swiper-container.hs-slideshow',
        context,
      ).forEach((slider) => {
        if (!slider) {
          return;
        }

        const prev = slider.querySelector('.swiper-button-prev');
        const next = slider.querySelector('.swiper-button-next');
        const pager = slider.querySelector('.swiper-pagination');

        if (!prev || !next || !pager) {
          return;
        }

        let bar = null;

        const group = () => {
          if (bar) {
            return;
          }
          bar = document.createElement('div');
          bar.className = 'swiper-controls-wrapper';
          // append() moves the live nodes, so Swiper keeps its references.
          bar.append(prev, pager, next);
          slider.appendChild(bar);
        };

        const ungroup = () => {
          if (!bar) {
            return;
          }
          slider.append(prev, pager, next);
          bar.remove();
          bar = null;
        };

        const sync = () => (mq.matches ? group() : ungroup());

        sync();
        mq.addEventListener('change', sync);
      });
    },
  };
}(Drupal, once));
