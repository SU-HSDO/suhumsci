import React from "react";
import {MediaField} from "../Atoms/MediaField";
import {TextAreaField} from "../Atoms/TextAreaField";
import {InputField} from "../Atoms/InputField";

export const AccordionForm = ({entity, onFieldEdit}) => {
  let summaryValue = '';
  if (typeof (entity.field_hs_accordion_summary) !== 'undefined' && entity.field_hs_accordion_summary.length) {
    summaryValue = entity.field_hs_accordion_summary[0].value
  }

  let imageValue = '';
  if (typeof (entity.field_hs_accordion_image) !== 'undefined' && entity.field_hs_accordion_image.length) {
    imageValue = entity.field_hs_accordion_image[0].target_id
  }

  let descriptionValue = '';
  if (typeof (entity.field_hs_accordion_description) !== 'undefined' && entity.field_hs_accordion_description.length) {
    descriptionValue = entity.field_hs_accordion_description[0].value
  }

  return (
    <div>

      <InputField
        label="Summary"
        name="field_hs_accordion_summary[0][value]"
        value={summaryValue}
        onChange={onFieldEdit}
      />

      <div className="form-item">
        <label>Image</label>
        <MediaField data={imageValue}/>
      </div>


        <TextAreaField
          label="Description"
          value={descriptionValue}
          name="field_hs_accordion_description[0][value]"
          onChange={onFieldEdit}
        />
    </div>
  )
};
