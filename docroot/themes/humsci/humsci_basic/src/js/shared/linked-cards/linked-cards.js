function linkedCards() {
  // find all hb-vertical-card elements
  const cards = document.querySelectorAll('.hb-vertical-card', '.hb-card--date-stacked');

  // Loop through each card
  cards.forEach((card) => {
    // Find the main link within each card
    const mainLink = card.querySelector('.hb-card__title a');

    if (!mainLink) {
      return;
    }

    // Add a click event listener to each card
    function handleClick() {
      mainLink.click();
    }

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
}

linkedCards();
