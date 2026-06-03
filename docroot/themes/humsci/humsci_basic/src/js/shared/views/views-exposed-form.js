((Drupal, once) => {
  /**
   * Returns true if any exposed filter input in the given form has an active
   * (non-empty, non-default) value.
   *
   * Covers: text inputs, native <select> (single + multiple), the hidden
   * inputs written by the Preact combobox, checkboxes, and radio buttons.
   */
  const hasActiveFilter = (form) => {
    // Text inputs and textareas (skip hidden inputs — handled separately).
    const textInputs = Array.from(
      form.querySelectorAll('input[type="text"], input[type="search"], textarea'),
    );
    if (textInputs.some((input) => input.value.trim() !== '')) return true;

    // Native <select> elements.
    // For multi-selects, check whether *any* option is selected.
    // For single selects, skip the "All" / empty default.
    const selects = Array.from(form.querySelectorAll('select'));
    if (
      selects.some((select) => {
        if (select.multiple) {
          return Array.from(select.options).some((opt) => opt.selected);
        }
        return select.value !== '' && select.value !== 'All';
      })
    )
      return true;

    // Hidden inputs written by the Preact combobox (e.g. value="Winter").
    // These live inside .select-preact wrappers and are never empty when
    // a selection is active — skip the ones whose value is blank or "All".
    const hiddenInputs = Array.from(
      form.querySelectorAll('.select-preact input[type="hidden"]'),
    );
    if (hiddenInputs.some((input) => input.value !== '' && input.value !== 'All'))
      return true;

    // Checkboxes and radio buttons.
    const checkables = Array.from(
      form.querySelectorAll('input[type="checkbox"], input[type="radio"]'),
    );
    if (checkables.some((input) => input.checked)) return true;

    return false;
  };

  const updateResetVisibility = (form) => {
    // Match any reset button regardless of the view-specific ID suffix.
    const reset = form.querySelector(
      '[data-drupal-selector^="edit-reset"]',
    );
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

          // React to any filter change or text input, including changes
          // dispatched by the Preact combobox (it fires 'change' on the
          // hidden native <select> after every selection).
          form.addEventListener('change', () => updateResetVisibility(form));
          form.addEventListener('input', () => updateResetVisibility(form));
        },
      );
    },
  };
})(Drupal, once);
