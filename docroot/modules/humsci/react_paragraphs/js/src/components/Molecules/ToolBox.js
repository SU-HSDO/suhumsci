import React from 'react';
import styled from 'styled-components';
import PropTypes from 'prop-types';

const ToolBoxWrapper = styled.div`
  border: 1px solid #ccc;
  box-shadow: 3px 3px 3px 3px rgba(0, 0, 0, 0.2)
`;

export const ToolBox = ({items, onTakeItem}) => {
  return (
    <ToolBoxWrapper>
      <span className="toolbox__title">Toolbox</span>
      <div className="toolbox__items">

        {Object.keys(items).map(item_id => (
          <ToolBoxItem
            key={item_id}
            id={item_id}
            item={items[item_id]}
            onTakeItem={onTakeItem}
          />
        ))}

      </div>
    </ToolBoxWrapper>
  );
};

export const ToolBoxItem = ({id, item, onTakeItem}) => {
  item.id = id;
  return (
    <div className="toolbox__items__item">
      <a href="#" onClick={onTakeItem.bind(undefined, item)}>
        Add a {item.label}
      </a>
    </div>
  )
};

ToolBoxItem.propTypes = {
  key: PropTypes.string,
  item: PropTypes.object,
  onTakeItem: PropTypes.func
};
