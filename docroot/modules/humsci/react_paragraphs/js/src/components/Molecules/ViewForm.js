import React, {Component} from "react";
import Select from 'react-select';
import {default as UUID} from "node-uuid";
import {ErrorMessages} from "../Atoms/ErrorMessages";

export class ViewForm extends Component {

  constructor(props) {
    super(props);

    this.state = {
      fieldValues: {
        target_id: '',
        display_id: '',
        arguments: '',
        show_title: false,
        override_title: false,
        overridden_title: '',
      },
      fieldIds: {
        target_id: 'field-' + UUID.v4(),
        display_id: 'field-' + UUID.v4(),
        arguments: 'field-' + UUID.v4(),
        show_title: 'field-' + UUID.v4(),
        override_title: 'field-' + UUID.v4(),
        overridden_title: 'field-' + UUID.v4()
      },
      views: [],
      displays: {}
    };

    if (typeof (this.props.item.entity.field_hs_view) !== 'undefined' && this.props.item.entity.field_hs_view.length) {
      this.state.fieldValues = {...this.props.item.entity.field_hs_view[0]};
    }

    this.onChange = this.onChange.bind(this);
    this.onSelect = this.onSelect.bind(this);
  }

  static validateFields(entity) {
    if (entity.field_hs_view && (typeof entity.field_hs_view[0].display_id === 'undefined' || !entity.field_hs_view[0].display_id)) {
      return {field: 'field_hs_view', message: 'Display ID is required'};
    }
    return null
  }

  componentWillMount() {
    fetch(window.reactParagraphsApiUrl + '/entity-list/view-displays')
      .then(response => response.json())
      .then(jsonData => {
        this.setState(prevState => ({
          ...prevState,
          views: jsonData.views,
          displays: jsonData.display,
        }));
      });
  }

  onSelect(fieldName, selectedItem) {
    const newState = {...this.state};
    newState.fieldValues[fieldName] = selectedItem.value;
    this.props.onFieldEdit('field_hs_view[0][' + fieldName + ']', selectedItem.value);
    this.setState(newState)
  }

  onChange(field, event) {
    const newState = {...this.state};
    newState.fieldValues[field] = field == 'show_title' || field == 'override_title' ? event.target.checked : event.target.value;
    this.setState(newState);

    Object.keys(newState.fieldValues).map(fieldName => {
      this.props.onFieldEdit('field_hs_view[0][' + fieldName + ']', newState.fieldValues[fieldName]);
    })
  }

  render() {
    const targetValue = this.state.views.find(item => item.value === this.state.fieldValues.target_id);
    let displayValue = null;
    if (targetValue) {
      displayValue = this.state.displays[targetValue.value].find(item => item.value === this.state.fieldValues.display_id);
    }

    const hasErrors = this.props.errors && this.props.errors.length;
    return (

      <div>
        {hasErrors && <ErrorMessages errors={this.props.errors}/>}

        <div className="form-item">
          <input
            id={this.state.fieldIds.show_title}
            type="checkbox"
            onChange={this.onChange.bind(undefined, 'show_title')}
            defaultChecked={this.state.fieldValues.show_title}
          />
          <label htmlFor={this.state.fieldIds.show_title} className="option">Display
            View Title</label>
        </div>


        <div className="form-item"
             style={{display: this.state.fieldValues.show_title ? 'block' : 'none'}}>
          <input
            id={this.state.fieldIds.override_title}
            type="checkbox"
            onChange={this.onChange.bind(undefined, 'override_title')}
            defaultChecked={this.state.fieldValues.override_title}
          />
          <label htmlFor={this.state.fieldIds.override_title}
                 className="option">Override Title</label>
        </div>


        <div className="form-item"
             style={{display: this.state.fieldValues.show_title && this.state.fieldValues.override_title ? 'block' : 'none'}}>
          <label htmlFor={this.state.fieldIds.overridden_title}>Overridden
            Title</label>
          <input
            id={this.state.fieldIds.overridden_title}
            type="textfield"
            defaultValue={this.state.fieldValues.overridden_title}
            onChange={this.onChange.bind(undefined, 'overridden_title')}
          />
        </div>

        <Select
          options={this.state.views}
          onChange={this.onSelect.bind(undefined, 'target_id')}
          value={targetValue}
        />

        <Select
          isDisabled={!this.state.fieldValues.target_id.length}
          options={this.state.displays[this.state.fieldValues.target_id]}
          onChange={this.onSelect.bind(undefined, 'display_id')}
          value={displayValue}
        />

      </div>
    )
  }

}
