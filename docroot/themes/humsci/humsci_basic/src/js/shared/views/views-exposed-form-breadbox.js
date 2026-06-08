((Drupal, once) => {
  const getActiveFilters = (form) => Array.from(form.querySelectorAll('select')).flatMap((select) => {
    if (select.multiple) {
      return Array.from(select.options)
        .filter((opt) => opt.selected)
        .map((opt) => ({ select, option: opt }));
    }
    // Single select: only include if a real value is chosen.
    const selected = select.options[select.selectedIndex];
    if (!selected || selected.value === '' || selected.value === 'All') {
      return [];
    }
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
          // Multi-select: click the matching Preact listbox item to deselect.
          const itemId = `${selectId}-preact-${option.value.replace(/\W+/g, '-')}`;
          const listItem = document.getElementById(itemId);
          if (listItem) {
            listItem.click();
          } else {
            option.selected = false;
            select.dispatchEvent(new Event('change', { bubbles: true }));
          }
        } else {
          // Single select: click the "Any" / empty option in the Preact listbox.
          const emptyItemId = `${selectId}-preact-empty`;
          const emptyItem = document.getElementById(emptyItemId);
          if (emptyItem) {
            emptyItem.click();
          } else {
            select.value = 'All';
            select.dispatchEvent(new Event('change', { bubbles: true }));
          }
        }
      });

      breadbox.appendChild(item);
    });
  };

  Drupal.behaviors.viewsExposedFormBreadbox = {
    attach(context) {
      once('views-exposed-form-breadbox', '.views-exposed-form', context).forEach(
        (wrapper) => {
          const form = wrapper.querySelector('form') ?? wrapper;

          const breadbox = document.createElement('div');
          breadbox.className = 'breadbox';

          const actions = form.querySelector(
            '[data-drupal-selector="edit-actions"], .form-actions',
          );
          if (actions) {
            actions.insertAdjacentElement('afterend', breadbox);
          } else {
            form.appendChild(breadbox);
          }

          updateBreadbox(form, breadbox);

          form.addEventListener('change', () => updateBreadbox(form, breadbox));
        },
      );
    },
  };
})(Drupal, once);
