const path = require('path');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
  entry: path.resolve(__dirname, 'src', 'index.js'),
  output: {
    path: path.resolve(__dirname, 'build'),
    filename: 'search-app.js',
    iife: true,
  },
  devtool: false,
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: { presets: ['@babel/preset-env'] },
        },
      },
    ],
  },
  optimization: {
    minimizer: [
      new TerserPlugin({
        terserOptions: { format: { comments: false } },
        extractComments: false,
      }),
    ],
  },
  performance: {
    // InstantSearch and the Algolia client are bundled on purpose, and the
    // library only loads on the search page.
    hints: false,
  },
};
