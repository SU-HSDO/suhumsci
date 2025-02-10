// Requires / Dependencies
const path = require('path');
const ESLintPlugin = require('eslint-webpack-plugin');

const srcDir = path.resolve(__dirname, 'src/js/');

// Shared JS files. Used in both traditional and colorful themes.
const shared = {
  accordion: 'shared/accordion/accordion-toggle-all.js',
  addtocal: 'shared/addtocal/addtocal.js',
  'page-scroll-animations': 'shared/animation/page-scroll.js',
  'carousel-slides-height': 'shared/carousel-slides/carousel-slides-height.js',
  editoria11y: 'shared/editoria11y/editoria11y.js',
  'equal-height-grid': 'shared/equal-height-grid/index.js',
  'linked-cards': 'shared/cards/linked-cards.js',
  'main-content-fallback': 'shared/main-content-fallback/main-content-fallback.js',
  'video-with-caption': 'shared/media/video-with-caption.js',
  megamenu: 'shared/megamenu/index.js',
  'main-menu': 'shared/navigation/main-menu-index.js',
  'secondary-toggler': 'shared/navigation/secondary-toggler.js',
  colorbox: 'shared/photo-album/colorbox.js',
  'prefered-reduced-motion': 'shared/prefered-reduced-motion/prefered-reduced-motion.js',
  search: 'shared/search/search-expand.js',
  'table-scope': 'shared/tables/scope.js',
  'table-pattern': 'shared/tables/table-pattern.js',
  'table-wrap': 'shared/tables/wrap.js',
  timeline: 'shared/timeline/expand-collapse-timeline.js',
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
      entries[`shared.${key}`] = path.resolve(srcDir, file);
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
      const themePath = theme === 'shared' ? 'dist/js' : `../${theme}/js`;
      return `${themePath}/${filename}.js`;
    },
    path: path.resolve(__dirname, '.'),
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
