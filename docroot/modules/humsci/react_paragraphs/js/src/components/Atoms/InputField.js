import React from "react";
import {default as UUID} from "node-uuid";

export const InputField = ({type, label, name, value, onChange, attributes}) => {
  const inputId = 'field-' + UUID.v4();
  return (
    <div className="form-item" {...attributes}>
      <label htmlFor={inputId}>{label}</label>
      <input
        type={type ? type : 'textfield'}
        id={inputId}
        name={name}
        defaultValue={value}
        onChange={onChange ? onChange.bind(undefined, name) : null}
      />
    </div>
  )
};
