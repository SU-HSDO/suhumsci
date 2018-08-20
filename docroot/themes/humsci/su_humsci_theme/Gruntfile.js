/**
 * @file
 */
module.exports = function(grunt) {

  // This is where we configure each task that we'd like to run.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    watch: {
      // This is where we set up all the tasks we'd like grunt to watch for changes.
      scripts: {
        files: ['js/source/**/*.js'],
        tasks: ['uglify', 'drush:ccall'],
        options: {
          spawn: false,
        },
      },
      images: {
        files: ['images/source/*.{png,jpg,gif}'],
        tasks: ['imagemin'],
        options: {
          spawn: false,
        }
      },
      vector: {
        files: ['images/source/**/*.svg'],
        tasks: ['svgmin'],
        options: {
          spawn: false,
        }
      },
      css: {
        files: ['scss/**/*.scss', 'scss/**/**/*.scss','patterns/**/**/scss/*.scss'],
        tasks: ['sass'],
        options: {
          interrupt: true
        }
      },
      twig: {
        files: ['templates/**/*.html.twig'],
        tasks: ['uglify', 'svgmin', 'imagemin', 'sass', 'drush:ccall']
      }
    },
    uglify: {
      // This is for minifying all of our scripts.
      options: {
        sourceMap: true,
        mangle: false
      },
      my_target: {
        files: [{
          expand: true,
          cwd: 'js/source',
          src: '{,*/}*.js',
          dest: 'js/build'
        }]
      }
    },
    imagemin: {
      // This will optimize all of our images for the web.
      dynamic: {
        files: [{
          expand: true,
          cwd: 'img/source/',
          src: ['{,*/}*.{png,jpg,gif}' ],
          dest: 'img/optimized/'
        }]
      }
    },
    svgmin: {
      options: {
        plugins: [{
          removeViewBox: false
        }, {
          removeUselessStrokeAndFill: false
        }]
      },
      dist: {
        files: [{
          expand: true,
          cwd: 'images/source/',
          src: ['{,*/}*.svg' ],
          dest: 'images/optimized/'
        }]
      }
    },
    sass: {
      // This will compile all of our sass files
      // Additional configuration options can be found at https://github.com/sindresorhus/grunt-sass
      options: {
        includePaths: [
          "scss",
          "node_modules/bourbon/core",
          "node_modules/bourbon-neat/app/assets/stylesheets",
          "node_modules/neat-omega/core",
          "node_modules/decanter/scss",
          "node_modules",
        ],
        sourceMap: true,
        // This controls the compiled css and can be changed to nested, compact or compressed.
        outputStyle: 'compressed',
        precision: 10
      },
      dist: {
        files: {
          // Files are compiled individually so they may be included
          // conditionally using logic built in to the theme template or module.

          // BASE
          'css/base/index.css': 'scss/base/index.scss',
          'css/ckeditor.css':   'scss/ckeditor.scss',

          // PATTERNS
          'patterns/molecules/date-stacked-vertical-card/css/date-stacked-vertical-card.css': 'patterns/molecules/date-stacked-vertical-card/scss/date-stacked-vertical-card.scss',
          'patterns/molecules/horizontal-card/css/horizontal-card.css':                       'patterns/molecules/horizontal-card/scss/horizontal-card.scss',
          'patterns/molecules/masonry-item/css/masonry-item.css':                             'patterns/molecules/masonry-item/scss/masonry-item.scss',
          'patterns/molecules/vertical-card/css/vertical-card.css':                           'patterns/molecules/vertical-card/scss/vertical-card.scss',
          'patterns/molecules/vertical-link-card/css/vertical-link-card.css':                 'patterns/molecules/vertical-link-card/scss/vertical-link-card.scss',
          'patterns/molecules/table-row/css/table-row.css':                                   'patterns/molecules/table-row/scss/table-row.scss',
          'patterns/organisms/table-pattern/css/table-pattern.css':                           'patterns/organisms/table-pattern/scss/table-pattern.scss',
          'patterns/organisms/masonry/css/masonry.css':                                       'patterns/organisms/masonry/scss/masonry.scss',

          // COMPONENTS
          'css/components/atoms/atoms.css':         'scss/components/atoms/index.scss',
          'css/components/molecules/molecules.css': 'scss/components/molecules/index.scss',

          // NODES
          'css/nodes/hs_basic_page.css':  'scss/nodes/hs_basic_page.scss',
          'css/nodes/hs_person.css':      'scss/nodes/hs_person.scss',

          // CUSTOM PROJECTS
          'css/custom/archaeology/archaeology.css': 'scss/custom/archaeology/archaeology.scss',
        }
      }
    },
    drush: {
      ccall: {
        args: ['cache-rebuild', 'all']
      }
    },
    browserSync: {
      dev: {
        bsFiles: {
          src : [
            'css/**/*.css',
            'templates/**/*.twig',
            'images/optimized/**/*.{png,jpg,gif,svg}',
            'js/build/**/*.js',
            '*.theme'
          ]
        },
        options: {
          watchTask: true,
          // reloadDelay: 1000,
          // reloadDebounce: 500,
          reloadOnRestart: true,
          logConnections: true,
          injectChanges: false // Depends on enabling the link_css module
        }
      }
    },
    availabletasks: {
      tasks: {
        options: {
          filter: "include",
          tasks: [
            'browserSync', 'imagemin', 'sass', 'svgmin', 'uglify', 'watch', 'devmode'
          ]
        }
      }
    }
  });

  // This is where we tell Grunt we plan to use this plug-in.
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-imagemin');
  grunt.loadNpmTasks('grunt-svgmin');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-browser-sync');
  grunt.loadNpmTasks('grunt-available-tasks');
  grunt.loadNpmTasks('grunt-drush');

  // My tasks.
  grunt.registerTask('devmode', "Watch and BrowserSync all in one.", ['browserSync', 'watch']);

  // This is where we tell Grunt what to do when we type "grunt" into the terminal.
  // Note: if you'd like to run and of the tasks individually you can do so by typing 'grunt mytaskname' alternatively
  // you can type 'grunt watch' to automatically track your files for changes.
  grunt.registerTask('default', ['availabletasks']);
};
