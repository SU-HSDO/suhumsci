import React, {Component} from "react";
import {default as UUID} from "node-uuid";

export class InputField extends Component {

  constructor(props) {
    super(props);
    this.inputId = 'field-' + UUID.v4();

    this.onInputChange = this.onInputChange.bind(this);
  }

  onInputChange(event) {
    this.props.onChange(this.props.name, event.target.value);
  }

  render() {
    return (
      <div className="form-item">
        <label htmlFor={this.inputId}>{this.props.label}</label>
        <input
          type={this.props.type ? this.props.type : 'textfield'}
          id={this.props.inputId}
          name={this.props.name}
          defaultValue={this.props.value}
          onChange={this.onInputChange}
        />
      </div>
    )
  }
}
