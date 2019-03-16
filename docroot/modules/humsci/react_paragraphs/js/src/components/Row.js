import React, {Component} from 'react';
import {Draggable, Droppable} from "react-beautiful-dnd";
import {Item} from "./Item";
import {Handle} from "./Atoms/Handle";
import {ToggleButton} from "./Atoms/ToggleButton";

export class Row extends Component {

  state = {containerWidth: 0};
  containerRef = React.createRef();

  /**
   * When the component mounts, add an event listener so we can measure the
   * container.
   */
  componentDidMount() {
    this.setState({containerWidth: this.containerRef.current.offsetWidth});
    window.addEventListener("resize", this.onWindowResize.bind(this));
  }

  /**
   * If the window is resized, measure and save the new row container width.
   */
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
                isDropDisabled={this.props.items.length === 4}
              >
                {(provided, snapshot) => (

                  <div {...provided.droppableProps}
                       ref={provided.innerRef}
                       className="row-items-wrapper"
                       style={{width: 'calc(100% - 60px)'}}
                  >

                    <div className="item-list" ref={this.containerRef}
                         style={{background: snapshot.isDraggingOver ? 'lightblue' : '#f9f9f9'}}>
                      {this.props.items.map((item, itemIndex) => {
                        return (
                          <Item
                            key={item.id}
                            item={item}
                            index={itemIndex}
                            availableParagraphs={this.props.availableParagraphs}
                            rowItems={this.props.items}
                            containerWidth={this.state.containerWidth}
                            onItemResize={this.props.onItemResize}
                            onItemRemove={this.props.onItemRemove}
                            onItemEdit={this.props.onItemEdit}
                          />
                        )
                      })}

                      {provided.placeholder}
                    </div>
                  </div>
                )}
              </Droppable>

              <ToggleButton
                className="row-actions"
                actions={[{
                  onClick: this.props.onRemoveRow.bind(undefined, this.props.row),
                  value: 'Delete Row'
                }]}
              />
            </div>
          </div>
        )}
      </Draggable>
    )
  };
}

