/* global ed11yLang, ed11yOnce */
window.editoria11yOptionsOverride = true;
window.editoria11yOptions = (options) => {
  // Change the default button text for the Heading Outline tab.
  if (ed11yLang && ed11yLang.en) {
    ed11yLang.en.buttonOutlineContent = 'Heading Outline';
  }
  return options;
};

window.addEventListener('load', () => {
  // If the editoria11y is not loaded, we don't need to do anything.
  if (typeof ed11yOnce === 'undefined' || !ed11yOnce) {
    return;
  }

  // Editoria11y element is added to the DOM after the load event, we need to
  // observe the body element for changes.
  const observer = new MutationObserver((mutationList) => {
    mutationList.forEach((mutation) => {
      if (mutation.addedNodes.length && mutation.addedNodes[0].nodeName === 'ED11Y-ELEMENT-PANEL') {
        // Once we get the element, we update the styles to make the alert button text black.
        const { shadowRoot } = mutation.addedNodes[0];
        if (shadowRoot) {
          const style = document.createElement('style');
          style.textContent = `
            .ed11y-shut.ed11y-errors #ed11y-toggle {
              color: #000000;
            }
          `;
          shadowRoot.appendChild(style);
        }
      }
    });
  });
  observer.observe(document.body, {
    childList: true,
  });
});
