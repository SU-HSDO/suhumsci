import React from 'react';
import {configure, shallow, render} from 'enzyme';
import Adapter from 'enzyme-adapter-react-16';
import {ParagraphGroups} from "../components/ParagraphGroups";

global.fetch = require('jest-fetch-mock');
configure({adapter: new Adapter()});

const data = {
  existing_items: [
    {
      target_id: 1,
      settings: {row: 0, index: 0, width: 12}
    },
    {
      target_id: 2,
      settings: {row: 1, index: 0, width: 6}
    },
    {
      target_id: 3,
      settings: {row: 1, index: 1, width: 6}
    },
    {
      target_id: 4,
      settings: {row: 2, index: 0, width: 3}
    },
    {
      target_id: 5,
      settings: {row: 2, index: 1, width: 6}
    },
    {
      target_id: 6,
      settings: {row: 2, index: 2, width: 3}
    }
  ],
  available_items: {
    hs_accordion: {label: 'Accordion'},
    hs_hero_image: {label: 'Hero Image'},
    hs_postcard: {label: 'Postcard'},
    hs_text_area: {label: 'Text Area'},
    hs_view: {label: 'View'},
    hs_webform: {label: 'Webform'}
  },
};


it("renders correctly", () => {
  const wrapper = render(
    <ParagraphGroups {...data}/>
  );
  expect(wrapper).toMatchSnapshot();
});
