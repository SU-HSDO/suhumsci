// Note: Colorful requires the new mega menu to complete screenshots.
module.exports = [
  {
    name: 'Colorful - Homepage',
    url: 'http://hs-colorful.suhumsci.loc/',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Homepage No JS',
    url: 'http://hs-colorful.suhumsci.loc/',
    waitForSelector: '.no-js',
    enableJavascript: false,
    widths: [1200]
  },
  {
    name: 'Colorful - Mobile Menu',
    url: 'http://hs-colorful.suhumsci.loc/',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    execute() { document.querySelector('.js-megamenu__mobile-btn').click() },
    additionalSnapshots: [{
      suffix: ' - Child open',
      execute() {
        document.querySelector('.js-megamenu__toggle').click();
      }
    }],
    widths: [768]
  },
  {
    name: 'Colorful - Desktop Menu',
    url: 'http://hs-colorful.suhumsci.loc/',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    execute() { document.querySelector('.js-megamenu__toggle').click() },
    widths: [1200]
  },
  {
    name: 'Colorful - Views - People',
    url: 'http://hs-colorful.suhumsci.loc/default-views/people',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Views - Publications',
    url: 'http://hs-colorful.suhumsci.loc/default-views/publications',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - News',
    url: 'http://hs-colorful.suhumsci.loc/news/jackelyn-hwang-wins-jane-addams-award-best-article-community-and-urban-sociology-section',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Events',
    url: 'http://hs-colorful.suhumsci.loc/events/colloquium-series/sociology-department-colloquium-robert-braun',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - People',
    url: 'http://hs-colorful.suhumsci.loc/people/abhinanda-sarkar',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Publications',
    url: 'http://hs-colorful.suhumsci.loc/publications/privilege-and-punishment-how-race-and-class-matter-criminal-court',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Collection - Examples',
    url: 'http://hs-colorful.suhumsci.loc/collection-real-world-examples',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Collection - Examples with Well',
    url: 'http://hs-colorful.suhumsci.loc/collection-real-world-examples-w-well',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Collection - Examples with Uniform and Stretch',
    url: 'http://hs-colorful.suhumsci.loc/collection-real-examples-uniform-and-stretch',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Hero Images Main Region',
    url: 'http://hs-colorful.suhumsci.loc/hero-images-main-region',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Hero Images Main Region - Gradient Slider',
    url: 'http://hs-colorful.suhumsci.loc/componentshero-images-main-region/hero-gradient-slider',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Hero Images Main Region - Hero Layered Slider',
    url: 'http://hs-colorful.suhumsci.loc/hero-images-main-region/hero-layered-slider',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Hero Images Main Region - Hero No Text',
    url: 'http://hs-colorful.suhumsci.loc/componentshero-images-main-region/hero-no-text',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Hero Images Main Region - Hero Overlay',
    url: 'http://hs-colorful.suhumsci.loc/componentshero-images-main-region/hero-overlay',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Hero Images Main Region - Tall Spotlight',
    url: 'http://hs-colorful.suhumsci.loc/componentshero-images-main-region/tall-spotlight',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Hero Images Main Region - Short Spotlight',
    url: 'http://hs-colorful.suhumsci.loc/componentshero-images-main-region/short-spotlight',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Photo Album',
    url: 'http://hs-colorful.suhumsci.loc/components/photo-album',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Text Area Typography',
    url: 'http://hs-colorful.suhumsci.loc/components/text-area-typography',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Vertical Timeline',
    url: 'http://hs-colorful.suhumsci.loc/components/vertical-timeline-view-using-events-ct',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Mixed Pages - Raised Cards',
    url: 'http://hs-colorful.suhumsci.loc/mixed-pages/mixed-page-raised-cards',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Mixed Pages - Mixed Page',
    url: 'http://hs-colorful.suhumsci.loc/mixed-pages/mixed-page',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Mixed Pages - Wells',
    url: 'http://hs-colorful.suhumsci.loc/mixed-pages/mixed-page-wells',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Colorful - Mixed Pages - Wells Raised Cards',
    url: 'http://hs-colorful.suhumsci.loc/mixed-pages/mixed-page-wells-raised-cards',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
]
