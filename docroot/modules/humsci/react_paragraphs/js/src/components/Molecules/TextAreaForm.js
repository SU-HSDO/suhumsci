import React, {Component} from "react";
import styled from 'styled-components';
import {CirclePicker} from "react-color";
import {TextAreaField} from "../Atoms/Fields/TextAreaField";


const Wrapper = styled.div`
  ${({outlineColor}) => outlineColor !== '#fff' && `outline: 4px solid ${outlineColor};`}
`;

export class TextAreaForm extends Component {

  constructor(props) {
    super(props);
    this.state = {
      colors: [
        '#fff',
        '#8c1515',
        '#000',
        '#4d4f53',
        '#d5d0c0',
        '#928b81',
        '#009b76',
        '#0f6c91',
        '#006374',
        '#eaab00',
        '#e98300',
        '#53284f'
      ],
      colorValue: '#fff',
      bodyValue: '',
      colorAccess: false
    };

    if (typeof (this.props.item.entity.field_hs_text_area) !== 'undefined' && this.props.item.entity.field_hs_text_area.length) {
      this.state.bodyValue = this.props.item.entity.field_hs_text_area[0].value
    }

    if (typeof (this.props.item.entity.field_hs_text_area_bg_color) !== 'undefined' && this.props.item.entity.field_hs_text_area_bg_color.length) {
      this.state.colorValue = '#' + this.props.item.entity.field_hs_text_area_bg_color[0].color
    }

    this.onColorChange = this.onColorChange.bind(this);
  }

  componentWillMount() {
    fetch(window.reactParagraphsApiUrl + '/api/field-access/paragraph.hs_text_area.field_hs_text_area_bg_color')
      .then(response => {
        if (response.status === 200) {
          this.setState({colorAccess: true})
        }
      })
  }

  onColorChange(color) {
    this.setState({colorValue: color.hex});
    if (color.hex == '#fff') {
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

        {this.state.colorAccess &&
        <div className="form-item">
          <label>Background Color</label>
          <CirclePicker
            color={this.state.colorValue}
            colors={this.state.colors}
            onChange={this.onColorChange}
          />
        </div>
        }
      </Wrapper>
    )
  }
}
