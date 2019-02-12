import React from 'react'
import {Router, Route, hashHistory} from 'react-router'
import {render} from 'react-dom'
import {App} from './components/App'

window.React = React
render(
  <App />,
  document.getElementById('react-events')
);
