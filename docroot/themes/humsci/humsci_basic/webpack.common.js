import autoPrefixer from 'autoprefixer';
import ESLintPlugin from 'eslint-webpack-plugin';
import { globSync } from 'fs';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import path from 'path';
import { fileURLToPath } from 'url';
import RemoveEmptyScriptsPlugin from 'webpack-remove-empty-scripts';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const jsSrcDir = path.resolve(__dirname, 'src/js/');

// Shared JS files. Used in both traditional and colorful themes.
const shared = {
  init: 'shared/init/init.js',
  accordion: 'shared/accordion/accordion-toggle-all.js',
  addtocal: 'shared/addtocal/addtocal.js',
  'page-scroll-animations': 'shared/animation/page-scroll.js',
  'carousel-slides-height': 'shared/carousel-slides/carousel-slides-height.js',
  editoria11y: 'shared/editoria11y/editoria11y.js',
  'equal-height-grid': 'shared/equal-height-grid/index.js',
  'linked-cards': 'shared/cards/linked-cards.js',
  'structured-card': 'shared/cards/structured-card.js',
  'main-content-fallback':
    'shared/main-content-fallback/main-content-fallback.js',
  'video-with-caption': 'shared/media/video-with-caption.js',
  'lazy-load-video': 'shared/media/lazy-load-video.js',
  megamenu: 'shared/megamenu/index.js',
  'main-menu': 'shared/navigation/main-menu-index.js',
  'media-caption-toggle': 'shared/media/media-caption-toggle.js',
  'secondary-toggler': 'shared/navigation/secondary-toggler.js',
  colorbox: 'shared/photo-album/colorbox.js',
  search: 'shared/search/search-expand.js',
  'swiper-reduced-motion': 'shared/carousel-slides/swiper-reduced-motion.js',
  'table-scope': 'shared/tables/scope.js',
  'table-pattern': 'shared/tables/table-pattern.js',
  'table-wrap': 'shared/tables/wrap.js',
  timeline: 'shared/timeline/expand-collapse-timeline.js',
};

// Colorful and traditional theme specific JS files.
const colorful = {};
const traditional = {};

const sassFiles = globSync('src/scss/**/*.scss', {
  exclude: ['src/scss/partials/**/*.scss'],
});

export default {
  entry() {
    const entries = {};
    // Shared files have two entries, one for each theme.
    Object.keys(shared).forEach((key) => {
      const file = shared[key];
      entries[`js/shared/${key}`] = path.resolve(jsSrcDir, file);
    });

    // Traditional theme specific files.
    Object.keys(traditional).forEach((key) => {
      const file = shared[key];
      entries[`js/humsci_traditional/${key}`] = path.resolve(jsSrcDir, file);
    });

    // Colorful theme specific files.
    Object.keys(colorful).forEach((key) => {
      const file = shared[key];
      entries[`js/humsci_colorful/${key}`] = path.resolve(jsSrcDir, file);
    });

    // Sass files.
    sassFiles.forEach((file) => {
      const pathParts = file.split('/');
      const filename = pathParts.pop().split('.')[0];
      const themeName = pathParts.pop();
      entries[`css/${themeName}/${filename}`] = `./${file}`;
    });

    return entries;
  },
  plugins: [
    new ESLintPlugin(),
    new MiniCssExtractPlugin({
      filename(pathData) {
        const [_, themePath, filename] = pathData.chunk.name.split('/');
        return `../${themePath}/css/${filename}.css`;
      },
    }),
    new RemoveEmptyScriptsPlugin(),
  ],
  output: {
    filename(pathData) {
      const [fileType, theme, filename] = pathData.chunk.name.split('/');
      const themePath = theme === 'shared' ? 'dist' : `../${theme}`;
      return `${themePath}/${fileType}/${filename}.js`;
    },
    path: path.resolve(__dirname, '.'),
  },
  module: {
    rules: [
      // Apply babel ES6 compilation to JavaScript files.
      {
        test: /\.m?js$/,
        exclude: /node_modules/,
        resolve: {
          fullySpecified: false,
        },
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
          },
        },
      },
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              url: false,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: true,
              postcssOptions: {
                plugins: [autoPrefixer()],
              },
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true,
              sassOptions: {
                outputStyle: 'compressed',
                quietDeps: true,
              },
            },
          },
        ],
      },
    ],
  },
};
