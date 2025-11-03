(function (Drupal, once) {
  Drupal.behaviors.mediaCaptionToggle = {
    attach(context) {
      const captions = once('media-caption-toggle', '.field-media-image-caption', context);

      captions.forEach((caption) => {
        const toggleButton = caption.querySelector('.toggle-caption__toggle');
        const content = caption.querySelector('.toggle-caption__content');
        const spotlight = caption.closest('.hb-spotlight--expanded');
        const mobileView = window.matchMedia('(max-width: 991px)');

        if (!content || !toggleButton) return;

        const updateCaptionState = () => {
          // Temporarily remove classes to get the “natural” rendered height
          caption.classList.remove('collapsible-caption');
          content.classList.remove('visually-hidden');
          toggleButton.classList.remove('is-open');

          // Determine if this caption should be collapsible:
          // - It's long enough.
          // - Or it's inside a spotlight on a mobile viewport.
          // Determine if this caption should be collapsible
          const collapsible = content.offsetHeight >= 18 || (spotlight && mobileView.matches);

          // Add collapsible classes only if needed
          if (collapsible) {
            caption.classList.add('collapsible-caption');
            if (!toggleButton.classList.contains('is-open')) {
              content.classList.add('visually-hidden');
            }
          }
        };

        // Initial setup.
        updateCaptionState();

        // Toggle open/close.
        toggleButton.addEventListener('click', () => {
          const isOpen = toggleButton.classList.toggle('is-open');
          content.classList.toggle('visually-hidden', !isOpen);
        });

        // Update on window resize.
        window.addEventListener('resize', updateCaptionState);
      });
    },
  };
}(Drupal, once));
