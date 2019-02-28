import {Select} from "semantic-ui-react";
import React from "react";
import {TextAreaField} from "../Atoms/TextAreaField";
import {MediaField} from "../Atoms/MediaField";
import {InputField} from "../Atoms/InputField";

export const PostcardForm = ({item, onFieldEdit}) => {

  let displayValue = '';
  if (typeof (item.entity.field_hs_postcard_display) !== 'undefined' && item.entity.field_hs_postcard_display.length) {
    displayValue = item.entity.field_hs_postcard_display[0].value
  }

  let bodyValue = '';
  if (typeof (item.entity.field_hs_postcard_body) !== 'undefined' && item.entity.field_hs_postcard_body.length) {
    bodyValue = item.entity.field_hs_postcard_body[0].value
  }

  let imageValue = '';
  if (typeof (item.entity.field_hs_postcard_image) !== 'undefined' && item.entity.field_hs_postcard_image.length) {
    imageValue = item.entity.field_hs_postcard_image[0].value
  }

  let linkUriValue = '';
  if (typeof (item.entity.field_hs_postcard_link) !== 'undefined' && item.entity.field_hs_postcard_link.length) {
    linkUriValue = item.entity.field_hs_postcard_link[0].uri
  }

  let linkTitleValue = '';
  if (typeof (item.entity.field_hs_postcard_link) !== 'undefined' && item.entity.field_hs_postcard_link.length) {
    linkTitleValue = item.entity.field_hs_postcard_link[0].title
  }

  let titleValue = '';
  if (typeof (item.entity.field_hs_postcard_title) !== 'undefined' && item.entity.field_hs_postcard_title.length) {
    titleValue = item.entity.field_hs_postcard_title[0].value
  }

  const displayOptions = [
    {value: 'vertical', label: 'Vertical Card'},
    {value: 'preview', label: 'Horizontal Card'},
    {value: 'token', label: 'Vertical Linked Card'},
  ];


  return (
    <div className="horizontal">
      <div className="form-item">
        <label>Display</label>
        <Select value={displayValue}
                options={displayOptions}/>
      </div>

      <div className="form-item">
        <label>Image</label>
        <MediaField
          data={imageValue}/>
      </div>

      <InputField
        label="Card Title"
        name="field_hs_postcard_title[0][value]"
        value={titleValue}
        onChange={onFieldEdit}
      />

      <TextAreaField
        label="Card Body"
        value={bodyValue}
        name="field_hs_postcard_body[0][value]"
        onChange={onFieldEdit}
      />

      <fieldset className="container">
        <legend>Read More Link</legend>
        <div className="fieldset-wrapper">
          <InputField
            label="URL"
            name="field_hs_postcard_link[0][uri]"
            value={linkUriValue}
            onChange={onFieldEdit}
          />
          <InputField
            label="Link text"
            name="field_hs_postcard_link[0][title]"
            value={linkTitleValue}
            onChange={onFieldEdit}
          />
        </div>
      </fieldset>
    </div>
  )
};
