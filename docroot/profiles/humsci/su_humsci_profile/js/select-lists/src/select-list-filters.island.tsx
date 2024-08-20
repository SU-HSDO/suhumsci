import { createIslandWebComponent } from "preact-island";
import SelectList from "./select-list";
import { useEffect, useRef, useState } from "preact/compat";

const FilterIsland = ({ focus = false }) => {
  const ref = useRef();
  const [originalSelect, setOriginalSelect] = useState(null);
  const [label, setLabel] = useState("");
  const [selectedValues, setSelectedValues] = useState([]);

  useEffect(() => {
    setOriginalSelect(ref.current.parentNode.querySelector("select"));
    setLabel(ref.current.parentNode.querySelector("label").textContent);

    // Use visibility because when display none, the field isn't updated
    // sometimes after ajax.
    const origSelect = ref.current.parentNode.querySelector("select");
    origSelect.setAttribute("aria-hidden", "true");
    origSelect.style.visibility = "hidden";
    origSelect.style.height = "0";
    origSelect.style.position = "absolute";

    const origLabel = ref.current.parentNode.querySelector("label");
    origLabel.style.visibility = "hidden";
    origLabel.style.height = "0";
    origLabel.style.position = "absolute";
  }, []);

  const addAllOptionToSelect = (selectElement) => {
    if (!selectElement) return;
  
    // Check if "All" option already exists to avoid duplicates
    const allOptionExists = Array.from(selectElement.children).some(
      option => option.getAttribute("value") === "All"
    );
  
    if (!allOptionExists) {
      // Create a new <option> element for "All"
      const allOption = document.createElement("option");
      allOption.value = "All";
      allOption.textContent = "All";
      allOption.disabled = false; // Optional: change this if needed
  
      // Insert "All" at the top of the children
      selectElement.insertBefore(allOption, selectElement.firstChild);
    }
  };

  const getSelectOptions = (selectElement) => {
    if (selectElement?.getAttribute("multiple")) {
      addAllOptionToSelect(selectElement);
    }

    const options = [];

    const optionElements = selectElement.children;
    
    for (let i = 0; i < optionElements.length; i++) {
      const option = optionElements[i];
      const value = option.getAttribute("value");
      const label = option.textContent;
      options.push({
        value,
        label,
        disabled: option.getAttribute("disabled") === "disabled",
      });
    }
  
    return options;
  };

  const getDefaultValue = () => {
    let defaultValue = [];
    for (let option of originalSelect?.children) {
      if (option.getAttribute("selected")) {
        if (!originalSelect.getAttribute("multiple"))
          return option.getAttribute("value");

        defaultValue.push(option.getAttribute("value"));
      }
    }
    return defaultValue;
  };

  const selectOptions = originalSelect && getSelectOptions(originalSelect);

  const onSelectChange = (e, value) => {
    if (!originalSelect.getAttribute('multiple')) {
      return originalSelect.value = value;
    } else {
      const allValues = Array.from(originalSelect.children).map(option => option.getAttribute('value'));
      console.log(value);
      if (value.includes('All')) {
        for (let option of originalSelect.children) {
          option.setAttribute('selected', 'true');
        }
      } else {
        if (!value.includes('All') && value.length + 1 === allValues.length) {
          for (let option of originalSelect.children) {
            option.setAttribute('selected', 'true');
          }
        } else {
          for (let option of originalSelect.children) {
            if (value.includes(option.getAttribute('value'))) {
              option.setAttribute('selected', 'true')
            } else {
              option.removeAttribute('selected');
            }
          }
        }
      }
    }
  };

  return (
    <div ref={ref}>
      {originalSelect && (
        <SelectList
          name={originalSelect.getAttribute("id") + "-preact"}
          options={
            originalSelect?.getAttribute("multiple")
              ? selectOptions
              : selectOptions?.filter((item) => item.value !== "All")
          }
          label={label}
          multiple={originalSelect.getAttribute("multiple") === "multiple"}
          onChange={onSelectChange}
          defaultValue={getDefaultValue()}
          emptyLabel={selectOptions.find((item) => item.value === "All")?.label}
        />
      )}
    </div>
  );
};

if (process.env.NODE_ENV === "development") {
  const island = createIslandWebComponent("combobox-select-list", FilterIsland);
  island.render({
    selector: `.select-preact`,
  });
} else {
  (function () {
    Drupal.behaviors.selectPreact = {
      attach: function (context) {
        let contextClass = "";
        try {
          contextClass = "." + context.getAttribute("class").replace(/ /g, ".");
        } catch (e) {}

        const island = createIslandWebComponent(
          "combobox-select-list",
          FilterIsland
        );
        island.render({
          selector: `${contextClass} .select-preact`,
          initialProps: { focus: contextClass.indexOf("js-view-dom-id") >= 0 },
        });
      },
    };
  })();
}
