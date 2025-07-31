const sass = require('sass');
const glob = require('glob');
const tilde_importer = require('grunt-sass-tilde-importer');

module.exports = function (grunt) {
  function getIncludeFiles() {
    const patterns = [
      'src/**/*.scss',
    ];

    const libraries = [];
    patterns.map((pattern) => {
      glob.sync(pattern).map((file) => {
        libraries.push(file);
      });
    });

    return libraries;
  }

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    watch: {
      css: {
        files: ['src/**/*.{scss,sass}'],
        tasks: ['dart-sass:dist'],
        options: {
          interrupt: true,
        },
      },
    },
    run: {
      stylelint: {
        exec: 'npm run lint:sass',
      },
    },
    postcss: {
      options: {
        map: false, // inline sourcemaps
        processors: [
          require('autoprefixer')({
            grid: true,
          }), // add vendor prefixes
        ],
      },
      dist: {
        src: [
          '../humsci_colorful/**/*.css',
          '../humsci_airy/**/*.css',
          '../humsci_traditional/**/*.css',
        ],
      },
    },
    'dart-sass': {
      options: {
        implementation: sass,
        sourceMap: false,
        outputStyle: 'compressed',
        importer: tilde_importer,
        includePaths: getIncludeFiles(),
        quietDeps: true,
      },
      dist: {
        files: [
          {
            expand: true,
            cwd: 'src',
            src: 'scss/**/[a-z]*.scss',
            dest: '../',
            ext: '.css',
            extDot: 'last',
            rename(dest, src) {
              const path = src.split('/');
              const filename = path.pop().split('.')[0];
              const themeName = path.pop();
              return `${dest}${themeName}/css/${filename}.css`;
            },
          },
        ],
      },
    },
  });

  grunt.loadNpmTasks('grunt-dart-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-run');

  grunt.registerTask('compile', ['dart-sass:dist', 'postcss:dist']);
};
