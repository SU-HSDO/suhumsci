import {Component, PropTypes} from 'react'
import Pager from 'react-pager'
import {EventCard} from "./EventCard";

const getEventSlice = (currentPage = 0, numPerPage = 7, events = []) => {
  return events.slice(currentPage * numPerPage, currentPage * numPerPage + numPerPage)
};

export class EventList extends Component {

  constructor(props) {
    super(props);

    let eventWeeks = this.getEventWeekArray(props.events);
    console.log(eventWeeks);
    this.state = {
      current: 34,
      events: props.events,
      eventSlices: eventWeeks
    };
    this.handlePageChanged = this.handlePageChanged.bind(this);
  }

  getEventWeekArray(events) {
    let weeks = [];
    events.map((event) => {
      let date = new Date(event.isoEventDate) / 1000;
      let sunday = this.getSunday(new Date());

      let week = Math.floor((date - sunday) / (7 * 24 * 60 * 60));
      if (weeks[week + 500] == undefined) {
        weeks[week + 500] = [];
      }
      weeks[week + 500].push(event);
    });
    weeks.sort(function (a, b) {
      return new Date(a[0]['isoEventDate']).getTime() - new Date(b[0]['isoEventDate']).getTime()
    });
    return weeks;
  }

  getSunday(d) {
    if (d.getDay() !== 0) {
      d.setHours(-24 * d.getDay());
    }
    d.setMinutes(0);
    d.setMilliseconds(0);
    d.setSeconds(0);
    return d.getTime() / 1000;
  }


  handlePageChanged(newPage) {
    this.setState({
      current: newPage
    });
  }

  render() {
    return (
      <div className="event-list">
        <Pager
          total={36}
          current={this.state.current}
          visiblePages={5}
          titles={{
            first: '« First',
            prev: '‹ Previous Week',
            next: 'Next Week ›',
            last: 'Last »'
          }}
          className="pager__items"
          onPageChanged={this.handlePageChanged}
        />


        <div className="events clearfix">
          {this.state.eventSlices[this.state.current].map((event, i) =>
            <EventCard key={i}
                       {...event}/>
          )}
        </div>


        <Pager
          total={this.state.eventSlices.length}
          current={this.state.current}
          visiblePages={this.state.visiblePage}
          titles={{
            first: '« First',
            prev: '‹ Previous Week',
            next: 'Next Week ›',
            last: 'Last »'
          }}
          className="pager__items"
          onPageChanged={this.handlePageChanged}
        />
      </div>
    )
  }
}
