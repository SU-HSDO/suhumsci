require('dotenv').config({ path: './.env' });
const fs = require('fs');

const testDomain = 'http://sparkbox-sandbox.suhumsci.loc';
const referenceDomain = `https://${process.env.BASIC_USERNAME}:${process.env.BASIC_PASSWORD}@sparkbox-sandbox-stage.stanford.edu`;
const defaultURLs = [
  '/',
  '/qa-views',
  '/qa-text-area',
  '/qa-text-area-2',
  '/qa/media-wrapping-text',
  '/carousel-hero-image',
  '/qa/texts-cards-row-wells',
  '/qa/texts-cards-row-wells-hb-raised-cards',
  '/qa/texts-cards-row-wells-hb-stretch-vertical-linked-cards',
  '/qa/qa-postcards',
  '/qa/qa-raised-postcards',
  '/qa/qa-stretch-vertical-linked-postcards',
  '/qa/date-stacked-horizontal-card',
  '/qa/breakout-and-quote-styles',
  '/qa/qa-heading-size-issues-when-not-left-aligned',
  '/qa/qa-page',
  '/qa/qa-image-styles',
  '/events/1st-year-economics-graduate-student-seminar-series/qa-events-a',
  '/news/qa-joeys-test-news-item',
  '/people/qa-person-all-fields-filled',
  '/qa/compact-laser-driven-ions-beams-combining-global-leadership-high-pow',
  '/publications/qa-publications-all-fields-filled',
  '/utilities/field-utilities',
  '/utilities/node-group-block-utilities',
  '/wysiwyg-typography-styles',
  '/people/people-flexible-image-classes',
  '/courses-0'
];

/**
 * This generates a backstop config file.
 * Once ran, you can run backstop with that config with:
 * 1. npm run backstop:reference (creates the reference images)
 * 2. npm run backstop:reference (Creates the comparisons)
 */
(async () => {
  try {
    const config = {
      id: 'backstop_default',
      viewports: [
        {
          label: "small",
          width: 576,
          height: 900
        },
        {
          label: "medium",
          width: 768,
          height: 900
        },
        {
          label: "extra large",
          width: 1200,
          height: 900
        }
      ],
      paths: {
        bitmaps_reference: "backstop_data/bitmaps_reference",
        bitmaps_test: "backstop_data/bitmaps_test",
        engine_scripts: "backstop_data/engine_scripts",
        html_report: "backstop_data/html_report",
        ci_report: "backstop_data/ci_report"
      },
      report: ["browser"],
      engine: "puppeteer",
      engineOptions: {
        args: ["--no-sandbox"]
      },
      asyncCaptureLimit: 5,
      asyncCompareLimit: 50,
      debug: false,
      debugWindow: false
    };

    // If run with everuthing=true, map through the sitemape.
    const URLs = process.env.everything
      ? await getSiteMapPaths()
      : defaultURLs;
    const scenarios = URLs.map(url => ({
      label: url,
      url: `${testDomain}${url}`,
      referenceUrl: `${referenceDomain}${url}`,
      delay: 500,
      requireSameDimensions: true,
    }));
    config.scenarios = scenarios;

    // Write the config to a json file
    fs.writeFile('./backstop/backstop.json', JSON.stringify(config), (err) => {
      if (err) {
        console.log('File write failed:', err);
        return err;
      }

      // Inform the tester of next steps:
      console.log('Backstop config saved to ./backstop/backstop.json');
      return config;
    });
    return config;
  } catch (error) {
    console.error('Something went wrong', error);
    return error;
  }
})();
