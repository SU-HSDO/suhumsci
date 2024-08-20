import styled from "styled-components";
import {useSelect, SelectOptionDefinition, SelectProvider, SelectValue} from '@mui/base/useSelect';
import {useOption} from '@mui/base/useOption';
import {ChevronDownIcon} from "@heroicons/react/20/solid";
import {useEffect, useState, useId, useRef, useLayoutEffect, RefObject, ReactNode} from "preact/compat";
import useOutsideClick from "./use-outside-click";

interface OptionProps {
  rootRef: RefObject<HTMLUListElement>
  children?: ReactNode;
  value: string;
  disabled?: boolean;
  multiple?: boolean;
}

const SelectedItem = styled.span`
  border: 1px solid #b6b1a9;
  padding: 4px 8px;
  margin-right: 5px;
  border-radius: 4px;
  white-space: nowrap;
`

const renderSelectedValue = (value: SelectValue<string, boolean>, options: SelectOptionDefinition<string>[]) => {

  if (Array.isArray(value)) {
    return value.map(item =>
      <SelectedItem
        key={item}
      >
        {renderSelectedValue(item, options)}
      </SelectedItem>
    );
  }
  const selectedOption = options.find((option) => option.value === value);
  return selectedOption ? selectedOption.label : null;
}

const StyledOption = styled.li<{ selected: boolean, highlighted: boolean, disabled: boolean }>`
  cursor: pointer;
  overflow: hidden;
  margin: 0 !important;
  padding: 5px 10px !important;
  background: ${props => props.disabled ? "#f1f0ee" : props.selected ? "#b6b1a9" : props.highlighted ? "#d9d7d2" : ""};
  color: ${props => props.disabled? "#b6b1a9" : "#000"};
  text-decoration: ${props => props.highlighted ? "underline" : "none"};;

  &:hover {
    background: ${props => props.disabled ? "#f1f0ee" : (props.selected || props.highlighted ? "" : "#dbdcde")};
    color: ${props => props.disabled ? "#b6b1a9" : props.selected ? "" : "#000"};
    text-decoration: ${props => !props.disabled && "underline"};
  }

  &:before {
    display: none !important;
  }

  input[type="checkbox"] {
    margin-right: 8px;
    width: 16px;
    height: 16px;
    appearance: none;
    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 2px;
    position: relative;
    cursor: pointer;
  }

  input[type="checkbox"]:checked {
    background-color: #413e39;
    border-color: #413e39;
  }

  input[type="checkbox"]:checked::before {
    content: '';
    position: absolute;
    top: 1px;
    left: 4px;
    width: 6px;
    height: 10px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
  }

  /* Adjusting for disabled state */
  input[type="checkbox"]:disabled {
    background-color: #f1f0ee;
    border-color: #b6b1a9;
  }

  input[type="checkbox"]:disabled:checked::before {
    border-color: #b6b1a9;
  }
`

function CustomOption(props: OptionProps) {
  const {children, value, rootRef, id, disabled = false, multiple} = props;
  const {getRootProps, highlighted, selected} = useOption({
    rootRef: rootRef,
    value,
    disabled,
    label: children,
    id
  });

  const {...otherProps}: { id: string } = getRootProps();

  useEffect(() => {
    if (highlighted && id && rootRef?.current?.parentElement) {
      const item = document.getElementById(id);
      if (item) {
        const itemTop = item?.offsetTop;
        const itemHeight = item?.offsetHeight;
        const parentScrollTop = rootRef.current.parentElement.scrollTop
        const parentHeight = rootRef.current.parentElement.offsetHeight;

        if (itemTop < parentScrollTop) {
          rootRef.current.parentElement.scrollTop = itemTop;
        }

        if ((itemTop + itemHeight) > parentScrollTop + parentHeight) {
          rootRef.current.parentElement.scrollTop = itemTop - parentHeight + itemHeight;
        }
      }
    }
  }, [rootRef, id])

  return (
    <StyledOption
      {...otherProps}
      id={id}
      selected={selected}
      highlighted={highlighted}
      disabled={disabled}
    >
      {/* Checkbox Element if multiple */}
      {multiple && (
        <input type="checkbox" checked={selected} readOnly disabled={disabled} />
      )}
      {children}
    </StyledOption>
  );
}

interface Props {
  options: SelectOptionDefinition<string>[];
  label?: string
  ariaLabelledby?: string
  defaultValue?: SelectValue<string, boolean>
  onChange?: (event: MouseEvent | KeyboardEvent | FocusEvent | null, value: SelectValue<string, boolean>) => void;
  multiple?: boolean
  disabled?: boolean
  value?: SelectValue<string, boolean>
  required?: boolean
  emptyValue?: string
  emptyLabel?: string
  name: string
}

const SelectList = ({options = [], label, multiple, ariaLabelledby, required, defaultValue, name, emptyValue, emptyLabel = "- None -", ...props}: Props) => {
  const labelId = name;
  const labeledBy = ariaLabelledby ?? labelId;

  const inputRef = useRef<HTMLInputElement | null>(null);
  const listboxRef = useRef<HTMLUListElement | null>(null);
  const [listboxVisible, setListboxVisible] = useState<boolean>(false);
  const containerProps = useOutsideClick(() => setListboxVisible(false))

  const {getButtonProps, getListboxProps, contextValue, value} = useSelect<string, boolean>({
    listboxRef,
    listboxId: `${name}-preact-listbox`,
    onOpenChange: setListboxVisible,
    open: listboxVisible,
    defaultValue,
    multiple,
    ...props
  });

  useEffect(() => {
    listboxVisible && listboxRef.current?.focus();
  }, [listboxVisible]);

  useLayoutEffect(() => {
    const parentContainer = listboxRef.current?.parentElement?.getBoundingClientRect();
    if (parentContainer && (parentContainer.bottom > window.innerHeight || parentContainer.top < 0)) {
      listboxRef.current?.parentElement?.scrollIntoView({behavior: "smooth", block: "end", inline: "nearest"});
    }
  }, [listboxVisible, value])

  const optionChosen = (multiple && value) ? value.length > 0 : !!value;

  return (
    <div
      {...containerProps}
      style={{
        position: "relative",
        width: "100%",
        minWidth: "250px"
      }}
    >
      {label &&
        <div
          id={labelId}
          style={{
            marginBottom: "1.2rem",
            fontSize: "1.8rem",
            fontWeight: "600"
          }}
        >
          {label}
        </div>
      }

      <button
        {...getButtonProps()}
        aria-labelledby={labeledBy}
        style={{
          background: "#fff",
          color: "#000",
          width: "100%",
          border: props.disabled ? "1px solid #ABABA9" : "1px solid #000",
          borderRadius: "5px",
          textAlign: "left",
          minHeight: "40px"
        }}
      >
        <span style={{
          display: "flex",
          justifyContent: "space-between",
          flexWrap: "wrap",
        }}>
          {optionChosen &&
            <span style={{overflow: "hidden", maxWidth: "calc(100% - 30px)", padding: "8px 5px 8px 0"}}>
              {(multiple) ? 
                value?.length == options.length ? 'All selected' : `${value?.length} selected`
              :
              renderSelectedValue(value, options)
              }
            </span>
          }
          {(!optionChosen && !multiple) &&
            <span style={{padding: "8px 5px 8px 0", color: "#4c4740"}}>
              {emptyLabel}
            </span>
          }
          {(!optionChosen && multiple) &&
            <span style={{padding: "8px 5px 8px 0", color: "#4c4740"}}>
              Choose one or more options
            </span>
          }

          <ChevronDownIcon width={20} style={{flexShrink: "0", marginLeft: "auto", color: props.disabled ? "#ABABA9" : "#000"}}/>
        </span>
      </button>

      <div
        style={{
          position: "absolute",
          zIndex: "10",
          background: "#fff",
          maxHeight: "125px",
          overflowY: "scroll",
          width: "100%",
          border: "1px solid #D5D5D4",
          boxShadow: "rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0.1) 0px 10px 15px -3px, rgba(0, 0, 0, 0.1) 0px 4px 6px -4px",
          display: listboxVisible ? "block" : "none"
        }}
      >
        <ul
          {...getListboxProps()}
          aria-hidden={!listboxVisible}
          aria-labelledby={labeledBy}
          style={{
            listStyle: "none",
            margin: 0,
            padding: 0
          }}
        >
          <SelectProvider value={contextValue}>
            {(!required && !multiple) &&
              <CustomOption value={emptyValue ?? ""} rootRef={listboxRef} id={`${name}-empty`}>
                {emptyLabel}
              </CustomOption>
            }

            {options.map(option => {
              return (
                <CustomOption key={option.value} value={option.value} disabled={option.disabled} rootRef={listboxRef} id={`${name}-${option.value.replace(/\W+/g, '-')}`} multiple={multiple}>
                  {option.label}
                </CustomOption>
              );
            })}
          </SelectProvider>
        </ul>
      </div>
      {name &&
        <input ref={inputRef} name={name} type="hidden" value={value ?? ""}/>
      }
    </div>
  );
}


export default SelectList;
