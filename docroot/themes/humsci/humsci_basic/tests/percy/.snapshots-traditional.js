// Note: Traditional requires the original menu to complete screenshots.
module.exports = [
  {
    name: 'Traditional - Homepage',
    url: 'http://hs-traditional.suhumsci.loc/',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Homepage No JS',
    url: 'http://hs-traditional.suhumsci.loc/',
    waitForSelector: '.no-js',
    enableJavascript: false,
    widths: [1200]
  },
  {
    name: 'Traditional - Mobile Menu',
    url: 'http://hs-traditional.suhumsci.loc/',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    execute() { document.querySelector('.hb-main-nav__toggle').click() },
    additionalSnapshots: [{
      suffix: ' - Child open',
      execute() {
        document.querySelector('.hb-main-nav__button').click();
      }
    }],
    widths: [768]
  },
  {
    name: 'Traditional - Desktop Menu',
    url: 'http://hs-traditional.suhumsci.loc/',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    execute() { document.querySelector('.hb-main-nav__button').click() },
    widths: [1200]
  },
  {
    name: 'Traditional - Views - People',
    url: 'http://hs-traditional.suhumsci.loc/default-views/people',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Views - Publications',
    url: 'http://hs-traditional.suhumsci.loc/default-views/publications',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - News',
    url: 'http://hs-traditional.suhumsci.loc/news/jackelyn-hwang-wins-jane-addams-award-best-article-community-and-urban-sociology-section',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Events',
    url: 'http://hs-traditional.suhumsci.loc/events/colloquium-series/sociology-department-colloquium-robert-braun',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - People',
    url: 'http://hs-traditional.suhumsci.loc/people/abhinanda-sarkar',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Publications',
    url: 'http://hs-traditional.suhumsci.loc/publications/privilege-and-punishment-how-race-and-class-matter-criminal-court',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Collection - Examples',
    url: 'http://hs-traditional.suhumsci.loc/collection-real-world-examples',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Collection - Examples with Well',
    url: 'http://hs-traditional.suhumsci.loc/collection-real-world-examples-w-well',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Collection - Examples with Uniform and Stretch',
    url: 'http://hs-traditional.suhumsci.loc/collection-real-examples-uniform-and-stretch',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Hero Images Main Region',
    url: 'http://hs-traditional.suhumsci.loc/hero-images-main-region',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Hero Images Main Region - Gradient Slider',
    url: 'http://hs-traditional.suhumsci.loc/componentshero-images-main-region/hero-gradient-slider',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Hero Images Main Region - Hero Layered Slider',
    url: 'http://hs-traditional.suhumsci.loc/hero-images-main-region/hero-layered-slider',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Hero Images Main Region - Hero No Text',
    url: 'http://hs-traditional.suhumsci.loc/componentshero-images-main-region/hero-no-text',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Hero Images Main Region - Hero Overlay',
    url: 'http://hs-traditional.suhumsci.loc/componentshero-images-main-region/hero-overlay',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Hero Images Main Region - Tall Spotlight',
    url: 'http://hs-traditional.suhumsci.loc/componentshero-images-main-region/tall-spotlight',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Hero Images Main Region - Short Spotlight',
    url: 'http://hs-traditional.suhumsci.loc/componentshero-images-main-region/short-spotlight',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Photo Album',
    url: 'http://hs-traditional.suhumsci.loc/components/photo-album',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Text Area Typography',
    url: 'http://hs-traditional.suhumsci.loc/components/text-area-typography',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Vertical Timeline',
    url: 'http://hs-traditional.suhumsci.loc/components/vertical-timeline-view-using-events-ct',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Mixed Pages - Raised Cards',
    url: 'http://hs-traditional.suhumsci.loc/mixed-pages/mixed-page-raised-cards',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Collections cards uniform and stretch 1',
    url: 'https://hs-traditional.suhumsci.loc/collections-cards-uniform-and-stretch-1',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Mixed Pages - Mixed Page',
    url: 'http://hs-traditional.suhumsci.loc/mixed-pages/mixed-page',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Mixed Pages - Wells',
    url: 'http://hs-traditional.suhumsci.loc/mixed-page-wells',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
  {
    name: 'Traditional - Mixed Pages - Wells Raised Cards',
    url: 'http://hs-traditional.suhumsci.loc/mixed-pages/mixed-page-wells-raised-cards',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
]
