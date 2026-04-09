const addContextualEvents = (cardImageLink) => {
  cardImageLink.addEventListener('click', (e) => {
    const contextualLink = e.target.closest('.contextual-links a');
    const contextual = e.target.closest('.contextual');

    if (contextualLink) {
      e.stopPropagation();
      return;
    }

    if (contextual) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
};

const addCaptionEvents = (cardImageLink) => {
  cardImageLink.addEventListener('click', (e) => {
    const captionToggle = e.target.closest('button.toggle-caption__toggle');

    if (captionToggle) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
};

const addContextualImageLinkEvents = (cardImageLink, drupalSettings) => {
  const isAuthenticated = drupalSettings?.user?.uid > 0;

  const contextualRegion = isAuthenticated
    ? cardImageLink.querySelector('article.contextual-region')
    : null;

  const caption = cardImageLink.querySelector('.field-media-image-caption');

  if (contextualRegion) {
    addContextualEvents(cardImageLink);
  }

  if (caption) {
    addCaptionEvents(cardImageLink);
  }
};

export default addContextualImageLinkEvents;
