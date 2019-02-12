import {Component} from 'react'
import {EventList} from "./EventList";
import '../scss/stanford_events.scss'

export class App extends Component {

  constructor(props) {
    super(props);

    this.state = {events: []};

    fetch('/api/hs_event')
      .then(results => {
        return results.json();
      }).then(data => {

      data = data.map((event) => {
        return {
          title: event['title'][0]['value'],
          isoEventDate: event['field_hs_event_date'][0]['value'],
          description: event['body'][0]['processed']
        }
      });
      this.setState({
        events: this.sortEvents(data)
      })
    })
  }

  sortEvents = (events, sortField = "isoEventDate") => {
    events.sort(function (a, b) {
      try {
        let aDate = new Date.parse(a[sortField]);
        let bDate = new Date.parse(b[sortField]);
        return aDate - bDate;
      }
      catch (e) {
        // Do nothing.
      }

      let tempArray = [a[sortField], b[sortField]];
      tempArray.sort();
      return tempArray[0] === a[sortField] ? -1 : 1;
    });
    return events;
  };

  render() {
    return (
      (this.state.events.length ?
          <div className="app">
            <EventList events={this.state.events}/>
          </div>
          :
          <div className="app">
            <div className="loading">
              Loading...
            </div>
          </div>
      )
    )
  }
}
