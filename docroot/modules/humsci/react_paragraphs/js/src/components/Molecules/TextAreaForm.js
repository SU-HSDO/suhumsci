import React, {Component} from "react";
import {CirclePicker} from "react-color";
import {TextAreaField} from "../Atoms/TextAreaField";

export class TextAreaForm extends Component {

  constructor(props) {
    super(props);
    this.state = {
      colors: [
        '#8C1515',
        '#000000',
        '#4D4F53',
        '#D5D0C0',
        '#928B81',
        '#009B76',
        '#0F6C91',
        '#006374',
        '#EAAB00',
        '#E98300',
        '#53284F'
      ],
      colorValue: '',
      bodyValue: ''
    };

    if (typeof (this.props.item.entity.field_hs_text_area) !== 'undefined' && this.props.item.entity.field_hs_text_area.length) {
      this.state.bodyValue = this.props.item.entity.field_hs_text_area[0].value
    }

    if (typeof (this.props.item.entity.field_hs_text_area_bg_color) !== 'undefined' && this.props.item.entity.field_hs_text_area_bg_color.length) {
      this.state.colorValue = '#' + this.props.item.entity.field_hs_text_area_bg_color[0].color
    }

    this.onColorChange = this.onColorChange.bind(this);
  }

  onColorChange(color) {
    this.setState({colorValue: color.hex});
    this.props.onFieldEdit('field_hs_text_area_bg_color[0][color]', color.hex.replace('#', ''))
  }


  render() {
    return (
      <div className="text-area"
           style={{outline: '4px solid ' + this.state.colorValue}}>
        <TextAreaField
          label="Text Area"
          name="field_hs_text_area[0][value]"
          formatName="field_hs_text_area[0][format]"
          value={this.state.bodyValue}
          onChange={this.props.onFieldEdit}
        />

        <div className="form-item">
          <label>Background Color</label>
          <CirclePicker
            color={this.state.colorValue}
            colors={this.state.colors}
            onChange={this.onColorChange}
          />
        </div>
      </div>
    )
  }
}
