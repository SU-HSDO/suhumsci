import {Component, PropTypes} from 'react'
import Pager from 'react-pager'
import {EventCard} from "./EventCard";

const getEventSlice = (currentPage = 0, numPerPage = 7, events = []) => {
  return events.slice(currentPage * numPerPage, currentPage * numPerPage + numPerPage)
};

export class EventList extends Component {

  constructor(props) {
    super(props);
    let numPerPage = 7;

    this.state = {
      numPerPage: numPerPage,
      total: Math.ceil(props.events.length / numPerPage),
      current: 0,
      visiblePage: 5,
      events: props.events,
      eventSlice: getEventSlice(0, numPerPage, props.events)
    };
    this.handlePageChanged = this.handlePageChanged.bind(this);
  }

  handlePageChanged(newPage) {
    this.setState({
      current: newPage,
      eventSlice: getEventSlice(newPage, this.state.numPerPage, this.state.events)
    });
  }

  render() {
    return (
      <div className="event-list">
        <div className="events clearfix">
          {this.state.eventSlice.map((event, i) =>
            <EventCard key={i}
                       {...event}/>
          )}
        </div>


        <Pager
          total={this.state.total}
          current={this.state.current}
          visiblePages={this.state.visiblePage}
          titles={{
            first: '« First',
            prev: '‹ Previous',
            next: 'Next ›',
            last: 'Last »'
          }}
          className="pager__items"
          onPageChanged={this.handlePageChanged}
        />
      </div>
    )
  }
}
