import React, {Component} from "react";
import Select from 'react-select';
import {default as UUID} from "node-uuid";
import {TextAreaField} from "../Atoms/Fields/TextAreaField";
import {MediaField} from "../Atoms/Fields/MediaField";
import {InputField} from "../Atoms/Fields/InputField";
import {LinkField} from "../Atoms/Fields/LinkField";

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
      },
      selectedDisplay: null,
    };

    this.displayOptions = [
      {value: 'default', label: 'Vertical Card'},
      {value: 'preview', label: 'Horizontal Card'},
      {value: 'token', label: 'Vertical Linked Card'},
    ];

    if (typeof (this.props.item.entity.field_hs_postcard_display) !== 'undefined' && this.props.item.entity.field_hs_postcard_display.length) {
      this.state.fieldValues.displayValue = this.props.item.entity.field_hs_postcard_display[0].value;

      this.displayOptions.map(item => {
        if (item.value == this.state.fieldValues.displayValue) {
          this.state.selectedDisplay = item;
        }
      });
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

    this.onDisplayChange = this.onDisplayChange.bind(this);
  }

  onDisplayChange(selectedItem) {
    this.props.onFieldEdit('field_hs_postcard_display[0][value]', selectedItem.value);
    this.setState(prevState => ({
      ...prevState,
      selectedDisplay: selectedItem,
    }))
  }

  render() {

    return (
      <div className="horizontal">

        <Select
          options={this.displayOptions}
          onChange={this.onDisplayChange}
          value={this.state.selectedDisplay}
        />

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

        <LinkField
          legend="Read More Link"
          titleName="field_hs_postcard_link[0][title]"
          titleValue={this.state.fieldValues.linkTitleValue}
          uriName="field_hs_postcard_link[0][uri]"
          uriValue={this.state.fieldValues.linkUriValue}
          onChange={this.props.onFieldEdit}
        />
      </div>
    )
  }
}
