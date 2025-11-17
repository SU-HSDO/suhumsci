(function (Drupal, once) {
  Drupal.behaviors.copyBookmarkButton = {
    attach: function (context) {
      const copyButtons = once('copy-bookmark', '.copy-bookmark-button', context);

      copyButtons.forEach((copyButton) => {
        const paragraphId = copyButton.dataset.paragraphId;
        if (!paragraphId) return;

        // Swallow pointerdown in capture phase so other listeners can't close the dropdown.
        // PreventDefault is used so some dropdowns that close on pointerdown+focus don't run.
        function swallowPointerDown(e) {
          e.stopImmediatePropagation();
          e.stopPropagation();
          e.preventDefault();
        }

        // Handle click — also in capture phase. We keep this in capture so it's before any other handlers.
        function handleClickCapture() {
          // perform the copy action
          const bookmark = `#component-${paragraphId}`;

          navigator.clipboard.writeText(bookmark);

          // disable and show message
          copyButton.setAttribute('disabled', true);

          const message = document.createElement('span');
          message.classList.add('paragraph-copy-bookmark-message');
          message.textContent = 'Copied to the clipboard!';
          copyButton.insertAdjacentElement('afterend', message);

          setTimeout(() => {
            message.remove();
            copyButton.removeAttribute('disabled');
          }, 3000);
        }

        // Attach listeners in capture phase to run BEFORE Gin's dropdown handlers.
        // Many dropdown implementations close on pointerdown/mousedown (or in capture), so stopping the click only is too late.
        copyButton.addEventListener('pointerdown', swallowPointerDown, true);
        copyButton.addEventListener('mousedown', swallowPointerDown, true);
        copyButton.addEventListener('click', handleClickCapture, true);
      });
    },
  };
})(Drupal, once);
