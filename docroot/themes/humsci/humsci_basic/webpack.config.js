// Requires / Dependencies
const path = require('path');
const ESLintPlugin = require('eslint-webpack-plugin');

const srcDir = path.resolve(__dirname, 'src/');

module.exports = {
  mode: 'production',
  entry: {
    humsci_colorful: `${srcDir}/js/colorful/colorful.js`,
    humsci_traditional: `${srcDir}/js/traditional/traditional.js`,
  },
  plugins: [new ESLintPlugin()],
  output: {
    filename: (pathData) => `${pathData.chunk.name}/js/index.js`,
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
