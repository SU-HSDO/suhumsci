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
    name: 'Colorful - Mixed Page',
    url: 'http://hs-colorful.suhumsci.loc/mixed-pages/mixed-page',
    waitForSelector: '.js',
    waitForTimeout: 1500,
    widths: [576, 768, 1200]
  },
]
