import React, {Component} from 'react';
import {PostcardForm} from "./PostcardForm";
import {TextAreaForm} from "./TextAreaForm";
import {AccordionForm} from "./AccordionForm";
import {HeroImageForm} from "./HeroImageForm";
import {ViewForm} from "./ViewForm";
import {WebformForm} from "./WebformForm";

export class EntityForm extends Component {

  constructor(props) {
    super(props);
    this.state = {
      showForm: false,
    };
    this.onFieldEdit = this.onFieldEdit.bind(this);
  }

  /**
   * Field change listener to set the field value on the entity.
   */
  onFieldEdit(item, fieldName, newValue) {
    if (fieldName) {
      let fieldPath = fieldName.split('[');
      fieldPath = fieldPath.map(path => path.replace(']', ''));
      item.entity = this.setFieldValue(item.entity, fieldPath, newValue);
      this.props.onItemEdit(item);
    }
  }

  /**
   * Takes a field form name and split sets the nested value as needed.
   */
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

  /**
   * Render the individual forms.
   *
   * Todo: make this dynamic.
   */
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
