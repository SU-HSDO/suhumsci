import styled from 'styled-components';
import {
  useSelect,
  SelectOptionDefinition,
  SelectProvider,
  SelectValue,
} from '@mui/base/useSelect';
import { useOption } from '@mui/base/useOption';
import { ChevronDownIcon } from '@heroicons/react/20/solid';
import {
  useEffect,
  useState,
  useId,
  useRef,
  useLayoutEffect,
  RefObject,
  ReactNode,
} from 'preact/compat';
import useOutsideClick from './use-outside-click';

interface OptionProps {
  rootRef: RefObject<HTMLUListElement>;
  children?: ReactNode;
  value: string;
  disabled?: boolean;
  multiple?: boolean;
}

/** Returns the display label for a single option value, or empty string if not found. */
const getOptionLabel = (
  value: string,
  options: SelectOptionDefinition<string>[],
): string => options.find((o) => o.value === value)?.label ?? '';

/** Returns true if two string arrays contain the same values (order-independent). */
const sameStringSet = (a: string[], b: string[]): boolean =>
  a.length === b.length && [...a].sort().every((v, i) => v === [...b].sort()[i]);

const getDisplayInfo = (
  multiple: boolean,
  value: SelectValue<string, boolean>,
  options: SelectOptionDefinition<string>[],
  autoSubmit: boolean,
  defaultValue?: SelectValue<string, boolean>,
): DisplayInfo => {
  if (multiple) {
    const current = (value as string[]) ?? [];
    const committed = (defaultValue as string[]) ?? [];
    const count = current.length;
    const isApplied = autoSubmit || sameStringSet(current, committed);
    return {
      text: `${count} filter${count === 1 ? '' : 's'} ${isApplied ? 'applied' : 'selected'}`,
      isApplied,
    };
  }

  const singleValue = value as string;
  const isApplied = autoSubmit || singleValue === (defaultValue as string);
  return {
    text: isApplied
      ? (singleValue === 'All' ? 'Any' : getOptionLabel(singleValue, options))
      : '1 filter selected',
    isApplied,
  };
};

const StyledOption = styled.li<{
  selected: boolean;
  highlighted: boolean;
  disabled: boolean;
}>`
  cursor: pointer;
  overflow: hidden;
  margin: 0 !important;
  padding: 8px 16px !important;
  fontSize: 16px;
  fontWeight: 400;
  lineHeight: 140%;
  background: ${(props) =>
    props.disabled
      ? '#f1f0ee'
      : props.selected
      ? '#d9d7d2'
      : ''};
  color: ${(props) => (props.disabled ? '#b6b1a9' : '#000')};

  &:hover {
    background: ${(props) =>
      props.disabled
        ? '#f1f0ee'
        : props.selected || props.highlighted
        ? ''
        : '#f1f0ee'};
    color: ${(props) =>
      props.disabled ? '#b6b1a9' : props.selected ? '' : '#000'};

    input[type='checkbox']::before {
      border-color: #b6b1a9;
    }
  }

  &:before {
    display: none !important;
  }

  input[type='checkbox'] {
    margin-right: 8px;
    width: 14px;
    height: 14px;
    appearance: none;
    background-color: #fff;
    border: 1px solid #000000;
    border-radius: 0;
    position: relative;
    cursor: pointer;
  }

  input[type='checkbox']::before {
    content: '';
    position: absolute;
    top: 1px;
    left: 4px;
    width: 4px;
    height: 8px;
    border: solid transparent;
    border-width: 0 1px 1px 0;
    transform: rotate(45deg);
  }

  input[type='checkbox']:checked {
    background-color: transparent;
  }

  input[type='checkbox']:checked::before {
    border-color: #000000;
  }

  /* Hide checkmark */
  &:hover input[type='checkbox']:checked::before {
    border-color: transparent;
  }

  /* Show × on hover when already selected */
  &:hover input[type='checkbox']:checked {
    &::before {
      transform: translate(-50%, -50%) rotate(45deg);
    }

    &::after {
      content: '';
      position: absolute;
      transform: translate(-50%, -50%) rotate(-45deg);
    }

    &::before,
    &::after {
      top: 50%;
      left: 50%;
      width: 8px;
      height: 1px;
      border: none;
      background: #000;
    }

  }

  /* Adjusting for disabled state */
  input[type='checkbox']:disabled {
    background-color: #f1f0ee;
    border-color: #b6b1a9;
  }

  input[type='checkbox']:disabled:checked::before {
    border-color: #b6b1a9;
  }
`;

function CustomOption(props: OptionProps) {
  const { children, value, rootRef, id, disabled = false, multiple } = props;
  const { getRootProps, highlighted, selected } = useOption({
    rootRef: rootRef,
    value,
    disabled,
    label: children,
    id,
  });

  const { ...otherProps }: { id: string } = getRootProps();

  useEffect(() => {
    if (highlighted && id && rootRef?.current?.parentElement) {
      const item = document.getElementById(id);
      if (item) {
        const itemTop = item?.offsetTop;
        const itemHeight = item?.offsetHeight;
        const parentScrollTop = rootRef.current.parentElement.scrollTop;
        const parentHeight = rootRef.current.parentElement.offsetHeight;

        if (itemTop < parentScrollTop) {
          rootRef.current.parentElement.scrollTop = itemTop;
        }

        if (itemTop + itemHeight > parentScrollTop + parentHeight) {
          rootRef.current.parentElement.scrollTop =
            itemTop - parentHeight + itemHeight;
        }
      }
    }
  }, [rootRef, id]);

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
        <input
          type="checkbox"
          checked={selected}
          readOnly
          disabled={disabled}
        />
      )}
      {children}
    </StyledOption>
  );
}

interface Props {
  options: SelectOptionDefinition<string>[];
  label?: string;
  ariaLabelledby?: string;
  defaultValue?: SelectValue<string, boolean>;
  onChange?: (
    event: MouseEvent | KeyboardEvent | FocusEvent | null,
    value: SelectValue<string, boolean>,
  ) => void;
  multiple?: boolean;
  disabled?: boolean;
  value?: SelectValue<string, boolean>;
  required?: boolean;
  name: string;
  autoSubmit?: boolean;
}

const SelectList = ({
  options = [],
  label,
  multiple,
  ariaLabelledby,
  required,
  defaultValue,
  name,
  autoSubmit = true,
  ...props
}: Props) => {
  const labelId = name;
  const labeledBy = ariaLabelledby ?? labelId;

  const inputRef = useRef<HTMLInputElement | null>(null);
  const listboxRef = useRef<HTMLUListElement | null>(null);
  const [listboxVisible, setListboxVisible] = useState<boolean>(false);
  const containerProps = useOutsideClick((event) => {
    setTimeout(() => setListboxVisible(false), 50); // Short delay to let state settle
  });

  const { getButtonProps, getListboxProps, contextValue, value } = useSelect<
    string,
    boolean
  >({
    listboxRef,
    listboxId: `${name}-preact-listbox`,
    onOpenChange: setListboxVisible,
    open: listboxVisible,
    defaultValue,
    multiple,
    ...props,
  });

  useEffect(() => {
    if (!listboxVisible && value && value.length > 0) {
      listboxVisible && listboxRef.current?.focus();
    }
  }, [listboxVisible, value]);

  const [listboxMaxHeight, setListboxMaxHeight] = useState<string>('auto');

  useLayoutEffect(() => {
    // Measure actual height of first 6 items
    if (listboxRef.current) {
      const items = Array.from(listboxRef.current.children) as HTMLElement[];
      if (items.length <= 6) {
        setListboxMaxHeight('fit-content');
      } else {
        const maxHeight = items.slice(0, 6).reduce((sum, el) => sum + el.offsetHeight, 0);
        setListboxMaxHeight(`${maxHeight}px`);
      }
    }

    // Existing scroll-into-view logic
    const parentContainer =
      listboxRef.current?.parentElement?.getBoundingClientRect();
    if (
      parentContainer &&
      (parentContainer.bottom > window.innerHeight || parentContainer.top < 0)
    ) {
      listboxRef.current?.parentElement?.scrollIntoView({
        behavior: 'smooth',
        block: 'end',
        inline: 'nearest',
      });
    }
  }, [listboxVisible, value]);

  const optionChosen = multiple && value ? value.length > 0 : !!value;

  return (
    <div
      {...containerProps}
      style={{
        position: 'relative',
        width: '100%',
        minWidth: '250px',
      }}
    >
      {label && (
        <div
          id={labelId}
          class="select-preact__label"
        >
          {label}
        </div>
      )}

      <button
        {...getButtonProps()}
        aria-labelledby={labeledBy}
        style={{
          background: '#fff',
          color: '#000',
          width: '100%',
          border: props.disabled ? '1px solid #ABABA9' : '1px solid #000',
          borderRadius: '5px',
          textAlign: 'left',
          minHeight: '40px',
          paddingInline: !optionChosen && multiple ? '10px' : '16px',
        }}
      >
        <span
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
          }}
        >
          {optionChosen && (() => {
            const { text, isApplied } = getDisplayInfo(multiple, value, options, autoSubmit, defaultValue);
            return (
              <span
                className={`select-preact__option-label ${isApplied ? 'select-preact__checkmark' : ''}`}
              >
                {text}
              </span>
            );
          })()}
          {!optionChosen && multiple && (
            <span className='select-preact__option-label'>
              Choose one or more options
            </span>
          )}

          <ChevronDownIcon
            width={20}
            style={{
              flexShrink: '0',
              marginLeft: 'auto',
              color: props.disabled ? '#ABABA9' : '#000',
            }}
          />
        </span>
      </button>

      <div
        style={{
          position: 'absolute',
          zIndex: '10',
          background: '#fff',
          maxHeight: listboxMaxHeight,
          overflowY: options.length > 6 ? 'scroll' : 'auto',
          width: '100%',
          border: '1px solid #ababa9',
          display: listboxVisible ? 'block' : 'none',
        }}
      >
        <ul
          {...getListboxProps()}
          aria-hidden={!listboxVisible}
          aria-labelledby={labeledBy}
          style={{
            listStyle: 'none',
            margin: 0,
            padding: 0,
          }}
        >
          <SelectProvider value={contextValue}>
            {!required && !multiple && (
              <CustomOption
                value='All'
                rootRef={listboxRef}
                id={`${name}-empty`}
              >
                Any
              </CustomOption>
            )}

            {options.map((option) => {
              return (
                <CustomOption
                  key={option.value}
                  value={option.value}
                  disabled={option.disabled}
                  rootRef={listboxRef}
                  id={`${name}-${option.value.replace(/\W+/g, '-')}`}
                  multiple={multiple}
                >
                  {option.label}
                </CustomOption>
              );
            })}
          </SelectProvider>
        </ul>
      </div>
      {name && (
        <input ref={inputRef} name={name} type="hidden" value={value ?? ''} disabled />
      )}
    </div>
  );
};

export default SelectList;
