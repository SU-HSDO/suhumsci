import React from 'react'
import Modal from 'react-modal';
import {render} from 'react-dom'
import {ParagraphGroups} from './components/ParagraphGroups'
import "./scss/react_paragraphs.field_widget.scss"

window.React = React;
window.reactParagraphsApiUrl = window.location.origin;

// For yarn dev, use test data.
if (typeof (window.drupalSettings) === 'undefined') {
  window.drupalSettings = {
    reactParagraphs: [
      {
        fieldId: 'node-test-type-field-react-paragraphs',
        entityId: 442,
        fieldName: 'field_react_paragraphs',
        available_items: {
          hs_accordion: {label: 'Accordion'},
          hs_hero_image: {label: 'Hero Image'},
          hs_postcard: {label: 'Postcard'},
          hs_text_area: {label: 'Text Area'},
          hs_view: {label: 'View'},
          hs_webform: {label: 'Webform'}
        },
        existing_items: [
          {
            target_id: 280,
            settings: {row: 0, index: 0, width: 12}
          },
          {
            target_id: 281,
            settings: {row: 1, index: 0, width: 12}
          }
        ]
      }
    ]
  };
  window.reactParagraphsApiUrl = 'http://docroot.suhumsci.loc';
}


window.drupalSettings.reactParagraphs.map(item => {
  Modal.setAppElement('#' + item.fieldId);

  var paragraphsForm = document.getElementById(item.fieldId);
  if (paragraphsForm) {
    render(<ParagraphGroups {...item}/>, paragraphsForm);
  }
});
