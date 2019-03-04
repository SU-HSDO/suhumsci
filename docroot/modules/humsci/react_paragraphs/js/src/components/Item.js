import React, {Component} from 'react';
import {Draggable} from "react-beautiful-dnd";
import Resizable from "re-resizable";
import {PostcardForm} from "./Molecules/PostcardForm";
import {TextAreaForm} from "./Molecules/TextAreaForm";
import {AccordionForm} from "./Molecules/AccordionForm";
import {HeroImageForm} from "./Molecules/HeroImageForm";
import {ViewForm} from "./Molecules/ViewForm";
import {WebformForm} from "./Molecules/WebformForm";
import {Handle} from "./Atoms/Handle";

export class Item extends Component {

  constructor(props) {
    super(props);
    this.state = {
      showForm: false,
      showActions: false
    };
    this.onEditFormButtonClick = this.onEditFormButtonClick.bind(this);
    this.onViewActionsClick = this.onViewActionsClick.bind(this);
  }

  onEditFormButtonClick(event) {
    event.preventDefault();
    this.setState(prevState => ({
      ...prevState,
      showForm: !prevState.showForm
    }));
  }

  onViewActionsClick(action, event) {
    event.preventDefault();
    this.setState(prevState => ({
      ...prevState,
      showActions: action === 'leave' ? false : !prevState.showActions
    }))
  }

  getItemSummary() {
    let summary = [];

    Object.keys(this.props.item.entity).map(fieldName => {
      if (fieldName.indexOf('field_') === 0 && this.props.item.entity[fieldName].length) {
        summary.push(this.props.item.entity[fieldName][0].value);
      }
    });

    if (summary.length === 0) {
      return this.props.item.entity.type[0].target_id;
    }
    return summary.filter(line => line !== undefined && line.length > 1).join(', ').replace(/(<([^>]+)>)/ig, "").substr(0, 100);
  }

  render() {
    const gridIncrement = this.props.containerWidth / 12;
    const initialWidth = gridIncrement * this.props.item.settings.width;

    let totalWidth = 0;
    this.props.rowItems.map(item => {
      totalWidth += item.settings.width;
    });

    const maxWidth = gridIncrement * (12 - totalWidth) + initialWidth;

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
          >
            <div
              className="item"
              ref={provided.innerRef}
              {...provided.draggableProps}
            >
              <Handle {...provided.dragHandleProps}/>

              <div className="item-contents">
                <div className="item-header">
                  <div className="item-summary">
                    {this.getItemSummary()}
                  </div>
                  <div className="item-actions"
                       onMouseLeave={this.onViewActionsClick.bind(undefined, 'leave')}>
                    <button
                      onClick={this.onEditFormButtonClick}
                      className="button">{this.state.showForm ? 'Collapse' : 'Edit'}</button>

                    <button className="actions-toggle"
                            onClick={this.onViewActionsClick.bind(undefined, 'toggle')}>
                      <span className="visually-hidden">Toggle Actions</span>
                    </button>

                    <ul className="actions-list"
                        style={{display: this.state.showActions ? 'block' : 'none'}}>
                      <li>
                        <a className="delete-action" href="#"
                           onClick={this.props.onItemRemove.bind(undefined, this.props.item)}>Delete</a>
                      </li>
                    </ul>
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


export class EntityForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showForm: false,
    };
    this.onFieldEdit = this.onFieldEdit.bind(this);
  }

  onFieldEdit(item, fieldName, newValue) {
    if (fieldName) {
      let fieldPath = fieldName.split('[');
      fieldPath = fieldPath.map(path => path.replace(']', ''));
      item.entity = this.setFieldValue(item.entity, fieldPath, newValue);
      this.props.onItemEdit(item);
    }
  }

  setFieldValue(entity, path, value) {
    const [index] = path.splice(0, 1);
    if (path.length) {
      if (typeof (entity[index]) === 'undefined') {
        entity[index] = {};
      }
      entity[index] = this.setFieldValue(entity[index], path, value);
    }
    else {
      entity[index] = value;
    }
    return entity;
  }


  render() {
    switch (this.props.item.entity.type[0].target_id) {
      case 'hs_postcard':
        return (<PostcardForm item={this.props.item}
                              onFieldEdit={this.onFieldEdit.bind(undefined, this.props.item)}/>);
      case 'hs_text_area':
        return (<TextAreaForm item={this.props.item}
                              onFieldEdit={this.onFieldEdit.bind(undefined, this.props.item)}/>);
      case 'hs_accordion':
        return (<AccordionForm item={this.props.item}
                               onFieldEdit={this.onFieldEdit.bind(undefined, this.props.item)}/>);
      case 'hs_hero_image':
        return (<HeroImageForm item={this.props.item}
                               onFieldEdit={this.onFieldEdit.bind(undefined, this.props.item)}/>);
      case 'hs_view':
        return (<ViewForm item={this.props.item}
                          onFieldEdit={this.onFieldEdit.bind(undefined, this.props.item)}/>);
      case 'hs_webform':
        return (<WebformForm item={this.props.item}
                             onFieldEdit={this.onFieldEdit.bind(undefined, this.props.item)}/>);
      default:
        return (<div/>)
    }
  }
}
