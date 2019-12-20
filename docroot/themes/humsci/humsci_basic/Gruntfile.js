const sass = require('sass');
const glob = require('glob');

module.exports = function (grunt) {

  function getIncludeFiles() {
    const patterns = [
      'src/**/*.scss',
      // Decanture uses Bourbon imports relative to this path
      'node_modules/bourbon/core',
      'node_modules',
    ];

    const libraries = [];
    patterns.map(function (pattern) {
      glob.sync(pattern).map(function (file) {
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
          interrupt: true
        }
      }
    },
    run: {
      stylelint: {
        exec: 'npm run lint:sass'
      }
    },
    postcss: {
      options: {
        map: false, // inline sourcemaps
        processors: [
          require('autoprefixer')({
            grid: true,
          }) // add vendor prefixes
        ]
      },
      dist: {
        src: [
          'dist/**/*.css'
        ]
      }
    },
    'dart-sass': {
      options: {
        implementation: sass,
        sourceMap: false,
        outputStyle: 'compressed',
        includePaths: getIncludeFiles()
      },
      dist: {
        files: [
          {
            expand: true,
            cwd: 'src',
            src: ['scss/**/[a-z]*.scss'],
            dest: 'dist',
            ext: '.css',
            extDot: 'last',
            rename: function (dest, src) {
              return dest + '/' + src.replace('scss', 'css');
            }
          }
        ]
      }
    },
  });

  grunt.loadNpmTasks('grunt-dart-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-run');

  grunt.registerTask('compile', ['dart-sass:dist', 'postcss:dist']);
};
