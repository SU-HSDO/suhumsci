import React, {Component} from 'react';
import {Draggable, Droppable} from "react-beautiful-dnd";
import {Item} from "./Item";

export class Row extends Component {

  constructor(props) {
    super(props);
    this.state = {containerWidth: 0};
    this.containerRef = React.createRef();
  }

  componentDidMount() {
    this.setState({containerWidth: this.containerRef.current.offsetWidth});
    window.addEventListener("resize", this.onWindowResize.bind(this));
  }

  onWindowResize() {
    this.setState({containerWidth: this.containerRef.current.offsetWidth});
  }

  render() {
    return (
      <Draggable draggableId={this.props.row.id}
                 index={this.props.index}>
        {provided => (
          <div {...provided.draggableProps} ref={provided.innerRef}>

            <div className="row">

              <div {...provided.dragHandleProps}
                   className="row-draggable-handle">
                <span className="draggable-icon">
                ::
                </span>
              </div>

              <Droppable  droppableId={this.props.row.id} type="item"
                         direction="horizontal">
                {(provided, snapshot) => (

                  <div {...provided.droppableProps} ref={provided.innerRef}
                       data-isdraggingover={snapshot.isDraggingOver.toString()}
                       className="row-items">

                    <div className="item-list-wrapper" ref={this.containerRef}>
                      {this.props.items.map((item, itemIndex) => {
                      return (
                        <Item
                          key={item.id}
                          item={item}
                          index={itemIndex}
                          rowItems={this.props.items}
                          containerWidth={this.state.containerWidth}
                          onItemResize={this.props.onItemResize}
                          onItemRemove={this.props.onItemRemove}
                          onItemEdit={this.props.onItemEdit}
                        />
                      )
                    })}

                    </div>
                  </div>
                )}
              </Droppable>
              <a href="#"
                 onClick={this.props.onRemoveRow.bind(undefined, this.props.row)}>X<span className="visually-hidden">Delete row</span></a>
            </div>
          </div>
        )}
      </Draggable>
    )
  };
}

