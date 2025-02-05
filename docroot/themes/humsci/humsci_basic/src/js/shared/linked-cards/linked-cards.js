(() => {
  // find all hb-vertical-card elements
  const cards = document.querySelectorAll('.hb-vertical-card, .hb-card--date-stacked, .hb-vertical-linked-card, .hb-card--structured');

  // Loop through each card
  cards.forEach((card) => {
    // Find the main link within each card
    let mainLink = '';

    // Logic for vertical card, date stacked card and structured card.
    if (card.querySelector('.hb-card__title a')) {
      mainLink = card.querySelector('.hb-card__title a');
    // Logic for vertical linked card.
    } else {
      mainLink = card.querySelector(
        '.hb-vertical-linked-card__title__link',
      );
    }

    if (!mainLink) {
      return;
    }

    let downTime = 0;

    // Calculate when the "click" starts.
    function handleMouseDown() {
      downTime = Date.now();
    }

    // Calculate when the "click" ends.
    function handleMouseUp() {
      const upTime = Date.now();
      // If the click "duration" is less than 200ms, trigger a click.
      if (upTime - downTime < 200) {
        mainLink.click();
      }
    }

    // Add a focus state class to card
    function handleFocus() {
      card.classList.add('is-focused');
    }

    // Remove focus state class from card
    function handleBlur() {
      card.classList.remove('is-focused');
    }

    mainLink.addEventListener('focus', handleFocus);
    mainLink.addEventListener('blur', handleBlur);
    card.addEventListener('mousedown', handleMouseDown);
    card.addEventListener('mouseup', handleMouseUp);
  });
})();
