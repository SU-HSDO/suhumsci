(function (Drupal) {
  Drupal.behaviors.mediaCaptionToggle = {
    attach(context) {
      const processCaptions = () => {
        const captions = context.querySelectorAll('.field-media-image-caption');

        captions.forEach((caption) => {
          const parentSpotlight = caption.closest('.hb-spotlight--expanded');
          const viewportWidth = window.innerWidth;
          const actualHeight = caption.offsetHeight;

          const captionText = Array.from(caption.children).filter(
            (el) => !el.classList.contains('toggle-caption__toggle')
              && !el.classList.contains('toggle-caption__content')
              && el.textContent
              && el.textContent.trim().length > 0,
          );

          // Check if toggle structure already exists
          const existingWrapper = caption.querySelector('.toggle-caption__content');

          // Condition to add toggle
          const addToggle = actualHeight >= 38 || (parentSpotlight && viewportWidth <= 991);

          // Condition to remove toggle
          const removeToggle = !addToggle;

          // Add toggle structure
          if (addToggle && !existingWrapper) {
            caption.classList.add('toggle-caption__wrapper');

            // Create wrapper
            const textWrapper = document.createElement('div');
            textWrapper.className = 'toggle-caption__content';
            textWrapper.style.visibility = 'hidden';
            caption.appendChild(textWrapper);

            // Create close button (inside wrapper, before text)
            const closeBtn = document.createElement('button');
            closeBtn.className = 'toggle-caption__close';
            closeBtn.setAttribute('aria-hidden', 'true');
            closeBtn.style.visibility = 'hidden';
            textWrapper.appendChild(closeBtn);

            // Move text inside wrapper
            captionText.forEach((el) => textWrapper.appendChild(el));

            // Create toggle button (outside wrapper)
            const toggle = document.createElement('button');
            toggle.className = 'toggle-caption__toggle';
            toggle.setAttribute('aria-hidden', 'true');
            caption.insertBefore(toggle, textWrapper);

            // Toggle behavior
            toggle.addEventListener('click', () => {
              toggle.style.display = 'none';
              textWrapper.style.visibility = 'visible';
              closeBtn.style.visibility = 'visible';
            });

            closeBtn.addEventListener('click', () => {
              textWrapper.style.visibility = 'hidden';
              closeBtn.style.visibility = 'hidden';
              toggle.style.display = 'block';
            });
          }

          // Remove toggle structure
          if (removeToggle && existingWrapper) {
            caption.classList.remove('toggle-caption__wrapper');

            const toggleBtn = caption.querySelector('.toggle-caption__toggle');
            const closeBtn = caption.querySelector('.toggle-caption__close');
            const textWrapper = caption.querySelector('.toggle-caption__content');

            // Move caption text elements back to main caption
            const innerElements = Array.from(textWrapper.children).filter(
              (el) => !el.classList.contains('toggle-caption__close'),
            );
            innerElements.forEach((el) => caption.appendChild(el));

            // Remove created buttons and wrapper
            if (toggleBtn) toggleBtn.remove();
            if (closeBtn) closeBtn.remove();
            if (textWrapper) textWrapper.remove();
          }
        });
      };

      // Initial run
      processCaptions();

      // Run again on viewport resize
      let resizeTimeout;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          processCaptions();
        }, 250); // Debounce
      });
    },
  };
}(Drupal));
