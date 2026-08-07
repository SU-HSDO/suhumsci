/**
 * Groups Swiper controls (prev arrow, pagination, next arrow) into a single
 * wrapper on mobile so they can be laid out together, and restores the
 * original DOM order on larger viewports.
 */
(function (Drupal, once) {
  Drupal.behaviors.swiperControlsPosition = {
    attach(context) {
      const mobileQuery = window.matchMedia('(max-width: 767px)');

      once('swiper-controls-position', '.swiper-container', context).forEach(
        (slider) => {
          const prev = slider.querySelector('.swiper-button-prev');
          const next = slider.querySelector('.swiper-button-next');
          const pager = slider.querySelector('.swiper-pagination');

          if (!prev || !next || !pager) {
            return;
          }

          let wrapper = null;

          const group = () => {
            if (wrapper) {
              return;
            }
            wrapper = document.createElement('div');
            wrapper.className = 'swiper-controls-wrapper';
            // append() moves the live nodes, so Swiper keeps its references.
            wrapper.append(prev, pager, next);
            slider.append(wrapper);
          };

          const ungroup = () => {
            if (!wrapper) {
              return;
            }
            slider.append(prev, pager, next);
            wrapper.remove();
            wrapper = null;
          };

          const sync = () => (mobileQuery.matches ? group() : ungroup());

          // AbortController lets detach() remove the listener in one call.
          const controller = new AbortController();
          slider.swiperControls = { controller };

          sync();
          mobileQuery.addEventListener('change', sync, {
            signal: controller.signal,
          });
        },
      );
    },

    detach(context, settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }
      context.querySelectorAll('.swiper-container').forEach((slider) => {
        slider.swiperControls?.controller.abort();
        delete slider.swiperControls;
      });
    },
  };
}(Drupal, once));
