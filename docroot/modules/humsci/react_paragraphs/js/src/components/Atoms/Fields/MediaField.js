import React, {Component} from "react";
import Modal from 'react-modal';
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

        <Modal
          isOpen={this.state.modalOpen}
          style={{
            overlay: {
              background: 'rgba(0, 0, 0, 0.7)'
            },
            content: {
              top: 100,
              'z-index': 99
            }
          }}
        >
          <button className="close-modal" onClick={this.onOpenIframe}>Close
          </button>
          <iframe
            src={reactParagraphsApiUrl + "/entity-browser/modal/image_browser?reactCard=1&uuid=" + this.state.iframeUuid}
            width="100%" height="100%"/>
        </Modal>

        <input
          type="hidden"
          name={this.props.name}
          data-uuid={this.state.iframeUuid}
        />
      </div>
    )
  }
}

export class MediaItem extends Component {

  constructor(props) {
    super(props);
    this.state = {entity: {}};
  }

  componentWillMount() {
    if (this.props.mediaId) {
      fetch(window.reactParagraphsApiUrl + '/media/' + this.props.mediaId + '?_format=json')
        .then(response => response.json())
        .then(jsonData => {
          this.setState({entity: jsonData});
        })
    }
  }

  render() {
    if (this.state.entity.bundle) {
      switch (this.state.entity.bundle[0].target_id) {
        case 'image':
          return <MediaImage entity={this.state.entity}/>;
        case 'file':
          return <MediaFile entity={this.state.entity}/>;
        case 'video':
          return <MediaVideo entity={this.state.entity}/>;
      }
    }
    return (
      <div className="media-item">
        {this.props.mediaId}
      </div>
    )
  }
}

export const MediaImage = ({entity}) => {
  return (
    <div className="media-item">
      <img src={entity.field_media_image[0].url}
           style={{maxWidth: '200px', maxHeight: '200px'}}/>
    </div>
  )
};

export const MediaFile = ({entity}) => {
  return (
    <div className="media-item">
      BUILD THIS
    </div>
  )
};

export const MediaVideo = ({entity}) => {
  return (
    <div className="media-item">
      <iframe src={entity.field_media_video_embed_field[0].value}/>
    </div>
  )
};
