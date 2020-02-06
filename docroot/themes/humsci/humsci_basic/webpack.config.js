 // Requires / Dependencies
 const path = require('path');
 const webpack = require('webpack');

const srcDir = path.resolve( __dirname, 'src/' );
 
 module.exports = {
  entry: srcDir + "/js/index.js",
  output: {
    path: srcDir + "/js/build/",
    filename: "scripts.js"
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
            presets: [ '@babel/preset-env' ]
          }
        }
      }
    ]
  }
};