import React, {Component} from "react";
import ReactModal from 'react-modal';
import {default as UUID} from "node-uuid";


export class MediaField extends Component {
  constructor(props) {
    super(props);
    this.state = {
      modalOpen: false,
      inputId: 'field-' + UUID.v4(),
      iframeUuid: UUID.v4(),
      selectedItems: typeof (this.props.value) === 'array' ? this.props.value : [this.props.value]
    };
    this.onOpenIframe = this.onOpenIframe.bind(this);
    this.onMediaSelection = this.onMediaSelection.bind(this);
  }

  onMediaSelection(event, itemUuid, selectedEntities) {
    const entityIds = selectedEntities.map(item => item[0]);
    this.setState(prevState => ({
      ...prevState,
      modalOpen: false,
      selectedItems: entityIds
    }));
    this.props.onChange(this.props.name, entityIds.toString())
  };

  onOpenIframe(event) {
    event.preventDefault();

    if (typeof (jQuery) === 'function') {
      jQuery(':input[data-uuid="' + this.state.iframeUuid + '"]')
        .on('entities-selected', this.onMediaSelection)
        .addClass('entity-browser-processed');
    }

    this.setState(prevState => ({
      ...prevState,
      modalOpen: !prevState.modalOpen
    }))
  }


  render() {
    return (
      <div className="form-item">
        <label>{this.props.label}</label>
        <br/>

        <input
          id={'id-' + this.state.iframeUuid}
          ref={elem => this.nv = elem}
          type="submit"
          value="Select Image"
          className="button"
          name={this.props.name + '[open]'}
          onClick={this.onOpenIframe}
        />

        {this.state.selectedItems.length &&
        <div className="selected-items">
          Selected Media:
          {this.state.selectedItems.map(mediaId => {
            return (<MediaItem key={mediaId} mediaId={mediaId}/>)
          })}

        </div>
        }

        <ReactModal isOpen={this.state.modalOpen} style={{'z-index': 99}}>
          <button className="close-modal" onClick={this.onOpenIframe}>Close
          </button>
          <iframe
            src={reactParagraphsApiUrl + "/entity-browser/modal/image_browser?uuid=" + this.state.iframeUuid}
            width="100%" height="100%"/>
        </ReactModal>

        <input
          type="hidden"
          name={this.props.name}
          data-uuid={this.state.iframeUuid}
        />
      </div>
    )
  }
}

export const MediaItem = ({mediaId, onRemoveItem}) => {
  return (
    <div className="media-item">
      {mediaId}
    </div>
  )
};
