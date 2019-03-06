import React, {Component} from "react";
import {default as UUID} from "node-uuid";
import Select from 'react-select';

export class WebformForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
      fieldValues: {target_id: ''},
    };

    if (this.props.item.entity.field_hs_webform && this.props.item.entity.field_hs_webform.length) {
      this.state.fieldValues = {...this.props.item.entity.field_hs_webform[0]}
    }

    this.displayOptions = {};
    this.fieldId = 'field-' + UUID.v4();
    this.myRef = React.createRef();
    this.onWebformSelect = this.onWebformSelect.bind(this);
  }

  componentWillMount() {
    fetch(window.reactParagraphsApiUrl + '/entity-list/webform')
      .then(response => response.json())
      .then(jsonData => {
        this.displayOptions = jsonData;
        if (this.state.fieldValues.target_id) {
          const formId = this.state.fieldValues.target_id;
          this.setState(prevState => ({
            ...prevState,
            selectedItem: {value: formId, label: this.displayOptions[formId]}
          }))
        }
      })
  }

  componentDidMount() {
    this.setState(prevState => ({
      ...prevState,
      inputId: this.myRef.current.select.inputRef.id
    }))
  }

  onWebformSelect(selectedItem) {
    this.setState(prevState => ({
      ...prevState,
      selectedItem: selectedItem,
      fieldValues: {
        ...prevState.fieldValues,
        target_id: selectedItem.value,
      }
    }));
    this.props.onFieldEdit('field_hs_webform[0][target_id]', selectedItem.value);
  }

  render() {
    const selectOptions = [];
    Object.keys(this.displayOptions).map(formId => {
      selectOptions.push({value: formId, label: this.displayOptions[formId]});
    });

    return (
      <div>
        <div className="form-item">
          <Select ref={this.myRef}
                  options={selectOptions}
                  value={this.state.selectedItem}
                  onChange={this.onWebformSelect}
          />
        </div>
      </div>
    )
  }
};
