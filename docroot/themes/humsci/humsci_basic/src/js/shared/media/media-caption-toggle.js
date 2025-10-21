(function (Drupal, once) {
  Drupal.behaviors.mediaCaptionToggle = {
    attach(context) {
      const captions = once(
        'media-caption-toggle',
        '.field-media-image-caption',
        context,
      );

      captions.forEach((caption) => {
        // Collect all child elements that contain visible text
        const captionText = Array.from(caption.children).filter(
          (el) => el.textContent && el.textContent.trim().length > 0,
        );
        if (!captionText) return;

        const actualHeight = caption.offsetHeight;

        if (actualHeight >= 40) {
          caption.classList.add('toggle-caption__wrapper');

          // Wrap all text elements inside a container for easier toggling
          const textWrapper = document.createElement('div');
          textWrapper.className = 'toggle-caption__content';
          captionText.forEach((el) => textWrapper.appendChild(el));
          textWrapper.style.visibility = 'hidden';
          caption.appendChild(textWrapper);

          // Create toggle button
          const toggle = document.createElement('button');
          toggle.className = 'toggle-caption__toggle';
          toggle.setAttribute('aria-hidden', 'true');
          caption.insertBefore(toggle, textWrapper);

          const closeBtn = document.createElement('button');
          closeBtn.className = 'toggle-caption__close';
          closeBtn.setAttribute('aria-hidden', 'true');
          closeBtn.style.visibility = 'hidden';

          caption.insertBefore(closeBtn, textWrapper);

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
      });
    },
  };
}(Drupal, once));
