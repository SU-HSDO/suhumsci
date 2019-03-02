import React from "react";
import {MediaField} from "../Atoms/MediaField";
import {TextAreaField} from "../Atoms/TextAreaField";
import {InputField} from "../Atoms/InputField";

export const AccordionForm = ({item, onFieldEdit}) => {
  let summaryValue = '';
  if (typeof (item.entity.field_hs_accordion_summary) !== 'undefined' && item.entity.field_hs_accordion_summary.length) {
    summaryValue = item.entity.field_hs_accordion_summary[0].value
  }

  let imageValue = '';
  if (typeof (item.entity.field_hs_accordion_image) !== 'undefined' && item.entity.field_hs_accordion_image.length) {
    imageValue = item.entity.field_hs_accordion_image[0].target_id
  }

  let descriptionValue = '';
  if (typeof (item.entity.field_hs_accordion_description) !== 'undefined' && item.entity.field_hs_accordion_description.length) {
    descriptionValue = item.entity.field_hs_accordion_description[0].value
  }

  return (
    <div>

      <InputField
        label="Summary"
        name="field_hs_accordion_summary[0][value]"
        value={summaryValue}
        onChange={onFieldEdit}
      />

      <MediaField
        label="Image"
        value={imageValue}
        allowedTypes={['image']}
        name="field_hs_accordion_image[0][target_id]"
        onChange={onFieldEdit}
      />

      <TextAreaField
        label="Description"
        name="field_hs_accordion_description[0][value]"
        formatName="field_hs_postcard_body[0][format]"
        value={descriptionValue}
        onChange={onFieldEdit}
      />
    </div>
  )
};
