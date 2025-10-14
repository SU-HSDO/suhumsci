const path = require('node:path');
const sassTrue = require('sass-true');

const sassFile = path.join(__dirname, './sass-specs/sass-tests.scss');

sassTrue.runSass(
  {
    describe,
    it,
    sass: require('sass'),
  },
  sassFile,
  {
    loadPaths: [
      'node_modules/bourbon/core',
      'node_modules',
    ],
  }
);
