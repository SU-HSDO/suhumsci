module.exports = function (grunt) {

  const sass = require('node-sass');
  const glob = require('glob');

  function getIncludeFiles() {
    var patterns = [
      'docroot/themes/humsci/**/*.scss',
      'docroot/modules/humsci/**/*.scss',
      'node_modules/decanter/scss',
      'node_modules/bourbon/core',
      'node_modules/bourbon-neat/app/assets/stylesheets',
      'node_modules/neat-omega/core'
    ];

    var libraries = [];
    patterns.map(function(pattern){
      glob.sync(pattern).map(function(file){
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
            cwd: 'docroot',
            src: ['modules/humsci/**/[a-z]*.scss', 'themes/humsci/**/[a-z]*.scss'],
            dest: 'docroot',
            ext: '.css',
            extDot: 'last',
            rename: function (dest, src) {

              return dest + '/' + src.replace('scss', 'css');
            }
          }
        ]
      }
    },
    availabletasks: {
      tasks: {
        options: {
          filter: "include",
          tasks: [
            'sass', 'watch', 'devmode'
          ]
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');

  grunt.registerTask('default', ['availabletasks']);
  grunt.registerTask('mike', ['stuff',], function () {

//     glob("config/default/*yml", {}, function (er, files) {
// console.log(files);
//     })
    console.log(glob.sync('**/decanter/scss').push('stuff'));
  })
};
