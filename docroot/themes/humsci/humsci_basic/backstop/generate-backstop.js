require('dotenv').config({ path: './.env' });
const fs = require('fs');

const backstopFile = './backstop/backstop.json';
const sites = ['hs-colorful', 'hs-traditional'];
const colorPairings = {
  'hs-colorful': ['ocean', 'mountain', 'cardinal', 'lake', 'canyon', 'cliff'],
  'hs-traditional': ['cardinal', 'bluejay', 'warbler', 'firefinch'],
};
const testPages = [
  '/',
  '/default-views/people',
  '/default-views/publications',
  '/news/jackelyn-hwang-wins-jane-addams-award-best-article-community-and-urban-sociology-section',
  '/events/colloquium-series/sociology-department-colloquium-robert-braun',
  '/people/abhinanda-sarkar',
  '/publications/privilege-and-punishment-how-race-and-class-matter-criminal-court',
  '/collection-real-world-examples',
  '/collection-real-world-examples-w-well',
  '/collection-real-examples-uniform-and-stretch',
  '/hero-images-main-region',
  '/componentshero-images-main-region/hero-gradient-slider',
  '/hero-images-main-region/hero-layered-slider',
  '/componentshero-images-main-region/hero-no-text',
  '/componentshero-images-main-region/hero-overlay',
  '/componentshero-images-main-region/tall-spotlight',
  '/componentshero-images-main-region/short-spotlight',
  '/components/photo-album',
  '/components/text-area-typography',
  '/components/vertical-timeline-view-using-events-ct',
  '/mixed-pages/mixed-page-raised-cards',
  '/collections-cards-uniform-and-stretch-1',
  '/mixed-pages/mixed-page',
  '/mixed-pages/mixed-page-wells',
  '/mixed-pages/mixed-page-wells-raised-cards',
];
const config = {
  id: 'backstop_default',
  viewports: [
    {
      label: 'small',
      width: 576,
      height: 900,
    },
    {
      label: 'medium',
      width: 768,
      height: 900,
    },
    {
      label: 'large',
      width: 1200,
      height: 900,
    },
    {
      label: 'extra large',
      width: 1920,
      height: 900,
    },
  ],
  paths: {
    bitmaps_reference: 'backstop_data/bitmaps_reference',
    bitmaps_test: 'backstop_data/bitmaps_test',
    engine_scripts: 'backstop/engine_scripts',
    html_report: 'backstop_data/html_report',
    ci_report: 'backstop_data/ci_report',
  },
  report: ['browser'],
  engine: 'puppeteer',
  engineOptions: {
    args: ['--no-sandbox'],
  },
  asyncCaptureLimit: 5,
  asyncCompareLimit: 50,
  debug: false,
  debugWindow: false,
};
const scenarios = [];

/**
 * This generates a backstop config file.
 * Once ran, you can run backstop with that config with:
 * 1. npm run backstop:reference (creates the reference images)
 * 2. npm run backstop:reference (Creates the comparisons)
 */
// Loop through each site to generate a backstop.json file
sites.forEach((site) => {
  // Create scenarios
  colorPairings[site].forEach((pairing) => {
    testPages.forEach((url) => {
      scenarios.push({
        label: `${site} ${url} ${pairing}`,
        url: `https://${site}.suhumsci.loc${url}`,
        referenceUrl: `https://${process.env.BASIC_USERNAME}:${process.env.BASIC_PASSWORD}@${site}-stage.stanford.edu${url}`,
        readySelector: '.js',
        delay: 1500,
        requireSameDimensions: true,
        onReadyScript: 'onReady.js',
      });
    });

    // Menus.
    scenarios.push({
      label: `${site} mobile_menu ${pairing}`,
      url: `https://${site}.suhumsci.loc`,
      referenceUrl: `https://${process.env.BASIC_USERNAME}:${process.env.BASIC_PASSWORD}@${site}-stage.stanford.edu`,
      readySelector: '.js',
      delay: 500,
      requireSameDimensions: true,
      clickSelector: '.hb-main-nav__toggle',
      postInteractionWait: 250,
      viewports: [
        {
          label: 'medium',
          width: 768,
          height: 900,
        },
      ],
      onReadyScript: 'onReady.js',
    });
    scenarios.push({
      label: `${site} mobile_menu_child_open ${pairing}`,
      url: `https://${site}.suhumsci.loc`,
      referenceUrl: `https://${process.env.BASIC_USERNAME}:${process.env.BASIC_PASSWORD}@${site}-stage.stanford.edu`,
      readySelector: '.js',
      delay: 500,
      requireSameDimensions: true,
      clickSelectors: ['.hb-main-nav__toggle', '.hb-main-nav__button'],
      postInteractionWait: 250,
      viewports: [
        {
          label: 'medium',
          width: 768,
          height: 900,
        },
      ],
      onReadyScript: 'onReady.js',
    });
    scenarios.push({
      label: `${site} desktop_menu ${pairing}`,
      url: `https://${site}.suhumsci.loc`,
      referenceUrl: `https://${process.env.BASIC_USERNAME}:${process.env.BASIC_PASSWORD}@${site}-stage.stanford.edu`,
      readySelector: '.js',
      delay: 500,
      requireSameDimensions: true,
      clickSelector: '.hb-main-nav__button',
      postInteractionWait: 500,
      viewports: [
        {
          label: 'large',
          width: 1200,
          height: 900,
        },
      ],
      onReadyScript: 'onReady.js',
    });
  });
});

// Add the scenarios array to the config
config.scenarios = scenarios;

// Write the config to a json file
fs.writeFile(backstopFile, JSON.stringify(config), (err) => {
  if (err) {
    console.log('File write failed:', err);
    return err;
  }

  // Inform the tester of next steps:
  console.log(`Backstop config saved to ${backstopFile}`);
  return config;
});
