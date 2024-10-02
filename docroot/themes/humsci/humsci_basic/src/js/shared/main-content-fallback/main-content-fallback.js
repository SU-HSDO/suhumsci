(() => {
  // Return if main content target is found, nothing to do.
  if (document.querySelector('#main-content')) {
    return;
  }

  const mainElement = document.querySelector('main');
  if (mainElement) {
    mainElement.insertAdjacentHTML(
      'afterbegin',
      '<div id="main-content" class="visually-hidden" tabindex="-1">Main content start</div>',
    );
  }
})();
