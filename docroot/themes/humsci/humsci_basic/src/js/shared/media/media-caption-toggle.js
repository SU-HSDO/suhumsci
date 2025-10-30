(function (Drupal, once) {
  Drupal.behaviors.mediaCaptionToggle = {
    attach(context) {
      const mobileView = window.matchMedia('(max-width: 991px)');
      const captions = once('media-caption-toggle', '.field-media-image-caption', context);

      captions.forEach((caption) => {
        const toggleButton = caption.querySelector('.toggle-caption__toggle');
        const content = caption.querySelector('.toggle-caption__content');
        const spotlight = caption.closest('.hb-spotlight--expanded');

        if (!content || !toggleButton) return;

        // Function to determine if this caption should be collapsible
        const isCollapsible = () => content.offsetHeight >= 28 || (spotlight && mobileView.matches);

        const updateCaptionState = () => {
          if (!isCollapsible()) {
            caption.classList.remove('collapsible-caption');
            content.classList.remove('visually-hidden');
            toggleButton.classList.remove('is-open');
          } else {
            caption.classList.add('collapsible-caption');
            if (!toggleButton.classList.contains('is-open')) {
              content.classList.add('visually-hidden');
            }
          }
        };

        // Initial setup
        updateCaptionState();

        // Toggle open/close behavior
        toggleButton.addEventListener('click', () => {
          const isOpen = toggleButton.classList.toggle('is-open');
          content.classList.toggle('visually-hidden', !isOpen);
        });

        // Run again on viewport resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(() => {
            updateCaptionState();
          }, 250); // Debounce
        });

        // Watch viewport changes
        mobileView.addEventListener('change', updateCaptionState);
      });
    },
  };
}(Drupal, once));
