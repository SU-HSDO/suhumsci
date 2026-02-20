const addCardEvents = (card, mainLink) => {
  let downTime = 0;

  // Add a focus state class to card
  mainLink.addEventListener('focus', () => {
    card.classList.add('is-focused');
  });

  // Remove focus state class from card
  mainLink.addEventListener('blur', () => {
    card.classList.remove('is-focused');
  });

  // Calculate when the "click" starts.
  card.addEventListener('mousedown', () => {
    downTime = Date.now();
  });

  // Calculate when the "click" ends.
  card.addEventListener('mouseup', (event) => {
    // Prevent right-click issues in Windows.
    if (event.button !== 0) {
      return;
    }

    // Ensure clicks on "Add to Calendar" container, a caption toggle or a clickable element.
    const addToCal = event.target.closest('.addtocal-container');
    const captionToggle = event.target.closest('.toggle-caption__toggle');
    const clickableTagNames = ['INPUT']; // Include others here when needed.
    if (addToCal || captionToggle || clickableTagNames.includes(event.target.tagName)) {
      return;
    }

    const upTime = Date.now();
    // If the click "duration" is less than 200ms, trigger a click.
    if (upTime - downTime < 200) {
      mainLink.click();
    }
  });
};

export default addCardEvents;
