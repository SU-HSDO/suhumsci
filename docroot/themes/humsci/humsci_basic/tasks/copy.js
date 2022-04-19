'use strict';
const shell = require('shelljs');

const copyPaths = [
  { from: './src/css/*.css', to: '../humsci_colorful/css' },
  { from: './src/css/*.css', to: '../humsci_airy/css' },
  { from: './src/css/*.css', to: '../humsci_traditional/css' }
];

copyPaths.forEach((path) => {
  console.log(`\nCopying ${path.from} ---> ${path.to}`);
  shell.mkdir('-p', path.to);
  shell.cp('-r', path.from, path.to);
});
