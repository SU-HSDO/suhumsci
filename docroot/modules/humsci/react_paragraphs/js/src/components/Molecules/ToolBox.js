import React from 'react';
import styled from 'styled-components';
import PropTypes from 'prop-types';
import {Draggable, Droppable} from "react-beautiful-dnd";

const ToolBoxWrapper = styled.div`
`;

const ToolBoxItems = styled.div`
  display: flex;
`;


export const ToolBox = ({items, onTakeItem}) => {
  return (
    <ToolBoxWrapper>
      <h2 className="toolbox__title">Content Toolbox</h2>

      <Droppable
        droppableId="toolbox" type="item"
        direction="horizontal"
        isDropDisabled={true}
      >
        {(provided, snapshot) => (

          <ToolBoxItems
            {...provided.droppableProps}
            ref={provided.innerRef}
          >
            {Object.keys(items).map((item_id, index) => (
              <ToolBoxItem
                key={item_id}
                id={item_id}
                item={items[item_id]}
                onTakeItem={onTakeItem}
                index={index}
              />
            ))}
          </ToolBoxItems>

        )}
      </Droppable>
    </ToolBoxWrapper>
  );
};

const ToolboxItem = styled.div`
  display:block;
  border: 1px solid #ccc;
  box-shadow: 3px 3px 3px 3px rgba(0, 0, 0, 0.2);
  padding: 10px;
  margin-right: 10px;
  background: #f9f9f9;
`;

const ItemIcon = styled.img`
  width: 20px;
  height: 20px;
  display: block;
  margin: 0 auto;
`;

export const ToolBoxItem = ({id, item, index, onTakeItem}) => {

  return (

    <Draggable draggableId={'new-item-' + id} index={index}>
      {provided => (
        <ToolboxItem
          ref={provided.innerRef}
          {...provided.draggableProps}
          {...provided.dragHandleProps}
        >
          <ItemIcon
            src={item.icon ? item.icon : 'https://png.pngtree.com/svg/20150803/320b35b99d.png'}
          />
          {item.label}
        </ToolboxItem>
      )}
    </Draggable>
  )
};

ToolBoxItem.propTypes = {
  key: PropTypes.string,
  item: PropTypes.object,
  onTakeItem: PropTypes.func
};
