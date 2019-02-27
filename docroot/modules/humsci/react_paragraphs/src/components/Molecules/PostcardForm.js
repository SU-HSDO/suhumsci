import {Select} from "semantic-ui-react";
import React from "react";
import {TextAreaField} from "../Atoms/TextAreaField";
import {MediaField} from "../Atoms/MediaField";
import {InputField} from "../Atoms/InputField";

export const PostcardForm = ({entity}) => {

  let displayValue = '';
  if (typeof (entity.field_hs_postcard_display) !== 'undefined' && entity.field_hs_postcard_display.length) {
    displayValue = entity.field_hs_postcard_display[0].value
  }

  let bodyValue = '';
  if (typeof (entity.field_hs_postcard_body) !== 'undefined' && entity.field_hs_postcard_body.length) {
    bodyValue = entity.field_hs_postcard_body[0].value
  }

  let imageValue = '';
  if (typeof (entity.field_hs_postcard_image) !== 'undefined' && entity.field_hs_postcard_image.length) {
    imageValue = entity.field_hs_postcard_image[0].value
  }

  let linkUriValue = '';
  if (typeof (entity.field_hs_postcard_link) !== 'undefined' && entity.field_hs_postcard_link.length) {
    linkUriValue = entity.field_hs_postcard_link[0].uri
  }

  let linkTitleValue = '';
  if (typeof (entity.field_hs_postcard_link) !== 'undefined' && entity.field_hs_postcard_link.length) {
    linkTitleValue = entity.field_hs_postcard_link[0].title
  }

  let titleValue = '';
  if (typeof (entity.field_hs_postcard_title) !== 'undefined' && entity.field_hs_postcard_title.length) {
    titleValue = entity.field_hs_postcard_title[0].value
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
        name="field_hs_postcard_title"
        value={titleValue}
      />

      <div className="form-item">
        <label>Card Body</label>
        <TextAreaField data={bodyValue}/>
      </div>

      <fieldset className="container">
        <legend>Read More Link</legend>
        <div className="fieldset-wrapper">
          <InputField
            label="URL"
            name="field_hs_postcard_link[uri]"
            value={linkUriValue}
          />
          <InputField
            label="Link text"
            name="field_hs_postcard_link[title]"
            value={linkTitleValue}
          />
        </div>
      </fieldset>


    </div>
  )
};
