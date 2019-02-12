import {PropTypes} from 'react'

export const EventCard = ({title, isoEventDate, description, imageUrl}) => {
  let date = new Date(Date.parse(isoEventDate));

  function trimHtml(html) {
    // Create a new div element
    var temporalDivElement = document.createElement("div");
    // Set the HTML content with the providen
    temporalDivElement.innerHTML = html;
    // Retrieve the text property of the element (cross-browser support)
    var text = temporalDivElement.textContent || temporalDivElement.innerText || "";

    var maxLength = 150;
    var trimmedString = text.substr(0, maxLength);
    trimmedString = trimmedString.substr(0, Math.min(trimmedString.length, trimmedString.lastIndexOf(" ")));

    return trimmedString
  }

  return (
    <div className="event-card">
      <div className="decanter-width-three-fourths">
        <h2 className="title">
          {title}
        </h2>
        <div className="date">
          {(date.getUTCMonth() + 1) + '/' + date.getUTCDate() + '/' + date.getFullYear()}
        </div>
        <div className="description">
          {( trimHtml(description) )}
        </div>
      </div>

      <div className="image decanter-width-one-fourth">
        <img src={imageUrl} width={200}/>
      </div>
    </div>
  )
};

EventCard.propTypes = {
  title: PropTypes.string,
  description: PropTypes.string,
  imageUrl: PropTypes.string
};
