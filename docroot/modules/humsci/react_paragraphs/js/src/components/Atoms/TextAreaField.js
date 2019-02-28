import React from "react";
import CKEditor from '@ckeditor/ckeditor5-react';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

export const TextAreaField = ({data, onItemEdit}) => {
  return (
    <CKEditor
      editor={ClassicEditor}
      data={data}
      onInit={editor => {
      }}
      onChange={(event, editor) => {
        const data = editor.getData();
      }}
      onBlur={editor => {
        // console.log('Blur.', editor);
      }}
      onFocus={editor => {
        // console.log('Focus.', editor);
      }}
    />
  )
};
