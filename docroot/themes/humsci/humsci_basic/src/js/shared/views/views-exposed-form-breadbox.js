((Drupal, once) => {
  const isAutoSubmit = (form) => form.hasAttribute('data-bef-auto-submit')
    || !!form.closest('[data-bef-auto-submit]');

  const getActiveFilters = (form) => Array.from(form.querySelectorAll('select')).flatMap((select) => {
    if (select.multiple) {
      return Array.from(select.options)
        .filter((opt) => opt.selected)
        .map((opt) => ({ select, option: opt }));
    }
    const selected = select.options[select.selectedIndex];
    if (!selected || selected.value === '' || selected.value === 'All') return [];
    return [{ select, option: selected }];
  });

  const updateBreadbox = (form, breadbox) => {
    breadbox.querySelectorAll('.breadbox__item').forEach((c) => c.remove());

    const filters = getActiveFilters(form);
    breadbox.classList.toggle('breadbox--hidden', filters.length === 0);

    filters.forEach(({ select, option }) => {
      const item = document.createElement('button');
      item.type = 'button';
      item.className = 'breadbox__item';
      item.setAttribute('aria-label', `Remove filter: ${option.text}`);

      const label = document.createElement('span');
      label.className = 'breadbox__label';
      label.textContent = option.text;
      item.appendChild(label);

      item.addEventListener('click', () => {
        const selectId = select.getAttribute('id');

        if (select.multiple) {
          const itemId = `${selectId}-preact-${option.value.replace(/\W+/g, '-')}`;
          const listItem = document.getElementById(itemId);
          if (listItem) {
            listItem.click();
          } else {
            option.selected = false;
            select.dispatchEvent(new Event('change', { bubbles: true }));
          }
        } else {
          const emptyItemId = `${selectId}-preact-empty`;
          const emptyItem = document.getElementById(emptyItemId);
          if (emptyItem) {
            emptyItem.click();
          } else {
            select.value = 'All';
            select.dispatchEvent(new Event('change', { bubbles: true }));
          }
        }

        setTimeout(() => {
          updateBreadbox(form, breadbox);

          if (!isAutoSubmit(form)) {
            const applyBtn = form.querySelector(
              '[data-drupal-selector^="edit-submit"], .js-form-submit[value="Apply"]',
            );
            if (applyBtn) applyBtn.click();
          }
        }, 10);
      });

      breadbox.appendChild(item);
    });
  };

  Drupal.behaviors.viewsExposedFormBreadbox = {
    attach(context) {
      once('views-exposed-form-breadbox', '.views-exposed-form', context).forEach(
        (wrapper) => {
          const form = wrapper.querySelector('form') ?? wrapper;
          const autoSubmit = isAutoSubmit(form);

          const breadbox = document.createElement('div');
          breadbox.className = 'breadbox breadbox--hidden';

          const actions = form.querySelector(
            '[data-drupal-selector="edit-actions"], .form-actions',
          );
          if (actions) {
            actions.insertAdjacentElement('afterend', breadbox);
          } else {
            form.appendChild(breadbox);
          }

          const resetBtn = form.querySelector('[data-drupal-selector^="edit-reset"]');
          if (resetBtn) breadbox.appendChild(resetBtn);

          updateBreadbox(form, breadbox);

          if (autoSubmit) {
            form.addEventListener('change', () => updateBreadbox(form, breadbox));
          } else {
            // Delegated on document so it survives AJAX DOM replacement.
            const formSelector = form.getAttribute('data-drupal-selector')
              ?? form.getAttribute('id');

            document.addEventListener('submit', (e) => {
              const submittedForm = e.target.closest
                ? e.target
                : null;
              if (
                submittedForm
                && (submittedForm === form
                  || submittedForm.getAttribute('data-drupal-selector') === formSelector
                  || submittedForm.getAttribute('id') === formSelector)
              ) {
                // The breadbox may have been rebuilt by AJAX — find the current one.
                const currentBreadbox = submittedForm.parentElement?.querySelector('.breadbox')
                  ?? document.querySelector('.breadbox');
                if (currentBreadbox) updateBreadbox(submittedForm, currentBreadbox);
              }
            });
          }
        },
      );
    },
  };
})(Drupal, once);
