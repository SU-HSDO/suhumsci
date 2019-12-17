var path = require('path');
var sassTrue = require('sass-true');
// Docs: https://github.com/oddbird/true

const sassFile = path.join(__dirname, './sass-specs/sass-tests.scss');
sassTrue.runSass(
  {
    file: sassFile,
    includePaths: [
      // Decanture uses Bourbon imports relative to this path
      'node_modules/bourbon/core',
      'node_modules',
    ],
  },
  { describe, it }
);
