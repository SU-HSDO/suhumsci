var path = require('path');
var sassTrue = require('sass-true');
// Docs: https://github.com/oddbird/true

const sassFile = path.join(__dirname, './sass-specs/sass-tests.scss');
sassTrue.runSass({ file: sassFile }, { describe, it });
 