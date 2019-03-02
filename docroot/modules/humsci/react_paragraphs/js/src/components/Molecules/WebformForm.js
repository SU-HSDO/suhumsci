import {Select} from "semantic-ui-react";
import React from "react";

export const WebformForm = ({item}) => {
  const displayOptions = [
    {value: '_none', label: '- None -'},
    {value: 'contact"', label: 'Contact Us'},
    {value: 'funding_request"', label: 'Funding Request'},
  ];
  return (
    <div>
      <div className="form-item">
        <label>Display</label>
        <Select options={displayOptions}/>
      </div>
    </div>
  )
};
