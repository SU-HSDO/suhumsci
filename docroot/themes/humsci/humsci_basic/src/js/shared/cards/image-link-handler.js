const addContextualImageLinkEvents = (cardImageLink) => {
  cardImageLink.addEventListener('click', (e) => {
    const contextualLink = e.target.closest('.contextual-links a');
    const contextual = e.target.closest('.contextual');
    const captionToggle = e.target.closest('button.toggle-caption__toggle');

    /**
     * Allow contextual links to behave normally.
     * These are actual navigation links inside the contextual menu.
     */
    if (contextualLink) {
      e.stopPropagation();
      return;
    }

    /**
     * Prevent the parent <a> from navigating when interacting with:
     * - Contextual UI (e.g. trigger button, menu container)
     * - Caption toggle button
     *
     * We use preventDefault() to stop link navigation,
     * but still allow the internal JS behavior of these elements.
     */
    if (contextual || captionToggle) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
};

export default addContextualImageLinkEvents;
