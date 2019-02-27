import {Select} from "semantic-ui-react";
import React, {Component} from "react";

export class ViewForm extends Component {

  constructor(props) {
    super(props);

    this.state = {
      viewOptions: [
        {value: '_none"', label: '- None -'},
        {value: 'hs_courses"', label: 'Courses'},
        {value: 'hs_events"', label: 'Events'},
        {value: 'hs_event_series"', label: 'Event Series'},
        {value: 'hs_news"', label: 'News'},
        {value: 'hs_person"', label: 'People'},
        {value: 'hs_publications"', label: 'Publications'},
      ]
    };
    this.state.optionValue = '';
    if (typeof (this.props.entity.field_hs_view) !== 'undefined' && this.props.entity.field_hs_view.length) {
      this.state.optionValue = this.props.entity.field_hs_view[0].value
    }
  }

  render() {
    return (
      <div>
        <div className="form-item">
          <label>Display</label>
          <Select value={this.state.optionValue}
                  options={this.state.viewOptions}/>
        </div>
      </div>
    )
  }
};
