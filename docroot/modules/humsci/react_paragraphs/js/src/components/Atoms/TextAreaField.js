import React, {Component} from "react";
import {default as UUID} from "node-uuid";

export class TextAreaField extends Component {
  constructor(props) {
    super(props);

    this.state = {
      inputId: 'field-' + UUID.v4(),
      formatId: 'field-' + UUID.v4(),
      inputFormats: [
        {value: 'basic_html', label: 'Basic HTML'},
        {value: 'full_html', label: 'Full HTML'},
        {value: 'minimal_html', label: 'Minimal HTML'}
      ]
    }
  }

  componentDidMount() {
    if (typeof Drupal !== 'undefined') {
      Drupal.behaviors.editor.attach(document, window.drupalSettings);
    }
    jQuery('#' + this.state.inputId).on('formUpdated', {parentObject: this}, function (event) {
      // TODO some magic to get the value from ckeditor.
      event.data.parentObject.props.onChange(event.data.parentObject.props.name, event);
    })
  }

  render() {
    return (
      <div className="form-item">
        <label htmlFor={this.state.inputId}>{this.props.label}</label>
        <textarea
          id={this.state.inputId}
          name={this.props.name}
          defaultValue={this.props.value}
          onChange={this.props.onChange.bind(undefined, this.props.name)}
        />

        <label htmlFor={this.state.formatId}>Text Format</label>
        <select
          id={this.state.formatId}
          data-editor-for={this.state.inputId}
          name={this.props.formatName}
          onChange={this.props.onChange.bind(undefined, this.props.formatName)}
        >
          {this.state.inputFormats.map(format => {
            return (<option key={format.value}
                            value={format.value}>{format.label}</option>)
          })}
        </select>
      </div>
    )
  }

};
