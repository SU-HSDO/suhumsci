import React, {Component} from 'react';
import {DragDropContext, Droppable} from 'react-beautiful-dnd';
import {default as UUID} from "node-uuid";
import {ToolBox} from "./Molecules/ToolBox";
import {Row} from "./Row";

export class ParagraphGroups extends Component {

  constructor(props) {
    super(props);
    this.state = {
      items: {},
      rows: {},
      rowOrder: [],
      rowCount: 0
    };

    this.onDragEnd = this.onDragEnd.bind(this);
    this.onAddRowClick = this.onAddRowClick.bind(this);
    this.onRemoveRow = this.onRemoveRow.bind(this);
    this.onTakeToolItem = this.onTakeToolItem.bind(this);
    this.onItemRemove = this.onItemRemove.bind(this);
    this.onItemResize = this.onItemResize.bind(this);
    this.onItemEdit = this.onItemEdit.bind(this);
  }

  componentDidMount() {
    console.log(this.props);
    if (this.props.entityId == null) {
      return;
    }
    fetch('http://docroot.suhumsci.loc/node/' + this.props.entityId + '?_format=json')
      .then(response => response.json())
      .then(jsonData => {
        console.log(jsonData);
        jsonData[this.props.fieldName].map((item, delta) => {
          item.settings = {
            row: delta,
            index: 0,
            width: 12
          };
          item.id = 'item-' + item.target_uuid;

          var items = {...this.state.items};
          items[item.id] = item;
          this.setState({items});

          fetch('http://docroot.suhumsci.loc/entity/paragraph/' + item.target_id)
            .then(response => response.json())
            .then(jsonData => {

              var rows = {...this.state.rows};
              var items = {...this.state.items};
              items[item.id].entity = jsonData;

              if (typeof (rows['row-' + items[item.id].settings.row]) === 'undefined') {
                rows['row-' + items[item.id].settings.row] = {
                  id: 'row-' + items[item.id].settings.row,
                  items: []
                };
              }
              rows['row-' + items[item.id].settings.row].items.push(item.id);
              var rowOrder = this.state.rowOrder;

              rowOrder[items[item.id].settings.row] = 'row-' + items[item.id].settings.row;
              let newRowCount = this.state.rowCount;
              newRowCount++;
              this.setState(prevState => ({
                ...prevState,
                rows: rows,
                rowOrder: rowOrder,
                rowCount: newRowCount
              }))
            })
        });
      });
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    const formItemsField = document.getElementsByName(this.props.fieldName + '[value]');
    // const formItemsField =
    // document.getElementsByName('field_react_paragraphs[value]');
    if (formItemsField.length) {
      const returnValue = {
        items: this.state.items,
        rows: this.state.rows,
        rowOrder: this.state.rowOrder,
      };
      formItemsField[0].value = encodeURI(JSON.stringify(returnValue));
      return;
    }

    alert("Something isn't working correctly");
  }

  onItemResize(item, containerWidth, event, direction, element, changes) {
    const incrementWidth = containerWidth / 12;
    item.settings.width = Math.round((item.settings.width * incrementWidth + changes.width) / containerWidth * 12);
    this.setState(prevState => ({
      ...prevState,
      items: {
        ...prevState.items,
        [item.id]: item,
      }
    }))
  }

  onDragEnd(result) {
    const {destination, source, draggableId, type} = result;
    if (!destination || (destination.droppableId === source.droppableId && destination.index === source.index)) {
      return;
    }

    // Rows were reordered.
    if (type === 'row') {
      const newRowOrder = Array.from(this.state.rowOrder);
      newRowOrder.splice(source.index, 1);
      newRowOrder.splice(destination.index, 0, draggableId);
      const newState = {
        ...this.state,
        rowOrder: newRowOrder
      };
      this.setState(newState);
      return;
    }
    const start = this.state.rows[source.droppableId];
    const end = this.state.rows[destination.droppableId];

    if (start === end) {
      const newItemIds = Array.from(start.items);
      newItemIds.splice(source.index, 1);
      newItemIds.splice(destination.index, 0, draggableId);

      const newRow = {
        ...start,
        items: newItemIds,
      };

      const newState = {
        ...this.state,
        rows: {
          ...this.state.rows,
          [newRow.id]: newRow,
        }
      };

      this.setState(newState);
      return;
    }

    const startItems = Array.from(start.items);
    startItems.splice(source.index, 1);

    const newStart = {
      ...start,
      items: startItems,
    };

    const endItems = Array.from(end.items);
    endItems.splice(destination.index, 0, draggableId);

    const newEnd = {
      ...end,
      items: endItems,
    };

    // When a new item is added, shrink any items down to allow the new item in.
    const newItems = {...this.state.items};
    endItems.map(itemId => {
      const equalWidth = 12 / endItems.length;
      if (newItems[itemId].settings.width > equalWidth) {
        newItems[itemId].settings.width = equalWidth;
      }
    });

    const newState = {
      ...this.state,
      items: newItems,
      rows: {
        ...this.state.rows,
        [newStart.id]: newStart,
        [newEnd.id]: newEnd,
      }
    };

    this.setState(newState);
  }

  onAddRowClick(event) {
    event.preventDefault();

    const newState = {...this.state};
    newState.rowCount += 1;
    const newRowId = 'row-' + parseInt(newState.rowCount);

    newState.rows[newRowId] = {id: newRowId, items: []};
    newState.rowOrder.push(newRowId);
    this.setState(newState);
    return newState;
  }

  onRemoveRow(row, event) {
    event.preventDefault;
    let newState = {...this.state};
    row.items.map(itemId => {
      delete newState.items[itemId];
    });
    newState.rowOrder.splice(newState.rowOrder.indexOf(row.id), 1);
    delete newState.rows[row.id];
    this.setState(newState);
  }

  onTakeToolItem(newItem, event) {
    event.preventDefault();
    if (!this.state.rowOrder.length) {
      this.onAddRowClick(event);
    }

    let newState = {...this.state};

    let newUuid = UUID.v4();
    while (typeof (newState.items['item-' + newUuid]) !== 'undefined') {
      newUuid = UUID.v4();
    }

    const lastRowId = newState.rowOrder.slice(-1);
    const itemWidth = 12 / newState.rows[lastRowId].items.length;

    newState.items['item-' + newUuid] = {
      entity: {
        type: [{target_id: newItem.id}]
      },
      id: 'item-' + newUuid,
      settings: {
        row: 0,
        index: 0,
        width: isFinite(itemWidth) ? isFinite : 12,
      },
      target_id: null,
      target_uuid: newUuid,
    };

    newState.rows[lastRowId].items.push('item-' + newUuid);
    this.setState(newState);
  }

  onItemRemove(item, event) {
    event.preventDefault();

    const newState = {...this.state};
    delete newState.items[item.id];

    Object.keys(newState.rows).map(rowId => {
      const row = newState.rows[rowId];
      const indexOfItem = row.items.indexOf(item.id);
      if (indexOfItem !== -1) {
        row.items.splice(indexOfItem, 1);
        newState.rows[rowId] = row;
      }
    });
    this.setState(newState);

  }

  onItemEdit(item) {
    const newState = {...this.state};
    newState[item.id] = item;
    this.setState(newState);
  }

  render() {
    return (
      <DragDropContext onDragEnd={this.onDragEnd}>
        <Droppable droppableId="rows" type="row">
          {provided => (
            <div
              ref={provided.innerRef}
              {...provided.droppableProps}
            >

              {this.state.rowOrder.map((rowId, rowIndex) => {
                const row = this.state.rows[rowId];
                const rowItems = row.items.map(itemId => this.state.items[itemId]);
                return (
                  <Row
                    key={row.id}
                    items={rowItems}
                    row={row}
                    index={rowIndex}
                    onItemResize={this.onItemResize}
                    onItemRemove={this.onItemRemove}
                    onRemoveRow={this.onRemoveRow}
                    onItemEdit={this.onItemEdit}
                  />
                )
              })}
            </div>
          )}
        </Droppable>
        <button onClick={this.onAddRowClick} className="button">Add Row</button>
        <ToolBox items={this.props.available_items}
                 onTakeItem={this.onTakeToolItem}/>
      </DragDropContext>
    );
  }

}

