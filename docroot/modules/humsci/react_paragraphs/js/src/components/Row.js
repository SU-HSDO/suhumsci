import React, {Component} from 'react';
import {Draggable, Droppable} from "react-beautiful-dnd";
import {Item} from "./Item";
import {Handle} from "./Atoms/Handle";

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
              <Handle
                {...provided.dragHandleProps}
                style={{width: '20px'}}
              />

              <Droppable
                droppableId={this.props.row.id} type="item"
                direction="horizontal"
              >
                {(provided, snapshot) => (

                  <div {...provided.droppableProps}
                       ref={provided.innerRef}
                       className="row-items-wrapper"
                       style={{width: 'calc(100% - 30px)'}}
                  >

                    <div className="item-list" ref={this.containerRef}
                         style={{background: snapshot.isDraggingOver ? 'lightblue' : 'white'}}>
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

              <div className="remove-row-button" style={{width: '10px'}}>
                <a href="#"
                   onClick={this.props.onRemoveRow.bind(undefined, this.props.row)}>X<span
                  className="visually-hidden">Delete row</span></a>
              </div>
            </div>
          </div>
        )}
      </Draggable>
    )
  };
}

