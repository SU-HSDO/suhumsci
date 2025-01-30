(() => {
  // find all hb-vertical-card elements
  const cards = document.querySelectorAll('.hb-vertical-card, .hb-card--date-stacked, .hb-vertical-linked-card');

  // Loop through each card
  cards.forEach((card) => {
    // Find the main link within each card
    let mainLink = '';

    // Logic for vertical card and date stacked card.
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

    // Add a click event listener to each card
    function handleClick(event) {
      // Ensure clicks on "Add to Calendar" button or title do not trigger the card click
      if (event.target.classList.contains('addtocal') || event.target.classList.contains('addtocal-title')) {
        return;
      }
      mainLink.click();
    }

    // Stop the click propagation if the click is inside the addtocal menu links
    function handleMenuLinkClick(event) {
      // Prevent the event from bubbling up to the card
      event.stopPropagation();
    }

    // Add event listener to each menu link to stop propagation
    const addToCalLinks = card.querySelectorAll('.addtocal-menu .addtocal-link a');
    addToCalLinks.forEach((link) => {
      link.addEventListener('click', handleMenuLinkClick);
    });

    // Add a focus event listener to each main link
    mainLink.addEventListener('focus', () => {
      // Add a focus state class to card
      card.classList.add('is-focused');
    });

    // Add a blur event listener to each main link
    mainLink.addEventListener('blur', () => {
      // Remove focus state class from card
      card.classList.remove('is-focused');
    });

    card.addEventListener('click', handleClick);
  });
})();
