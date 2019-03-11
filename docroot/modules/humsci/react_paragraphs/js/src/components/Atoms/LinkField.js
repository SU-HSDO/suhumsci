import React, {Component} from 'react';
import {InputField} from "./InputField";
import {default as UUID} from "node-uuid";
import styled from 'styled-components';
import Autocomplete from 'react-autocomplete';

const AutocompleteItem = styled.div`
  cursor: pointer;
  ${({highlighted}) => highlighted && `background: #ccc;`}
`;

const FieldsetWrapper = styled.div`
  > div:first-of-type div {
    z-index: 999
    background: #fff;
    padding: 5px;
  }
`;

export class LinkField extends Component {

  constructor(props) {
    super(props);
    this.state = {
      nodeList: [],
      uriValue: this.props.uriValue ? this.props.uriValue : '',
    };
    this.inputId = 'field-' + UUID.v4();
    this.onUriChange = this.onUriChange.bind(this);
    this.onUriSelect = this.onUriSelect.bind(this);
  }

  componentWillMount() {
    fetch(window.reactParagraphsApiUrl + '/entity-list/node')
      .then(response => response.json())
      .then(jsonData => {
        const list = [];
        const newState = {...this.state};

        Object.keys(jsonData).map(nodeId => {
          const uri = 'entity:node/' + nodeId;

          list.push({id: nodeId, uri: uri, label: jsonData[nodeId]});

          if (uri === newState.uriValue) {
            newState.uriValue = jsonData[nodeId] + ' (' + nodeId + ')'
          }
        });
        newState.nodeList = list;

        this.setState(newState);
      })
  }

  componentDidMount() {
    this.input.refs.input.id = this.inputId;
  }

  onUriChange(event) {
    event.preventDefault();
    this.setState({uriValue: event.target.value});
    this.props.onChange(this.props.uriName, event.target.value);
  }

  onUriSelect(value) {
    const selectedItem = this.state.nodeList.find(item => value === item.label + ' (' + item.id + ')');
    this.setState({uriValue: value});
    this.props.onChange(this.props.uriName, selectedItem.uri);
  }

  render() {

    return (
      <fieldset className="container">
        <legend>{this.props.legend}</legend>
        <FieldsetWrapper className="fieldset-wrapper">
          <label htmlFor={this.inputId}>Link URL</label>

          <Autocomplete
            ref={el => this.input = el}
            items={this.state.nodeList}
            getItemValue={item => item.label + ' (' + item.id + ')'}
            value={this.state.uriValue}
            onChange={this.onUriChange}
            onSelect={this.onUriSelect}
            shouldItemRender={(item, value) => item.label.toLowerCase().indexOf(value.toLowerCase()) > -1}
            wrapperStyle={{display: 'block'}}
            renderItem={(item, isHighlighted) =>
              <AutocompleteItem key={item.id} highlighted={isHighlighted}>
                {item.label}
              </AutocompleteItem>
            }
          />

          <InputField
            label="Link text"
            name={this.props.titleName}
            value={this.props.titleValue}
            onChange={this.props.onChange}
          />
        </FieldsetWrapper>
      </fieldset>
    )
  }
}
