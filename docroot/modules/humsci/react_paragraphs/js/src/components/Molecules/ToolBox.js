import React from 'react';
import styled from 'styled-components';
import PropTypes from 'prop-types';
import {Draggable, Droppable} from "react-beautiful-dnd";

const ToolBoxWrapper = styled.div`
`;

const ToolBoxItems = styled.div.attrs({className:'toolbox-items'})`
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
        {provided => (

          <ToolBoxItems
            {...provided.droppableProps}
            ref={provided.innerRef}
          >
            {Object.keys(items).map((item_id, index) => (
              <ToolBoxItemWrapper
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

const ToolBoxItem = styled.div`
  display:block;
  border: 1px solid #ccc;
  box-shadow: 3px 3px 3px 3px rgba(0, 0, 0, 0.2);
  padding: 10px;
  margin-right: 10px;
  background: #f9f9f9;    
  flex-basis: 100px;
  text-align: center;
`;

const ItemIcon = styled.img`
  width: 20px;
  height: 20px;
  display: block;
  margin: 0 auto;
`;

export const ToolBoxItemWrapper = ({id, item, index, onTakeItem}) => {

  return (

    <Draggable draggableId={'new-item-' + id} index={index}>
      {provided => (
        <ToolBoxItem
          ref={provided.innerRef}
          {...provided.draggableProps}
          {...provided.dragHandleProps}
          className={id.replace(/_/g, '-')}
        >
          <ItemIcon
            src={item.icon ? item.icon : 'https://png.pngtree.com/svg/20150803/320b35b99d.png'}
          />
          {item.label}
        </ToolBoxItem>
      )}
    </Draggable>
  )
};

ToolBoxItem.propTypes = {
  id: PropTypes.string,
  item: PropTypes.object,
  index: PropTypes.number,
  onTakeItem: PropTypes.func
};
