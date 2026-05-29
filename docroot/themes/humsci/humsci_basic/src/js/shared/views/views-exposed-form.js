((Drupal, once) => {
  /**
   * Returns true if any exposed filter input in the given form has an active
   * (non-empty, non-default) value.
   *
   * Covers: text inputs, <select> (including the hidden native select behind
   * the profile's Preact combobox), checkboxes, and radio buttons.
   */
  const hasActiveFilter = (form) => {
    // Text inputs and textareas.
    const textInputs = Array.from(
      form.querySelectorAll('input[type="text"], input[type="search"], textarea'),
    );
    if (textInputs.some((input) => input.value.trim() !== '')) return true;

    // Select elements — skip the "All" / empty-value default option.
    const selects = Array.from(form.querySelectorAll('select'));
    if (selects.some((select) => select.value !== '' && select.value !== 'All')) return true;

    // Checkboxes and radio buttons.
    const checkables = Array.from(
      form.querySelectorAll('input[type="checkbox"], input[type="radio"]'),
    );
    if (checkables.some((input) => input.checked)) return true;

    return false;
  };

  const updateResetVisibility = (form) => {
    const reset = form.querySelector('[data-drupal-selector="edit-reset"]');
    if (!reset) return;
    reset.style.display = hasActiveFilter(form) ? '' : 'none';
  };

  Drupal.behaviors.viewsExposedFormReset = {
    attach(context) {
      once('views-exposed-form-reset', '.views-exposed-form', context).forEach(
        (wrapper) => {
          const form = wrapper.querySelector('form') ?? wrapper;

          // Set initial visibility.
          updateResetVisibility(form);

          // React to any filter change or text input.
          form.addEventListener('change', () => updateResetVisibility(form));
          form.addEventListener('input', () => updateResetVisibility(form));
        },
      );
    },
  };
})(Drupal, once);
