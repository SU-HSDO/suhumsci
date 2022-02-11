require('dotenv').config({ path: './.env' });
const fs = require('fs');

const backstopFile = './backstop/backstop.json';
const sites = [
  'hs-colorful',
  'hs-traditional',
];
const testPages = [
  '/',
  '/training-page',
  '/training/accessibility-reference-page',
  '/default-views/courses',
  '/default-views/events',
  '/default-views/event-series',
  '/events/colloquium-series',
  '/events/example-lecture-series',
  '/default-views/news',
  '/default-views/people',
  '/default-views/publications',
  '/courses/politics-sex-work-family-and-citizenship-modern-american-womens-history-csre-162-femgen-0',
  '/news/jackelyn-hwang-wins-jane-addams-award-best-article-community-and-urban-sociology-section',
  '/events/colloquium-series/sociology-department-colloquium-robert-braun',
  '/people/abhinanda-sarkar',
  '/publications/privilege-and-punishment-how-race-and-class-matter-criminal-court',
  '/components/accordions',
  '/components/callout-box',
  '/components/collection-text-areas',
  '/components/collections-cards',
  '/components/collections-cards-uniform-and-stretch',
  '/components/collection-real-world-examples',
  '/components/collection-real-world-examples-w-well',
  '/components/collection-real-examples-uniform-and-stretch',
  '/components/color-bands',
  '/components/hero-images-main-region',
  '/componentshero-images-main-region/hero-gradient-slider',
  '/componentshero-images/hero-layered-slider',
  '/componentshero-images-main-region/hero-no-text',
  '/componentshero-images-main-region/hero-overlay',
  '/componentshero-images-main-region/tall-spotlight',
  '/componentshero-images-main-region/short-spotlight',
  '/components/photo-album',
  '/components/testimonials',
  '/components/text-area-typography',
  '/components/vertical-timeline-without-and-collection',
  '/components/vertical-timeline-without-and-collection-well',
  '/mixed-pages/mixed-page',
  '/mixed-pages/mixed-page-raised-cards',
  '/mixed-pages/mixed-page-wells',
  '/mixed-pages/mixed-page-wells-raised-cards',
  '/mixed-page-wells-raised-cards-full-width',
  '/mixed-pages/mixed-page-wells-raised-cards-full-width-sidebar',
  '/common-embeds',
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
    engine_scripts: 'backstop_data/engine_scripts',
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
let scenarios = [];

/**
 * This generates a backstop config file.
 * Once ran, you can run backstop with that config with:
 * 1. npm run backstop:reference (creates the reference images)
 * 2. npm run backstop:reference (Creates the comparisons)
 */
// Loop through each site to generate a backstop.json file
sites.forEach((site) => {
  // Create scenarios
  const siteScenarios = testPages.map((url) => ({
    label: `${site} ${url}`,
    url: `https://${process.env.BASIC_USERNAME}:${process.env.BASIC_PASSWORD}@${site}-dev.stanford.edu${url}`,
    referenceUrl: `https://${process.env.BASIC_USERNAME}:${process.env.BASIC_PASSWORD}@${site}-stage.stanford.edu${url}`,
    delay: 500,
    requireSameDimensions: true,
  }));
  scenarios.push(siteScenarios);
});

// The forEach loop will push separate arrays to scenarios
// This will concatinate the individual arrays into a single array
scenarios = [].concat(...scenarios);

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
