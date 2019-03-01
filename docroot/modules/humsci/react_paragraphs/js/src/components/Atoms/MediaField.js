import React, {Component} from "react";
import {default as UUID} from "node-uuid";


export class MediaField extends Component {
  constructor(props) {
    super(props);
    this.state = {inputId: 'field-' + UUID.v4()};
    this.onSelectClick = this.onSelectClick.bind(this);
  }

  onSelectClick(event) {
    event.preventDefault();
  }

  render() {
    return (
      <div className="form-item">
        <label htmlFor={this.state.inputId}>{this.props.label}</label>
        Selected Media: {this.props.value}<br/>

        <button className="button" onClick={this.onSelectClick}>Select
          Image</button>
      </div>
    )
  }
};
