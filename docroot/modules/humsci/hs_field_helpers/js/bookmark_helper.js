(function (Drupal, once) {
  Drupal.behaviors.copyBookmarkButton = {
    attach: function (context) {
      const copyButtons = once('copy-bookmark', '.copy-bookmark-button', context);

      copyButtons.forEach((copyButton) => {
        const paragraphId = copyButton.dataset.paragraphId;

        if (!paragraphId) return;

        copyButton.addEventListener('click', (event) => {
          // Prevents loading page again
          event.preventDefault();

          // Prevents dropdown from closing
          event.stopPropagation();

          const bookmark = `#component-${paragraphId}`;

          navigator.clipboard.writeText(bookmark);
          copyButton.setAttribute('disabled', true);

          const message = document.createElement('span');
          message.classList.add('copy-bookmark-message');
          message.textContent = 'Copied to the clipboard!';
          copyButton.insertAdjacentElement('afterend', message);

          // Remove after 3 seconds
          setTimeout(() => {
            message.remove();
            copyButton.removeAttribute('disabled');
          }, 3000);
        });
      });
    },
  };
})(Drupal, once);
