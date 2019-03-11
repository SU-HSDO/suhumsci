import React, {Component} from 'react';
import {DragDropContext, Droppable} from 'react-beautiful-dnd';
import {confirmAlert} from 'react-confirm-alert';
import {default as UUID} from "node-uuid";
import {ToolBox} from "./Molecules/ToolBox";
import {Row} from "./Row";
import '../react_paragraphs.confirm.scss';

export class ParagraphGroups extends Component {

  /**
   * Instantiate the component object.
   *
   * @param props
   */
  constructor(props) {
    super(props);

    const existingItems = {};

    this.props.existing_items.map(item => {
      item.id = 'item-' + item.target_id;
      existingItems[item.id] = item;
    });

    this.state = {
      loadedItems: 0,
      isLoading: !!existingItems.length,
      items: existingItems,
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

  /**
   * Load all the paragraph entities before mounting the component.
   */
  componentWillMount() {
    if (this.props.entityId == null) {
      return;
    }
    Object.keys(this.state.items).map(itemId => {
      this.loadParagraphEntity(itemId);
    });
  }

  /**
   * Load a single paragraph entity from the rest API.
   *
   * @param itemId
   */
  loadParagraphEntity(itemId) {
    const item = this.state.items[itemId];

    // This API is provided by Drupal's rest module in core.
    fetch(reactParagraphsApiUrl + '/entity/paragraph/' + item.target_id)
      .then(response => response.json())
      .then(jsonData => {

        // If the API fails to get the paragraph, we get a message. Throw that
        // message as an error.
        if (typeof (jsonData.message) !== 'undefined') {
          throw jsonData.message;
        }

        // Build the new item data object and add it to the existing items.
        var rows = {...this.state.rows};
        var items = {...this.state.items};

        items[item.id].entity = jsonData;

        const rowNumber = item.settings.row;

        if (typeof (rows['row-' + rowNumber]) === 'undefined') {
          rows['row-' + rowNumber] = {
            id: 'row-' + rowNumber,
            items: []
          };
        }
        rows['row-' + rowNumber].items[item.settings.index] = item.id;

        var rowOrder = this.state.rowOrder;
        rowOrder[rowNumber] = 'row-' + rowNumber;

        // Set the new state with the new paragraph entity.
        this.setState(prevState => ({
          ...prevState,
          isLoading: prevState + 1 === items.length,
          loadedItems: prevState.loadedItems + 1,
          items: items,
          rows: rows,
          rowOrder: rowOrder,
          rowCount: Object.keys(rows).length
        }))
      })
      .catch(error => {
        console.log('An error has occured: ' + error)
      });
  }

  /**
   * When the component updates at any point, inject the data into a hidden
   * field for consumption by the field widget during node save.
   */
  componentDidUpdate(prevProps, prevState, snapshot) {
    const formItemsField = document.getElementsByName(this.props.fieldName + '[value]');
    if (formItemsField.length) {
      const returnValue = {
        items: this.state.items,
        rows: this.state.rows,
        rowOrder: this.state.rowOrder,
      };
      formItemsField[0].value = encodeURI(JSON.stringify(returnValue));
      return;
    }
  }

  /**
   * When an item is resized, save it as part of the state.
   */
  onItemResize(item, initialWidth, incrementWidth, event, direction, element, changes) {
    const newWidth = initialWidth + changes.width;
    item.settings.width = Math.round(newWidth / incrementWidth);

    this.setState(prevState => ({
      ...prevState,
      items: {
        ...prevState.items,
        [item.id]: item,
      }
    }))
  }

  /**
   * After a user has dropped an tem into its desired location, reorder some
   * data and set the state with the new order.
   */
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
    const newItems = {...this.state.items};

    // Items in the same row were reorderd.
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

    // An item was moved from one row to the next.
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
    endItems.map((itemId, itemIndex) => {
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

  /**
   * When the user clicks the button to add a new row, add it to the state.
   */
  onAddRowClick(event) {
    event.preventDefault();

    const newState = {...this.state};
    const newRowId = 'row-' + parseInt(newState.rowCount);

    newState.rows[newRowId] = {id: newRowId, items: []};
    newState.rowOrder.push(newRowId);
    newState.rowCount++;
    this.setState(newState);
  }

  /**
   * When a user clicks to remove a row, alert them to confirm, then delete
   * the row.
   */
  onRemoveRow(row, event) {
    event.preventDefault();

    confirmAlert({
      customUI: ({onClose}) => {
        return (
          <div className='alert-dialog'>
            <p>Are you sure you want to delete this row?</p>
            <button className="button" onClick={onClose}>Cancel</button>
            <button className="button button--primary" onClick={(e) => {
              e.preventDefault();

              let newState = {...this.state};
              row.items.map(itemId => {
                delete newState.items[itemId];
              });
              newState.rowOrder.splice(newState.rowOrder.indexOf(row.id), 1);
              delete newState.rows[row.id];
              this.setState(newState);

              onClose()
            }}
            >
              Delete
            </button>
          </div>
        )
      }
    });
  }

  /**
   * When a user clicks to add a new item into the mix, add it to the last row,
   * or create a new row and add it to that. Then save to the state.
   */
  onTakeToolItem(newItem, event) {
    event.preventDefault();

    const newState = {...this.state};

    // No row exists. Add a row to hold the new item.
    if (!this.state.rowOrder.length) {
      const newRowId = 'row-' + parseInt(newState.rowCount);

      newState.rows[newRowId] = {id: newRowId, items: []};
      newState.rowOrder.push(newRowId);
      newState.rowCount++;
    }

    let newUuid = UUID.v4();
    // Ensure we always have a unique item ID. This uuid is only for the form.
    // It is not the same UUID that Drupal will use.
    while (typeof (newState.items['item-' + newUuid]) !== 'undefined') {
      newUuid = UUID.v4();
    }

    let lastRowId = newState.rowOrder.slice(-1);

    // Too many item in the last row. Add a new row.
    if (newState.rows[lastRowId].items.length === 4) {
      const newRowId = 'row-' + parseInt(newState.rowCount);

      newState.rows[newRowId] = {id: newRowId, items: []};
      newState.rowOrder.push(newRowId);
      newState.rowCount++;
      lastRowId = newState.rowOrder.slice(-1);
    }

    // Resize all the items in the row. This is the easiest way to handle it.
    const itemWidth = 12 / (newState.rows[lastRowId].items.length + 1);
    newState.rows[lastRowId].items.map(itemId => newState.items[itemId].settings.width = isFinite(itemWidth) ? itemWidth : 12);

    newState.items['item-' + newUuid] = {
      entity: {
        type: [{target_id: newItem.id}]
      },
      id: 'item-' + newUuid,
      settings: {
        row: 0,
        index: 0,
        width: isFinite(itemWidth) ? itemWidth : 12,
      },
      target_id: null,
    };

    newState.rows[lastRowId].items.push('item-' + newUuid);
    this.setState(newState);
  }

  /**
   * When a user clicks to delete a single item out of the row, remove it, and
   * save the state.
   */
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

  /**
   * An item has been edited in some way, save it in the state.
   */
  onItemEdit(item) {
    const newState = {...this.state};
    newState.items[item.id] = item;
    this.setState(newState);
  }

  /**
   * Render our component.
   */
  render() {
    if (this.state.isLoading) {
      // All paragraphs haven't been loaded.
      return (
        <div className="react-loader"><span
          className="visually-hidden">Loading</span></div>
      )
    }

    return (
      <div className="react-paragraphs-widget">
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

                {provided.placeholder}
              </div>
            )}
          </Droppable>
          <button onClick={this.onAddRowClick} className="button">Add Row
          </button>
          <ToolBox items={this.props.available_items}
                   onTakeItem={this.onTakeToolItem}/>
        </DragDropContext>
      </div>
    );
  }

}

