(function (Drupal, once) {
  Drupal.behaviors.copyBookmarkButton = {
    attach: function (context) {
      const copyButtons = once('copy-bookmark', '.copy-bookmark-button', context);

      copyButtons.forEach((copyButton) => {
        const paragraphId = copyButton.dataset.paragraphId;
        if (!paragraphId) return;

        // Handle click
        function handleClickCapture() {
          const bookmark = `#component-${paragraphId}`;

          navigator.clipboard.writeText(bookmark);

          // Close Gin dropdown cleanly
          copyButton.blur();

          const overlay = document.createElement('div');
          overlay.classList.add('paragraph-copy-bookmark-message__overlay');

          const message = document.createElement('span');
          message.classList.add('paragraph-copy-bookmark-message');
          message.textContent = 'Copied to the clipboard!';
          overlay.appendChild(message);

          document.body.appendChild(overlay);

          setTimeout(() => {
            overlay.remove();
          }, 5000);
        }

        copyButton.addEventListener('click', handleClickCapture);
      });
    },
  };
})(Drupal, once);
