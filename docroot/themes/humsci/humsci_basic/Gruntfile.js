const sass = require('sass');
const glob = require('glob');
const tilde_importer = require('grunt-sass-tilde-importer');

module.exports = function (grunt) {
  function getIncludeFiles() {
    const patterns = [
      'src/**/*.scss',
      // Decanter uses Bourbon imports relative to this path
      'node_modules/bourbon/core',
      'node_modules',
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
        tasks: ['dart-sass:dist', 'dart-sass:ckeditor'],
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
            src: ['scss/**/[a-z]*.scss', '!scss/ckeditor/[a-z]*.scss'],
            dest: '../',
            ext: '.css',
            extDot: 'last',
            rename(dest, src) {
              const filenameRegularExpression = /\w+(?=\.)/;
              const themeName = src.match(filenameRegularExpression)[0];
              return `${dest}${themeName}/css/${themeName}.css`;
            },
          },
        ],
      },
      ckeditor: {
        files: [
          {
            expand: true,
            cwd: 'src',
            src: ['scss/ckeditor/[a-z]*.scss', '!scss/ckeditor/imports.scss'],
            dest: '../',
            ext: '.css',
            extDot: 'last',
            rename(dest, src) {
              const filenameRegularExpression = /\w+(?=\.)/;
              const themeName = src.match(filenameRegularExpression)[0];
              return `${dest}${themeName}/css/${themeName}-ckeditor.css`;
            },
          }
        ],
      },
    },
  });

  grunt.loadNpmTasks('grunt-dart-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-run');

  grunt.registerTask('compile', ['dart-sass:dist', 'postcss:dist']);
  grunt.registerTask('ckeditor', ['dart-sass:ckeditor', 'postcss:dist']);
};
