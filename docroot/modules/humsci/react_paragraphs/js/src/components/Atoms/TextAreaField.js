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
    };

    this.onEditorChange = this.onEditorChange.bind(this);
    this.onFormatChange = this.onFormatChange.bind(this);
  }

  componentDidMount() {
    if (typeof Drupal !== 'undefined') {
      Drupal.behaviors.editor.attach(document, window.drupalSettings);
      Object.keys(Drupal.editors).map(editorId => {
        Drupal.editors[editorId].onChange(this.nv, this.onEditorChange.bind(undefined, this.nv));
      })
    }
  }

  onEditorChange(element, newValue) {
    this.props.onChange(this.props.name, newValue);
  }

  onFormatChange(event){
    this.props.onChange(this.props.formatName, event.target.value);
  }

  render() {
    return (
      <div className="form-item">
        <label htmlFor={this.state.inputId}>{this.props.label}</label>
        <textarea
          ref={elem => this.nv = elem}
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
          onChange={this.onFormatChange}
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
