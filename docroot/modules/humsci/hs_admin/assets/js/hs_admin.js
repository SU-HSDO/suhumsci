Drupal.behaviors.hsAdmin = {
  attach: function (context) {
    function alterSourceBtnTxt() {
      const htmlBtn = context.querySelector('.ck-source-editing-button');

      if (htmlBtn) {
        const htmlBtnLabel = htmlBtn.querySelector('.ck-button__label');
        htmlBtnLabel.innerText = 'HTML';
      }
    }

    // We wait for initial load due to async racing condition of CK5.
    window.addEventListener('load', function() {
      alterSourceBtnTxt();
    });

    // Then we observe when additional text areas are added on the DOM
    const ckContainer = document.querySelector('.form-textarea-wrapper');
    const observer = new MutationObserver(mutationCallback);

    if (ckContainer) {
      observer.observe(ckContainer, { childList: true, subtree: true });
    }

    function mutationCallback(mutations, observer) {
      alterSourceBtnTxt();
      observer.disconnect();
    }

    // Here we handle observing text format changes (Basic HTML -> Full HTML, etc.)
    const sourceInput = context.querySelector('.js-filter-list');

    if (sourceInput) {
      sourceInput.addEventListener('change', () => {
        const textArea = ckContainer.querySelector('.js-text-full');
        const observer = new MutationObserver(mutationCallback);
        observer.observe(textArea, { attributes: true });

        function mutationCallback(mutations, observer) {
          alterSourceBtnTxt();
        }
      });
    }

    // Handle Seven theme row weights
    const rowWeightsBtn = context.querySelector('.tabledrag-toggle-weight-wrapper .tabledrag-toggle-weight');

    if (rowWeightsBtn && rowWeightsBtn.innerText == 'Hide row weights') {
      rowWeightsBtn.setAttribute('style', 'display: inline;');
    }
  },
};

Drupal.behaviors.menuLinkCshs = {
  attach: function (context) {
    let menuLinkWeightWrapper;
    let labels = [
      'Parent menu',
      'First level menu item',
      'Second level menu item',
      'Third level menu item',
    ];
    let helpTexts = [
      'Select the main item under which your current content will be organized.',
      'Choose the first level menu item that directly falls under your selected parent menu.',
      'Choose the item that falls under your selected first level menu item.',
      'Choose the item that falls under your selected second level menu item.',
    ];
    if (context instanceof Document) {
      menuLinkWeightWrapper = context.querySelector(
        '#menu-link-weight-wrapper'
      );
    }
    if (
      context instanceof HTMLDivElement &&
      context.getAttribute('id') === 'menu-link-weight-wrapper'
    ) {
      menuLinkWeightWrapper = context;
    }
    if (!menuLinkWeightWrapper) {
      return;
    }
    // Use a timeout to allow CSHS to update the markup.
    setTimeout(() => {
      const cshsContainer = menuLinkWeightWrapper.previousElementSibling;
      // Ensure that each select wrapper is only processed once.
      const selectWrappers = once(
        'chsh-select-wrapper',
        '.select-wrapper',
        cshsContainer
      );
      selectWrappers.forEach((selectWrapper) => {
        const index = parseInt(selectWrapper.getAttribute('data-level'), 10);
        if (isNaN(index) || index < 0 || index > 3) {
          return;
        }
        const label = document.createElement('label');
        label.className = 'form-item__label';
        label.innerText = labels[index];
        const helptext = document.createElement('div');
        helptext.className = 'form-item__description';
        helptext.innerText = helpTexts[index];
        selectWrapper.insertAdjacentElement('beforebegin', label);
        selectWrapper.insertAdjacentElement('beforeend', helptext);
      });
    }, 0);
  }
}
