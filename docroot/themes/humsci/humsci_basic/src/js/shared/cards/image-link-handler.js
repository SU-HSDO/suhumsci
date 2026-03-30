const addContextualImageLinkEvents = (cardImageLink) => {
  cardImageLink.addEventListener('click', (e) => {
    const contextualLink = e.target.closest('.contextual-links a');
    const contextual = e.target.closest('.contextual');

    // Allow contextual links to navigate normally
    if (contextualLink) {
      e.stopPropagation();
      return;
    }

    // Block navigation for anything else inside the contextual region
    // (e.g. the trigger button)
    if (contextual) {
      e.preventDefault();
      e.stopPropagation();
      // eslint-disable-next-line no-useless-return
      return;
    }
  });
};

export default addContextualImageLinkEvents;
