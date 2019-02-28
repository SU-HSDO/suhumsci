import React from "react";
import CKEditor from '@ckeditor/ckeditor5-react';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import {default as UUID} from "node-uuid";

export const TextAreaField = ({label, name, value, onChange,}) => {
  const inputId = 'field-' + UUID.v4();
  return (
    <div className="form-item">
      <label htmlFor={inputId}>{label}</label>


      <CKEditor
        id={inputId}
        editor={ClassicEditor}
        data={value}
        onInit={editor => {
        }}
        onChange={(event, editor) => {

        }}
        onBlur={(event, editor) => {
          // When the user leaves the area, trigger the values.
          event.target = {value: editor.getData()};
          if (onChange) {
            onChange(name, event);
          }
        }}
        onFocus={editor => {
          // console.log('Focus.', editor);
        }}
      />
    </div>
  )
};
