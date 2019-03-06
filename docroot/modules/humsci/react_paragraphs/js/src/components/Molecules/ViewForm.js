import React, {Component} from "react";
import Select from 'react-select';
import {default as UUID} from "node-uuid";

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

  componentWillMount() {
    fetch(window.reactParagraphsApiUrl + '/entity-list/view-displays')
      .then(response => response.json())
      .then(jsonData => {
        delete jsonData.archive;
        delete jsonData.block_content;
        delete jsonData.content;
        delete jsonData.content_recent;
        delete jsonData.files;
        delete jsonData.frontpage;
        delete jsonData.glossary;
        delete jsonData.media;
        delete jsonData.hs_search;
        delete jsonData.hs_manage_content;
        delete jsonData.media_entity_browser;
        delete jsonData.redirect;
        delete jsonData.redirect_404;
        delete jsonData.taxonomy_term;
        delete jsonData.user_admin_people;
        delete jsonData.who_s_new;
        delete jsonData.who_s_online;
        delete jsonData.watchdog;

        const viewOptions = [];
        const displayOptions = {};

        Object.keys(jsonData).map(viewId => {
          viewOptions.push({value: viewId, label: jsonData[viewId].label});
          displayOptions[viewId] = [];

          Object.keys(jsonData[viewId].displays).map(displayId => {
            const display = jsonData[viewId].displays[displayId];
            if (display.display_plugin !== 'block') {
              return;

            }

            displayOptions[viewId].push({
              value: displayId,
              label: display.display_title
            });
          })
        });

        this.setState(prevState => ({
          ...prevState,
          views: viewOptions,
          displays: displayOptions,
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

    console.log(displayValue);

    return (
      <div>
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
