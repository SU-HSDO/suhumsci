import {SketchPicker} from "react-color";
import React from "react";
import {TextAreaField} from "../Atoms/TextAreaField";

export const TextAreaForm = ({item, onFieldEdit}) => {

  let bodyValue = '';
  if (typeof (item.entity.field_hs_text_area) !== 'undefined' && item.entity.field_hs_text_area.length) {
    bodyValue = item.entity.field_hs_text_area[0].value
  }

  return (
    <div className="text-area">
      <TextAreaField
        label="Text Area"
        name="field_hs_text_area[0][value]"
        formatName="field_hs_text_area[0][format]"
        value={bodyValue}
        onChange={onFieldEdit}
      />

      <div className="form-item">
        <label>Background Color</label>
        <SketchPicker/>
      </div>
    </div>
  )
};
