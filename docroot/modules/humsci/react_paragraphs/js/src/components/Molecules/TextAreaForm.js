import {SketchPicker} from "react-color";
import React from "react";
import {TextAreaField} from "../Atoms/TextAreaField";

export const TextAreaForm = ({entity, onItemEdit}) => {

  let bodyValue = '';
  if (typeof (entity.field_hs_text_area) !== 'undefined' && entity.field_hs_text_area.length) {
    bodyValue = entity.field_hs_text_area[0].value
  }

  return (
    <div className="text-area">
      <div className="form-item">
        <label>Text Area</label>
        <TextAreaField data={bodyValue}/>
      </div>
      <div className="form-item">
        <label>Background Color</label>
        <SketchPicker/>
      </div>
    </div>
  )
};
