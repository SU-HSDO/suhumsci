import React, {Component} from 'react';
import PropTypes from 'prop-types';

export class ToolBox extends Component {

  render() {
    return (
      <div className="toolbox">
        <span className="toolbox__title">Toolbox</span>
        <div className="toolbox__items">
          {Object.keys(this.props.items).map(item_id => (
            <ToolBoxItem
              key={item_id}
              id={item_id}
              item={this.props.items[item_id]}
              onTakeItem={this.props.onTakeItem}
            />
          ))}
        </div>
      </div>
    );
  }
}

export const ToolBoxItem = ({id, item, onTakeItem}) => {
  item.id = id;
  return (
    <div className="toolbox__items__item">
      <a href="#" onClick={onTakeItem.bind(undefined, item)}>
        Add a {item.label}
      </a>
    </div>
  )
};

ToolBoxItem.propTypes = {
  key: PropTypes.string,
  item: PropTypes.object,
  onTakeItem: PropTypes.func
};
