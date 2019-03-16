import React from 'react'
import Modal from 'react-modal';
import {render} from 'react-dom'
import {ParagraphGroups} from './components/ParagraphGroups'
import "./react_paragraphs.field_widget.scss"

window.React = React;
window.reactParagraphsApiUrl = window.location.origin;

// For yarn dev, use test data.
if (typeof (window.drupalSettings) === 'undefined') {
  window.drupalSettings = {
    reactParagraphs: [
      {
        fieldId: 'node-test-type-field-react-paragraphs',
        entityId: 446,
        fieldName: 'field_react_paragraphs',
        available_items: {
          hs_accordion: {
            label: 'Accordion',
            icon: "https://cdn0.iconfinder.com/data/icons/penthemes-layour-builder/512/accordion-512.png"
          },
          hs_hero_image: {label: 'Hero Image', icon: null},
          hs_postcard: {label: 'Postcard', icon: null},
          hs_text_area: {label: 'Text Area', icon: null},
          hs_view: {label: 'View', icon: null},
          hs_webform: {label: 'Webform', icon: null}
        },
        existing_items: [
          {
            target_id: 257,
            settings: {row: 0, index: 0, width: 12}
          },
          {
            target_id: 258,
            settings: {row: 1, index: 0, width: 12}
          },
          {
            target_id: 259,
            settings: {row: 2, index: 0, width: 12}
          }
        ]
      }
    ]
  };
  window.reactParagraphsApiUrl = 'http://docroot.suhumsci.loc';
}

// The field widget gives us all the data we need to get started.
window.drupalSettings.reactParagraphs.map(item => {
  Modal.setAppElement('#' + item.fieldId);

  var paragraphsForm = document.getElementById(item.fieldId);
  if (paragraphsForm) {
    render(<ParagraphGroups {...item}/>, paragraphsForm);
  }
});
