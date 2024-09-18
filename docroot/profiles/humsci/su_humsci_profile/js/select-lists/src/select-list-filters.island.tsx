import { createIslandWebComponent } from 'preact-island';
import SelectList from './select-list';
import { useEffect, useRef, useState } from 'preact/compat';

const FilterIsland = ({}) => {
  const ref = useRef();
  const [originalSelect, setOriginalSelect] = useState(null);
  const [label, setLabel] = useState('');
  const [selectedValues, setSelectedValues] = useState([]);

  useEffect(() => {
    setOriginalSelect(ref.current.parentNode.querySelector('select'));
    setLabel(ref.current.parentNode.querySelector('label').textContent);

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
      const allValues = Array.from(origSelect.children).map((option) =>
        option.getAttribute('value'),
      );
      const selectedOptions = Array.from(origSelect.children)
        .filter((option) => option.getAttribute('selected') === 'selected')
        .map((option) => option.getAttribute('value'));

      if (selectedOptions.length === allValues.length) {
        selectedOptions.push('All');
      }

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

    if (selectElement?.getAttribute('multiple')) {
      // Add "All" as the first option
      options.push({ value: 'All', label: 'All', disabled: false });
    }

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
      return;
    }

    const allValues = Array.from(originalSelect.children).map((option) =>
      option.getAttribute('value'),
    );

    // Handle "All" selection logic
    if (value.includes('All')) {
      if (selectedValues.includes('All')) {
        const valueWithoutAll = value.filter((v) => v !== 'All');
        setSelectedValues(valueWithoutAll);
      } else {
        setSelectedValues([...allValues, 'All']);
      }
    } else {
      if (selectedValues.includes('All') && value.length === allValues.length) {
        setSelectedValues([]);
      } else if (value.length === allValues.length) {
        setSelectedValues([...allValues, 'All']);
      } else {
        setSelectedValues(value);
      }
    }
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
          emptyLabel={selectOptions.find((item) => item.value === 'All')?.label}
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
