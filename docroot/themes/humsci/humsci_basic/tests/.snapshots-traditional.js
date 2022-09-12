// TODO: approve test on colorful make sure doesn't conflict
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
    name: 'Traditional - Mixed Page',
    url: 'http://hs-traditional.suhumsci.loc/mixed-pages/mixed-page',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
]
