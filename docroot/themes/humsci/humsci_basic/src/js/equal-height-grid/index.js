import equalHeightGrid from './equal-height-grid';

// Make the vertical linked card titles the same max height
const stretchCardsTiming = async(element, specialWrapperClass) => {
  console.log('here in async');
  const result = await equalHeightGrid(element, specialWrapperClass);
  return result;
};
// equalHeightGrid('hb-vertical-linked-card__title', 'hb-stretch-vertical-linked-cards');
// equalHeightGrid('hb-vertical-linked-card', 'hb-stretch-vertical-linked-cards');
stretchCardsTiming('hb-vertical-linked-card__title', 'hb-stretch-vertical-linked-cards');
stretchCardsTiming('hb-vertical-linked-card', 'hb-stretch-vertical-linked-cards');