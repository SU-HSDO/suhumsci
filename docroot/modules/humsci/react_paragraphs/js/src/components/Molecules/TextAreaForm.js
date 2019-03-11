import React, {Component} from "react";
import styled from 'styled-components';
import {CirclePicker} from "react-color";
import {TextAreaField} from "../Atoms/Fields/TextAreaField";


const Wrapper = styled.div`
  ${({outlineColor}) => outlineColor !== '#ffffff' && `outline: 4px solid ${outlineColor};`}
`;

export class TextAreaForm extends Component {

  constructor(props) {
    super(props);
    this.state = {
      colors: [
        '#ffffff',
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
      colorValue: '#ffffff',
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
    if (color.hex == '#ffffff') {
      this.props.onFieldEdit('field_hs_text_area_bg_color[0][color]', '');
      return;
    }
    this.props.onFieldEdit('field_hs_text_area_bg_color[0][color]', color.hex.replace('#', ''))
  }


  render() {
    return (
      <Wrapper className="text-area"
           outlineColor={this.state.colorValue}>
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
      </Wrapper>
    )
  }
}
