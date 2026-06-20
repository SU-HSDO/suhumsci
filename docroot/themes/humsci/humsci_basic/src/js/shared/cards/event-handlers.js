const addCardEvents = (card, linkHref) => {
  let downTime = 0;

  // Add class to apply card-wide hover styles.
  if (card.classList.contains('hb-vertical-linked-card')) {
    card.classList.add('hb-vertical-linked-card--linked');
  } else {
    card.classList.add('hb-card--linked');
  }

  // focusin/focusout bubble, so the card reflects focus on any child link.
  card.addEventListener('focusin', () => {
    card.classList.add('is-focused');
  });

  card.addEventListener('focusout', () => {
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

    // Ensure clicks on:
    // - "Add to Calendar" container
    // - Lazy video container
    // - Caption toggle
    // - An input or a button
    const addToCal = event.target.closest('.addtocal-container');
    const lazyVideo = event.target.closest('.hb-video-lazy');
    const captionToggle = event.target.closest('.toggle-caption__toggle');
    const clickableTagNames = ['INPUT', 'BUTTON'];

    if (
      addToCal
      || lazyVideo
      || captionToggle
      || clickableTagNames.includes(event.target.tagName)
    ) {
      return;
    }

    const upTime = Date.now();
    // If the click "duration" is less than 200ms, trigger a click.
    if (upTime - downTime < 200) {
      window.location.assign(linkHref);
    }
  });
};

export default addCardEvents;
