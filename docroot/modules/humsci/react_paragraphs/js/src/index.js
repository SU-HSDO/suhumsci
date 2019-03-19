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
          hs_hero_image: {
            label: 'Hero Image',
            icon: 'https://cdn0.iconfinder.com/data/icons/business-concepts-3/399/Superhero-512.png'
          },
          hs_postcard: {
            label: 'Postcard',
            icon: 'https://cdn2.iconfinder.com/data/icons/travel-set-02/64/postcard-512.png'
          },
          hs_text_area: {label: 'Text Area', icon: null},
          hs_view: {
            label: 'View',
            icon: 'http://cdn.onlinewebfonts.com/svg/img_82026.png'
          },
          hs_webform: {
            label: 'Webform',
            icon: 'https://cdn3.iconfinder.com/data/icons/interaction-design/512/Form2-512.png'
          }
        },
        existing_items: [
          // {
          //   target_id: 257,
          //   settings: {row: 0, index: 0, width: 12}
          // },
          // {
          //   target_id: 258,
          //   settings: {row: 1, index: 0, width: 12}
          // },
          // {
          //   target_id: 259,
          //   settings: {row: 2, index: 0, width: 12}
          // }
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

document.getElementById('edit-submit').addEventListener("click", e => {

  if (Object.keys(window.ParagraphsGroups.validateFields()).length >= 1) {
    e.preventDefault();
  }
});
