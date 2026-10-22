/**
 * Groups Swiper controls (prev arrow, pagination, next arrow) into a single
 * wrapper on mobile so they can be laid out together, and restores the
 * original DOM position on larger viewports.
 */
(function (Drupal, once) {
  Drupal.behaviors.swiperControlsPosition = {
    attach(context) {
      const mobileQuery = window.matchMedia('(max-width: 767px)');

      once('swiper-controls-position', '.swiper-container', context).forEach((slider) => {
        const prev = slider.querySelector('.swiper-button-prev');
        const next = slider.querySelector('.swiper-button-next');
        // The pager is optional: `slideshow_no_dots` ships without one.
        const pager = slider.querySelector('.swiper-pagination');

        if (!prev || !next) {
          return;
        }

        // Anchor each control with a comment node so ungroup() can put it back
        // exactly where it started, in the original order, instead of
        // appending everything to the end of the slider.
        const controls = [prev, pager, next].filter(Boolean);
        const anchors = controls.map((element) => {
          const anchor = document.createComment('swiper-controls-position');
          element.before(anchor);
          return { element, anchor };
        });

        let wrapper = null;

        const group = () => {
          if (wrapper) {
            return;
          }
          wrapper = document.createElement('div');
          wrapper.className = 'swiper-controls-wrapper';
          // append() moves the live nodes, so Swiper keeps its references.
          wrapper.append(...controls);
          slider.append(wrapper);
        };

        const ungroup = () => {
          if (!wrapper) {
            return;
          }
          // Each control goes back after its own anchor, so relative order is
          // preserved no matter what else lives in the slider.
          anchors.forEach(({ element, anchor }) => anchor.after(element));
          wrapper.remove();
          wrapper = null;
        };

        const sync = () => (mobileQuery.matches ? group() : ungroup());

        // AbortController lets destroy() remove the listener in one call.
        const controller = new AbortController();

        slider.swiperControls = {
          destroy() {
            controller.abort();
            ungroup();
            anchors.forEach(({ anchor }) => anchor.remove());
          },
        };

        sync();
        mobileQuery.addEventListener('change', sync, {
          signal: controller.signal,
        });
      });
    },

    detach(context, settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }
      once.remove('swiper-controls-position', '.swiper-container', context).forEach((slider) => {
        slider.swiperControls?.destroy();
        delete slider.swiperControls;
      });
    },
  };
}(Drupal, once));
