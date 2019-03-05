import React, {Component} from "react";
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
      viewOptions: {}
    };

    if (typeof (this.props.item.entity.field_hs_view) !== 'undefined' && this.props.item.entity.field_hs_view.length) {
      this.state.fieldValues = {...this.props.item.entity.field_hs_view[0]};
    }


    this.onChange = this.onChange.bind(this);
  }

  componentWillMount() {
    fetch(window.reactParagraphsApiUrl + '/entity/views')
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

        this.setState({viewOptions: jsonData});
      });
  }

  onChange(field, event) {
    const newState = {...this.state};
    newState.fieldValues[field] = field == 'show_title' || field == 'override_title' ? event.target.checked : event.target.value;
    this.setState(newState);

    Object.keys(newState.fieldValues).map(fieldName => {
      this.props.onFieldEdit('field_hs_view[0][' + fieldName + ']', newState.fieldValues[fieldName]);
    })
  }

  getDisplayOptions() {
    const options = [];
    if (this.state.fieldValues.target_id && this.state.viewOptions[this.state.fieldValues.target_id]) {
      return Object.keys(this.state.viewOptions[this.state.fieldValues.target_id].displays);
    }
    return options;
  }

  render() {
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
          <label htmlFor={this.state.fieldIds.override_title} className="option">Override Title</label>
        </div>


        <div className="form-item"
             style={{display: this.state.fieldValues.show_title && this.state.fieldValues.override_title ? 'block' : 'none'}}>
          <label htmlFor={this.state.fieldIds.overridden_title}>Overridden Title</label>
          <input
            id={this.state.fieldIds.overridden_title}
            type="textfield"
            defaultValue={this.state.fieldValues.overridden_title}
            onChange={this.onChange.bind(undefined, 'overridden_title')}
          />
        </div>


        <div className="form-item">
          <label htmlFor={this.state.fieldIds.target_id}>View</label>

          <select
            id={this.state.fieldIds.target_id}
            onChange={this.onChange.bind(undefined, 'target_id')}
            value={this.state.fieldValues.target_id}
          >
            <option value="">- Select a View -</option>
            {Object.keys(this.state.viewOptions).map(viewId => {
              return (<option key={viewId}
                              value={viewId}>{this.state.viewOptions[viewId].label}</option>)
            })}
          </select>
        </div>


        <div className="form-item"
             style={{display: this.state.fieldValues.target_id ? 'block' : 'none'}}>

          <label htmlFor={this.state.fieldIds.display_id}>Display</label>
          <select
            id={this.state.fieldIds.display_id}
            onChange={this.onChange.bind(undefined, 'display_id')}
            value={this.state.fieldValues.display_id}
          >
            <option value="_none">- Select a display -</option>
            {this.getDisplayOptions().map(displayId => {
              return (
                <option key={displayId} value={displayId}>
                  {this.state.viewOptions[this.state.fieldValues.target_id].displays[displayId]}
                </option>
              )
            })}
          </select>
        </div>


        <div className="form-item"
             style={{display: this.state.fieldValues.target_id ? 'block' : 'none'}}>
          <label htmlFor={this.state.fieldIds.arguments}>Arguments</label>
          <input
            id={this.state.fieldIds.arguments}
            type="textfield"
            onChange={this.onChange.bind(undefined, 'arguments')}
            defaultValue={this.state.fieldValues.arguments}
          />
          <div className="description">
            A comma separated list of arguments to pass to the selected view
            display.
          </div>
        </div>
      </div>
    )
  }

}
