import React from 'react'
import {render} from 'react-dom'
import {ParagraphGroups} from './components/ParagraphGroups'
import "../../scss/react_paragraphs.field_widget.scss"

window.React = React;

// For yarn dev, use test data.
if (typeof (window.drupalSettings) === 'undefined') {
  window.drupalSettings = {
    reactParagraphs: [
      {
        fieldId: 'node-test-type-field-react-paragraphs',
        entityId: 437,
        fieldName: 'field_hs_page_components',
        available_items: {
          hs_accordion: {label: 'Accordion'},
          hs_hero_image: {label: 'Hero Image'},
          hs_postcard: {label: 'Postcard'},
          hs_text_area: {label: 'Text Area'},
          hs_view: {label: 'View'},
          hs_webform: {label: 'Webform'}
        },
      }
    ]
  };
}

window.drupalSettings.reactParagraphs.map(item => {
  // item.entityId = null;
  // item.fieldName = 'field_hs_page_components';
  var paragraphsForm = document.getElementById(item.fieldId);
  if (paragraphsForm) {
    render(<ParagraphGroups {...item}/>, paragraphsForm);
  }
});
