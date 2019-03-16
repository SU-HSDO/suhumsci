import React, {Component} from 'react';
import {Draggable} from "react-beautiful-dnd";
import Resizable from "re-resizable";
import styled from 'styled-components';
import {ResizeHandle} from "./Atoms/ResizeHandle";
import {ToggleButton} from "./Atoms/ToggleButton";
import {EntityForm} from "./Molecules/EntityForm";

const ItemIcon = styled.img`
  width: 20px;
  height: 20px;
  display: block;
  margin: 0 auto;
`;

export class Item extends Component {

  constructor(props) {
    super(props);
    this.state = {
      showForm: false,
      showActions: false
    };
    this.paragraphType = this.props.item.entity.type[0].target_id;
    this.onEditFormButtonClick = this.onEditFormButtonClick.bind(this);
  }

  /**
   * Expand or collapse the form when the user wants.
   */
  onEditFormButtonClick(event) {
    event.preventDefault();
    this.setState(prevState => ({
      ...prevState,
      showForm: !prevState.showForm
    }));
  }

  /**
   * Get a string summary of the entity field values.
   *
   * todo: add file/media summary better.
   */
  getItemSummary() {
    let summary = [];

    Object.keys(this.props.item.entity).map(fieldName => {
      if (fieldName.indexOf('field_') === 0 && this.props.item.entity[fieldName].length) {
        summary.push(this.props.item.entity[fieldName][0].value);
      }
    });
    summary = summary.filter(line => line !== undefined && line.length > 1);
    return summary.join(', ').replace(/(<([^>]+)>)/ig, "").substr(0, 100);
  }

  getTypeLabel() {
    return this.props.availableParagraphs[this.paragraphType].label;
  }

  /**
   * Render our component.
   */
  render() {
    // Do some math for our 12 column grid given the container width.
    const gridIncrement = this.props.containerWidth / 12;
    const initialWidth = gridIncrement * this.props.item.settings.width;

    let totalWidth = 0;
    this.props.rowItems.map(item => {
      totalWidth += item.settings.width;
    });

    // If there is space available in the row, allow the item to be resized.
    const maxWidth = gridIncrement * (12 - totalWidth) + initialWidth;
    const icon = this.props.availableParagraphs[this.paragraphType].icon;

    return (
      <Draggable draggableId={this.props.item.id} index={this.props.index}>
        {provided => (
          <Resizable
            className="resizeable-item"
            defaultSize={{
              width: "100%",
              height: 'auto'
            }}
            size={{
              height: 'auto',
              width: initialWidth,
            }}
            maxWidth={maxWidth}
            minWidth={gridIncrement * 2}
            grid={[gridIncrement, 1]}
            enable={{
              top: false,
              right: true,
              bottom: false,
              left: false,
              topRight: false,
              bottomRight: false,
              bottomLeft: false,
              topLeft: false
            }}
            onResizeStop={this.props.onItemResize.bind(undefined, this.props.item, initialWidth, gridIncrement)}
            handleComponent={{right: ResizeHandle}}
          >
            <div
              className="item"
              ref={provided.innerRef}
              {...provided.draggableProps}
            >

              <div className="item-contents">
                <div className="item-header" {...provided.dragHandleProps}>

                  <div className="icon-label">
                    {icon != null && <ItemIcon src={icon}/>}
                    {this.getTypeLabel()}
                  </div>

                  <div className="item-summary">
                    {this.getItemSummary()}
                  </div>

                  <div className="item-actions">
                    <button
                      onClick={this.onEditFormButtonClick}
                      className="button">{this.state.showForm ? 'Collapse' : 'Edit'}</button>

                    <ToggleButton
                      actions={[{
                        value: 'Delete',
                        onClick: this.props.onItemRemove.bind(undefined, this.props.item)
                      },
                        {
                          value: 'Duplicate',
                          onClick: function (e) {
                            e.preventDefault();
                            alert('Not working yet')
                          }
                        }
                      ]}/>
                  </div>
                </div>

                <div className="item-form"
                     style={{display: this.state.showForm ? 'block' : 'none'}}>
                  <EntityForm item={this.props.item}
                              onItemEdit={this.props.onItemEdit}/>
                </div>
              </div>

            </div>
          </Resizable>
        )}
      </Draggable>
    )
  }
}
