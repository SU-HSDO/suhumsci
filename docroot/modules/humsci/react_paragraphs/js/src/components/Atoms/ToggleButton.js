import React, {Component} from 'react';
import styled from 'styled-components';
import icon from '../../icons/icon-actions.svg'

const Wrapper = styled.div`
  position: relative;
`;

const Button = styled.button`
  display: block;
  height: 100%;
  width: 40px;
  background-size: 26px 26px;
  border: 1px solid transparent;
  border-radius: 4px;
  -webkit-transition: all 0.1s;
  transition: all 0.1s;
  cursor: pointer;
  background:url(${icon}) no-repeat center;
`;

const InputWrapper = styled.div`
  position: absolute;
  z-index: 99;
  white-space: nowrap;
  right: 0;
  display: none;
  border: 1px solid #ccc;
  background: #fff;
  border-radius: 5px 0 5px 5px;
  padding: 5px;
  ${({displayItems}) => displayItems && `display:block;`}
`;

const Input = styled.button`
  display: block;
  width:100%;
  
  &:not(:first-child) {
    margin-top: 5px;
  }
`;

export class ToggleButton extends Component {

  constructor(props) {
    super(props);
    this.state = {
      showActions: false,
    };
    this.toggleActions = this.toggleActions.bind(this);
  }

  toggleActions(action, event) {
    event.preventDefault();
    if (action === 'leave') {
      this.setState({showActions: false});
      return;
    }
    this.setState({showActions: !this.state.showActions});
  }

  render() {
    return (
      <Wrapper
        className={this.props.className}
        onMouseLeave={this.toggleActions.bind(undefined, 'leave')}
      >

        <Button
          className="toggle-button"
          onClick={this.toggleActions.bind(undefined, 'toggle')}
        >
          <span className="visually-hidden">Toggle Row Actions</span>
        </Button>

        <InputWrapper
          className="toggle-button-list"
          displayItems={this.state.showActions}
        >
          {this.props.actions.map((item, index) => {

            return (
              <Input
                type="submit"
                key={index}
                onClick={item.onClick}
                value={item.value}
              >{item.value}</Input>
            )
          })}
        </InputWrapper>
      </Wrapper>
    )
  }
}
