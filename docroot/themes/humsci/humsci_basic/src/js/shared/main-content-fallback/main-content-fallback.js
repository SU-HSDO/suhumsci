(() => {
  const mainContentTarget = document.querySelector('#main-content');
  // Return if main content target is found, nothing to do.
  if (mainContentTarget) {
    return;
  }

  const mainElement = document.querySelector('main');
  if (mainElement) {
    mainElement.insertAdjacentHTML(
      'afterbegin',
      '<div id="main-content" class="visually-hidden focusable" tabindex="-1">Main content start</div>',
    );
  }
})();
