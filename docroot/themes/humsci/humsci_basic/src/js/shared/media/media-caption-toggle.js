(function (Drupal, once) {
  Drupal.behaviors.mediaCaptionToggle = {
    attach(context) {
      const captions = once('media-caption-toggle', '.field-media-image-caption', context);
      const html = document.documentElement;
      const isColorful = Array.from(html.classList).some((cls) => cls.startsWith('hc-pairing-'));

      captions.forEach((caption) => {
        const toggleButton = caption.querySelector('.toggle-caption__toggle');
        const content = caption.querySelector('.toggle-caption__content');
        const spotlight = caption.closest('.hb-spotlight--expanded');
        const mobileView = window.matchMedia('(max-width: 991px)');

        if (!content || !toggleButton) return;

        // Debounce helper
        const debounce = (func, delay = 150) => {
          let timeout;
          return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), delay);
          };
        };

        // Update state for a single caption
        const updateCaptionState = () => {
          // Temporarily remove classes to get the “natural” rendered height
          caption.classList.remove('collapsible-caption');
          content.classList.remove('visually-hidden');
          toggleButton.classList.remove('is-open');

          // Double requestAnimationFrame ensures layout is fully updated
          requestAnimationFrame(() => {
            requestAnimationFrame(() => {
              const height = content.offsetHeight;

              // Determine if this caption should be collapsible:
              // 1. It's long enough.
              // 2. Or it's inside a spotlight on a mobile viewport.
              const collapsible = height >= 28
                || (spotlight && mobileView.matches && isColorful);

              if (collapsible) {
                caption.classList.add('collapsible-caption');
                if (!toggleButton.classList.contains('is-open')) {
                  content.classList.add('visually-hidden');
                }
              }
            });
          });
        };

        // Initial setup.
        updateCaptionState();

        // Toggle open/close.
        toggleButton.addEventListener('click', () => {
          const isOpen = toggleButton.classList.toggle('is-open');
          content.classList.toggle('visually-hidden', !isOpen);
        });

        // Debounced resize listener
        // Default delay (150ms) is reasonable for UX.
        const debouncedResize = debounce(updateCaptionState, 150);
        window.addEventListener('resize', debouncedResize);
      });
    },
  };
}(Drupal, once));
