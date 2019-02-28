import {SketchPicker} from "react-color";
import React from "react";
import {TextAreaField} from "../Atoms/TextAreaField";

export const TextAreaForm = ({entity, onFieldEdit}) => {

  let bodyValue = '';
  if (typeof (entity.field_hs_text_area) !== 'undefined' && entity.field_hs_text_area.length) {
    bodyValue = entity.field_hs_text_area[0].value
  }

  return (
    <div className="text-area">
      <TextAreaField
        label="Text Area"
        name="field_hs_text_area[0][value]"
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
