const path = require("path");
const webpack = require("webpack");
const { styles, builds } = require("@ckeditor/ckeditor5-dev-utils");
const TerserPlugin = require("terser-webpack-plugin");

module.exports = [];

const bc = {
  mode: "production",
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          format: {
            comments: false,
          },
        },
        test: /\.js(\?.*)?$/i,
        extractComments: false,
      }),
    ],
    moduleIds: "named",
  },
  entry: {
    path: path.resolve(__dirname, "./src", "index.js"),
  },
  output: {
    path: path.resolve(__dirname, "./build"),
    filename: "anchor-link-help.js",
    library: ["CKEditor5", "anchorLinkHelp"],
    libraryTarget: "umd",
    libraryExport: "default",
  },
  plugins: [
    // It is possible to require the ckeditor5-dll.manifest.json used in
    // core/node_modules rather than having to install CKEditor 5 here.
    // However, that requires knowing the location of that file relative to
    // where your module code is located.
    new webpack.DllReferencePlugin({
      manifest: require("./node_modules/ckeditor5/build/ckeditor5-dll.manifest.json"), // eslint-disable-line global-require, import/no-unresolved
      scope: "ckeditor5/src",
      name: "CKEditor5.dll",
    }),
  ],
  module: {
    rules: [
      {
        test: /ckeditor5-[^/\\]+[/\\]theme[/\\].+\.css$/,
        use: [
          {
            loader: "style-loader",
            options: {
              injectType: "singletonStyleTag",
              attributes: {
                "data-cke": true,
              },
            },
          },
        ],
      },
    ],
  },
};

module.exports.push(bc);
