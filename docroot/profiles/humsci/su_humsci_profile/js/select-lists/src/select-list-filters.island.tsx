import { createIslandWebComponent } from 'preact-island';
import SelectList from './select-list';
import { useEffect, useRef, useState } from 'preact/compat';

const FilterIsland = ({}) => {
  const ref = useRef();
  const [originalSelect, setOriginalSelect] = useState(null);
  const [label, setLabel] = useState('');
  const [selectedValues, setSelectedValues] = useState([]);
  const [autoSubmit, setAutoSubmit] = useState(false);

  useEffect(() => {
    setOriginalSelect(ref.current.parentNode.querySelector('select'));
    setLabel(ref.current.parentNode.querySelector('label').textContent);

    // Detect whether the closest views exposed form has BEF auto-submit.
    const form = ref.current.closest('form, .views-exposed-form');
    setAutoSubmit(
      !!(form && (form.hasAttribute('data-bef-auto-submit') || form.closest('[data-bef-auto-submit]')))
    );

    // Add the same min width of the selector to parent.
    const parent = ref.current.parentNode;
    parent.style.minWidth = '250px';

    // Use visibility because when display none, the field isn't updated
    // sometimes after ajax.
    const origSelect = ref.current.parentNode.querySelector('select');
    origSelect.setAttribute('aria-hidden', 'true');
    origSelect.style.visibility = 'hidden';
    origSelect.style.height = '0';
    origSelect.style.position = 'absolute';

    const origLabel = ref.current.parentNode.querySelector('label');
    origLabel.style.visibility = 'hidden';
    origLabel.style.height = '0';
    origLabel.style.position = 'absolute';

    if (origSelect?.getAttribute('multiple')) {
      const selectedOptions = Array.from(origSelect.children)
        .filter((option) => option.getAttribute('selected') === 'selected')
        .map((option) => option.getAttribute('value'));

      setSelectedValues(selectedOptions);
    }
  }, []);

  useEffect(() => {
    if (originalSelect) {
      Array.from(originalSelect.children).forEach((option) => {
        if (selectedValues.includes(option.getAttribute('value'))) {
          option.setAttribute('selected', 'selected');
        } else {
          option.removeAttribute('selected');
        }
      });
    }
  }, [selectedValues]);

  const getSelectOptions = (selectElement) => {
    const options = [];

    const optionElements = selectElement.children;

    for (let i = 0; i < optionElements.length; i++) {
      const option = optionElements[i];
      const value = option.getAttribute('value');
      const label = option.textContent;
      options.push({
        value,
        label,
        disabled: option.getAttribute('disabled') === 'disabled',
      });
    }

    return options;
  };

  const getDefaultValue = () => {
    let defaultValue = [];
    for (let option of originalSelect?.children) {
      if (option.getAttribute('selected')) {
        if (!originalSelect.getAttribute('multiple'))
          return option.getAttribute('value');

        defaultValue.push(option.getAttribute('value'));
      }
    }
    return defaultValue;
  };

  const selectOptions = originalSelect && getSelectOptions(originalSelect);

  const onSelectChange = (e, value) => {
    if (!originalSelect.getAttribute('multiple')) {
      originalSelect.value = value;

      originalSelect.dispatchEvent(
        new Event('change', { bubbles: true })
      );

      return;
    }

    const allValues = Array.from(originalSelect.children).map((option) =>
      option.getAttribute('value'),
    );

    const nextValues = value.length === allValues.length ? allValues : value;

    // Set the .selected property on each option (not just the attribute)
    Array.from(originalSelect.options).forEach((option) => {
      option.selected = nextValues.includes(option.getAttribute('value'));
    });

    originalSelect.dispatchEvent(new Event('change', { bubbles: true }));

    setSelectedValues(nextValues);
  };

  return (
    <div ref={ref}>
      {originalSelect && (
        <SelectList
          name={originalSelect.getAttribute('id') + '-preact'}
          options={
            originalSelect?.getAttribute('multiple')
              ? selectOptions
              : selectOptions?.filter((item) => item.value !== 'All')
          }
          label={label}
          multiple={originalSelect.getAttribute('multiple') === 'multiple'}
          onChange={onSelectChange}
          defaultValue={getDefaultValue()}
          value={
            originalSelect.getAttribute('multiple') === 'multiple'
              ? selectedValues
              : undefined
          }
          autoSubmit={autoSubmit}
        />
      )}
    </div>
  );
};

if (process.env.NODE_ENV === 'development') {
  const island = createIslandWebComponent('combobox-select-list', FilterIsland);
  island.render({
    selector: `.select-preact`,
  });
} else {
  (function () {
    Drupal.behaviors.selectPreact = {
      attach: function (context) {
        let contextClass = '';
        try {
          contextClass = '.' + context.getAttribute('class').replace(/ /g, '.');
        } catch (e) {}

        // Remove stale island instances left over from prior AJAX cycles.
        // Without this, each AJAX re-attach accumulates extra <select> and
        // hidden input elements in the DOM, polluting form serialization.
        const selector = contextClass
          ? `${contextClass} .select-preact`
          : '.select-preact';
        document.querySelectorAll(selector).forEach((wrapper) => {
          const existing = wrapper.querySelector('combobox-select-list');
          if (existing) existing.remove();
        });

        const island = createIslandWebComponent(
          'combobox-select-list',
          FilterIsland,
        );
        island.render({
          selector: `${contextClass} .select-preact`,
          initialProps: { focus: contextClass.indexOf('js-view-dom-id') >= 0 },
        });
      },
    };
  })();
}