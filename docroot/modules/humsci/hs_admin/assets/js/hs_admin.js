Drupal.behaviors.hsAdmin= {
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
  }
};
