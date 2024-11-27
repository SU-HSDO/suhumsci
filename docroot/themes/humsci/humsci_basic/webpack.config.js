// Requires / Dependencies
const path = require('path');
const ESLintPlugin = require('eslint-webpack-plugin');

const srcDir = path.resolve(__dirname, 'src/js/');

// Shared JS files. Used in both traditional and colorful themes.
const shared = {
  accordion: 'shared/accordion/accordion-toggle-all.js',
  addtocal: 'shared/addtocal/addtocal.js',
};

// Colorful and traditional theme specific JS files.
const colorful = {};
const traditional = {};

module.exports = {
  mode: 'production',
  entry() {
    const entries = {};
    // Shared files have two entries, one for each theme.
    Object.keys(shared).forEach((key) => {
      const file = shared[key];
      entries[`humsci_traditional.${key}`] = path.resolve(srcDir, file);
      entries[`humsci_colorful.${key}`] = path.resolve(srcDir, file);
    });

    // Traditional theme specific files.
    Object.keys(traditional).forEach((key) => {
      const file = shared[key];
      entries[`humsci_traditional.${key}`] = path.resolve(srcDir, file);
    });

    // Colorful theme specific files.
    Object.keys(colorful).forEach((key) => {
      const file = shared[key];
      entries[`humsci_colorful.${key}`] = path.resolve(srcDir, file);
    });

    return entries;
  },
  plugins: [new ESLintPlugin()],
  output: {
    filename(pathData) {
      const [theme, filename] = pathData.chunk.name.split('.');
      return `${theme}/js/${filename}.js`;
    },
    path: path.resolve(__dirname, '../'),
  },
  module: {
    rules: [
      // Apply babel ES6 compilation to JavaScript files.
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
          },
        },
      },
    ],
  },
};
