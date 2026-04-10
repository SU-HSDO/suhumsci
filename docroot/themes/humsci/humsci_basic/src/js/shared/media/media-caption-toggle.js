(function (Drupal, once) {
  let captionCount = 0;

  Drupal.behaviors.mediaCaptionToggle = {
    attach(context) {
      const captions = once('media-caption-toggle', '.field-media-image-caption', context);
      const html = document.documentElement;
      const isColorful = Array.from(html.classList).some((cls) => cls.startsWith('hc-pairing-'));

      captions.forEach((caption) => {
        const toggleButton = caption.querySelector('.toggle-caption__toggle');
        const content = caption.querySelector('.toggle-caption__content');
        const spotlight = caption.closest('.hb-spotlight--expanded');
        const mobileView = window.matchMedia('(max-width: 1293px)');
        const showLabel = toggleButton?.dataset.showLabel || 'Show image caption';
        const hideLabel = toggleButton?.dataset.hideLabel || 'Hide image caption';

        if (!content || !toggleButton) return;

        captionCount += 1;
        const captionId = caption.id || `media-caption-${captionCount}`;
        caption.id = captionId;
        toggleButton.setAttribute('aria-controls', captionId);

        // Debounce helper
        const debounce = (func, delay = 150) => {
          let timeout;
          return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), delay);
          };
        };

        const syncButtonState = (isOpen) => {
          toggleButton.classList.toggle('is-open', isOpen);
          toggleButton.setAttribute('aria-expanded', `${isOpen}`);
          toggleButton.setAttribute('aria-label', isOpen ? hideLabel : showLabel);
        };

        // Update state for a single caption
        const updateCaptionState = () => {
          const isOpen = toggleButton.classList.contains('is-open');

          // Temporarily remove classes to get the “natural” rendered height
          caption.classList.remove('collapsible-caption');
          caption.classList.remove('is-open');
          content.classList.remove('visually-hidden');
          toggleButton.classList.remove('is-open');

          // Double requestAnimationFrame ensures layout is fully updated
          requestAnimationFrame(() => {
            const height = content.offsetHeight;

            // Determine if this caption should be collapsible:
            // 1. It's long enough.
            // 2. Or it's inside a spotlight on a mobile viewport.
            const collapsible = height >= (isColorful ? 28 : 27)
              || (spotlight && mobileView.matches && isColorful);

            if (collapsible) {
              caption.classList.add('collapsible-caption');

              // Keep the button focusable and accessible.
              toggleButton.removeAttribute('tabindex');

              syncButtonState(isOpen);

              if (!isOpen) {
                content.classList.add('visually-hidden');
              } else {
                caption.classList.add('is-open');
              }
            } else {
              // Remove from keyboard navigation when not collapsible.
              toggleButton.setAttribute('tabindex', '-1');
              syncButtonState(false);
            }
          });
        };

        // Initial setup.
        updateCaptionState();

        // Toggle open/close.
        toggleButton.addEventListener('click', () => {
          const isOpen = !toggleButton.classList.contains('is-open');
          syncButtonState(isOpen);
          caption.classList.toggle('is-open', isOpen);
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
