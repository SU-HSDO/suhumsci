import React, {Component} from 'react';

export class ResizeHandle extends Component {
  render(){
    return(
      <div className="resize-handle"><span className="visually-hidden">Resize the item</span></div>
    )
  }
}
