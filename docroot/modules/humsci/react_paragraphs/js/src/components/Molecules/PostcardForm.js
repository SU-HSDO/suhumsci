import React, {Component} from "react";
import {default as UUID} from "node-uuid";
import {TextAreaField} from "../Atoms/TextAreaField";
import {MediaField} from "../Atoms/MediaField";
import {InputField} from "../Atoms/InputField";

export class PostcardForm extends Component {

  constructor(props) {
    super(props);

    this.state = {
      fieldValues: {
        displayValue: '',
        titleValue: '',
        bodyValue: '',
        imageValue: '',
        linkUriValue: '',
        linkTitleValue: ''
      }
    };

    if (typeof (this.props.item.entity.field_hs_postcard_display) !== 'undefined' && this.props.item.entity.field_hs_postcard_display.length) {
      this.state.fieldValues.displayValue = this.props.item.entity.field_hs_postcard_display[0].value
    }

    if (typeof (this.props.item.entity.field_hs_postcard_body) !== 'undefined' && this.props.item.entity.field_hs_postcard_body.length) {
      this.state.fieldValues.bodyValue = this.props.item.entity.field_hs_postcard_body[0].value
    }

    if (typeof (this.props.item.entity.field_hs_postcard_image) !== 'undefined' && this.props.item.entity.field_hs_postcard_image.length) {
      this.state.fieldValues.imageValue = this.props.item.entity.field_hs_postcard_image[0].target_id
    }

    if (typeof (this.props.item.entity.field_hs_postcard_link) !== 'undefined' && this.props.item.entity.field_hs_postcard_link.length) {
      this.state.fieldValues.linkUriValue = this.props.item.entity.field_hs_postcard_link[0].uri
    }

    if (typeof (this.props.item.entity.field_hs_postcard_link) !== 'undefined' && this.props.item.entity.field_hs_postcard_link.length) {
      this.state.fieldValues.linkTitleValue = this.props.item.entity.field_hs_postcard_link[0].title
    }

    if (typeof (this.props.item.entity.field_hs_postcard_title) !== 'undefined' && this.props.item.entity.field_hs_postcard_title.length) {
      this.state.fieldValues.titleValue = this.props.item.entity.field_hs_postcard_title[0].value
    }

    this.displayId = 'field-' + UUID.v4();

    this.displayOptions = [
      {value: 'vertical', label: 'Vertical Card'},
      {value: 'preview', label: 'Horizontal Card'},
      {value: 'token', label: 'Vertical Linked Card'},
    ];

    this.onDisplayChange = this.onDisplayChange.bind(this);
  }

  onDisplayChange(event) {
    this.props.onFieldEdit('field_hs_postcard_display[0][value]', event.target.value);
  }

  render() {
    return (

      <div className="horizontal">
        <div className="form-item">
          <label htmlFor={this.displayId}>Display</label>
          <select id={this.displayId}
                  defaultValue={this.state.fieldValues.displayValue}
                  onChange={this.onDisplayChange}>

            {this.displayOptions.map(option =>
              <option key={option.value}
                      value={option.value}>{option.label}</option>
            )}
          </select>
        </div>

        <MediaField
          label="Image"
          value={this.state.fieldValues.imageValue}
          allowedTypes={['image']}
          name="field_hs_postcard_image[0][target_id]"
          onChange={this.props.onFieldEdit}
        />

        <InputField
          label="Card Title"
          name="field_hs_postcard_title[0][value]"
          value={this.state.fieldValues.titleValue}
          onChange={this.props.onFieldEdit}
        />

        <TextAreaField
          label="Card Body"
          name="field_hs_postcard_body[0][value]"
          formatName="field_hs_postcard_body[0][format]"
          value={this.state.fieldValues.bodyValue}
          onChange={this.props.onFieldEdit}
        />

        <fieldset className="container">
          <legend>Read More Link</legend>
          <div className="fieldset-wrapper">
            <InputField
              label="URL"
              name="field_hs_postcard_link[0][uri]"
              value={this.state.fieldValues.linkUriValue}
              onChange={this.props.onFieldEdit}
            />
            <InputField
              label="Link text"
              name="field_hs_postcard_link[0][title]"
              value={this.state.fieldValues.linkTitleValue}
              onChange={this.props.onFieldEdit}
            />
          </div>
        </fieldset>
      </div>
    )
  }
}
