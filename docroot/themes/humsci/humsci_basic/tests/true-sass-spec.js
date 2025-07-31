var path = require('path');
var sassTrue = require('sass-true');
// Docs: https://github.com/oddbird/true

const sassFile = path.join(__dirname, './sass-specs/sass-tests.scss');
sassTrue.runSass(
  {
    file: sassFile,
    includePaths: [
      // Decanter uses Bourbon imports relative to this path
      'node_modules/bourbon/core',
      'node_modules',
    ],
  },
  {
    sass: require('sass'),
    describe,
    it,
  }
);
