const sass = require('node-sass');
const glob = require('glob');

module.exports = function (grunt) {

  function getIncludeFiles() {
    const patterns = [
      '**/*.scss',
      'node_modules/decanter/scss',
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
        files: ['**/*.{scss,sass}'],
        tasks: ['sass'],
        options: {
          interrupt: true
        }
      }
    },
    postcss: {
      options: {
        map: true, // inline sourcemaps
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
    sass: {
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

  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-postcss');

  grunt.registerTask('compile', ['sass:dist', 'postcss:dist']);
};
