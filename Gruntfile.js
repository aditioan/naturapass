module.exports = function (grunt) {
    'use strict';
    // Force use of Unix newlines
    grunt.util.linefeed = '\n';
    var generateGlyphiconsData = require('./bower_components/bootstrap/grunt/bs-glyphicons-data-generator.js');
    // Project configuration.
    grunt.initConfig({
        pkg   : grunt.file.readJSON('package.json'),
        clean : {
            dist: ['<%= pkg.appCssDir %>', '<%= pkg.appJsDir %>']
        },
        concat: {
            bootstrap     : {
                src : [
                    '<%= pkg.bootstrapJsDir %>/transition.js',
                    '<%= pkg.bootstrapJsDir %>/alert.js',
                    '<%= pkg.bootstrapJsDir %>/button.js',
//                    '<%= pkg.bootstrapJsDir %>/carousel.js',
                    '<%= pkg.bootstrapJsDir %>/collapse.js',
                    '<%= pkg.bootstrapJsDir %>/dropdown.js',
                    '<%= pkg.bootstrapJsDir %>/modal.js',
                    '<%= pkg.bootstrapJsDir %>/tooltip.js',
                    '<%= pkg.bootstrapJsDir %>/popover.js',
//                    '<%= pkg.bootstrapJsDir %>/scrollspy.js',
                    '<%= pkg.bootstrapJsDir %>/tab.js'
//                    '<%= pkg.bootstrapJsDir %>/affix.js'
//
                ],
                dest: '<%= pkg.appJsDir %>/<%= pkg.name %>.js'
            },
            fileupload    : {
                src : [
                    '<%= pkg.fileuploadDir %>/vendor/jquery.ui.widget.js',
                    '<%= pkg.loadimageDir %>/load-image.js',
                    '<%= pkg.loadimageDir %>/load-image-ios.js',
                    '<%= pkg.loadimageDir %>/load-image-meta.js',
                    '<%= pkg.loadimageDir %>/load-image-exif.js',
                    '<%= pkg.loadimageDir %>/load-image-orientation.js',
                    '<%= pkg.canvastoblobDir %>/canvas-to-blob.js',
                    '<%= pkg.fileuploadDir %>/jquery.iframe-transport.js',
                    '<%= pkg.fileuploadDir %>/jquery.fileupload.js',
                    '<%= pkg.fileuploadDir %>/jquery.fileupload-process.js',
                    '<%= pkg.fileuploadDir %>/jquery.fileupload-image.js',
                    '<%= pkg.fileuploadDir %>/jquery.fileupload-audio.js',
                    '<%= pkg.fileuploadDir %>/jquery.fileupload-video.js',
                    '<%= pkg.fileuploadDir %>/jquery.fileupload-validate.js',
                    '<%= pkg.fileuploadDir %>/jquery.fileupload-angular.js'
                ],
                dest: '<%= pkg.appJsDir %>/fileupload.js'
            },
            plugin        : {
                src : [
                    '<%= pkg.momentDir %>/moment.js',
                    '<%= pkg.momentDir %>/locale/fr.js',
                    '<%= pkg.momentDir %>/locale/en.js',
                    '<%= pkg.appJsMainDir %>/jquery.cookies.2.2.0.min.js',
                    '<%= pkg.fancybox %>/jquery.fancybox.pack.js',
                    '<%= pkg.appJsMainDir %>/plugins.js',
                    '<%= pkg.appJsMainDir %>/jquery.clickout.js',
                    '<%= pkg.bxsliderDir %>/jquery.bxslider.js',
                    '<%= pkg.underscroreDir %>/underscore.js',
                    '<%= pkg.visibleDir %>/jquery.visible.js',
                    '<%= pkg.mousewheelDir %>/jquery.mousewheel.js',
                    '<%= pkg.scrollbarDir %>/jquery.mCustomScrollbar.js',
                    '<%= pkg.jpanelmenuDir %>/jquery.jpanelmenu.js',
                    '<%= pkg.touchswipeDir %>/jquery.touchSwipe.js',
                    '<%= pkg.select2Dir %>/select2.js',
                    '<%= pkg.select2Dir %>/select2_locale_fr.js',
                    '<%= pkg.select2Dir %>/select2_locale_en.js',
                    '<%= pkg.datepickerJsDir %>/bootstrap-datetimepicker.js', // Traduction anglaise déjà dedans
                    '<%= pkg.datepickerJsDir %>/locales/bootstrap-datetimepicker.fr.js',
                    '<%= pkg.angularDir %>/angular.js',
                    '<%= pkg.bowerDir %>/angular-route/angular-route.js',
                    '<%= pkg.bowerDir %>/ng-facebook/ngFacebook.js',
                    '<%= pkg.bowerDir %>/angular-bootstrap/ui-bootstrap-tpls.js',
                    '<%= pkg.ngprogress %>/build/ngProgress.js',
                    '<%= pkg.angularInfiniteScrolllDir %>/ng-infinite-scroll.js',
                    '<%= pkg.appJsDir %>/fileupload.js',
                    '<%= pkg.bowerDir %>/videojs/dist/video-js/video.dev.js',
                    '<%= pkg.bowerDir %>/jcrop/js/jquery.Jcrop.js',
                    '<%= pkg.bowerDir %>/angular-cookie/angular-cookie.js',
                    '<%= pkg.angularTree %>/dist/angular-ui-tree.js',
                    '<%= pkg.angularSelect2 %>/dist/select.js',
                    '<%= pkg.angularChecklistModel %>/checklist-model.js',
                    '<%= pkg.bowerDir %>/bootstrap-colorselector/lib/bootstrap-colorselector-0.2.0/js/bootstrap-colorselector.js',
                    '<%= pkg.bowerDir %>/jquery-qubit/jquery.qubit.js',
                    '<%= pkg.bowerDir %>/angular-tree-control/angular-tree-control.js',
                    //'<%= pkg.bowerDir %>/datatables/media/js/jquery.dataTables.min.js',
                    //'<%= pkg.bowerDir %>/datatables/media/js/dataTables.bootstrap.min.js',
                ],
                dest: '<%= pkg.appJsDir %>/plugin.js'
            },
            scriptcam     : {
                src : [
                    '<%= pkg.swfobjectDir %>/swfobject.js',
                    '<%= pkg.appJsMainDir %>/scriptcam.js'
                ],
                dest: '<%= pkg.appJsDir %>/scriptcam.js'
            },
            angulargmap   : {
                src : [
                    '<%= pkg.bowerDir %>/angular-google-maps-init/dist/angular-google-maps.js',
                    '<%= pkg.appJsMainDir %>/app/services/maputils.js',
                    '<%= pkg.bowerDir %>/lodash-init/dist/lodash.js',
                    '<%= pkg.appJsMainDir %>/oms.min.js',
                    '<%= pkg.appJsMainDir %>/target.overlay.js'
                ],
                dest: '<%= pkg.appJsDir %>/angular-gmap.js'
            },
            angulargmapnew: {
                src : [
                    '<%= pkg.bowerDir %>/angular-google-maps/dist/angular-google-maps.js',
                    '<%= pkg.bowerDir %>/lodash/dist/lodash.js',
                ],
                dest: '<%= pkg.appJsDir %>/angular-gmap-new.js'
            },
            translation_fr: {
                src : [
                    '<%= pkg.appJsTranslationsDir %>/*/fr.js'
                ],
                dest: '<%= pkg.appJsDir %>/translations.fr.js'
            }
        },
        uglify: {
            options       : {
                report: 'min'
            },
            bootstrap     : {
                src : '<%= concat.bootstrap.dest %>',
                dest: '<%= pkg.appJsDir %>/<%= pkg.name %>.min.js'
            },
            plugin        : {
                src : '<%= pkg.appJsDir %>/plugin.js',
                dest: '<%= pkg.appJsDir %>/plugin.min.js'
            },
            fileupload    : {
                src : '<%= pkg.appJsDir %>/fileupload.js',
                dest: '<%= pkg.appJsDir %>/fileupload.min.js'
            },
            scriptcam     : {
                src : '<%= pkg.appJsDir %>/scriptcam.js',
                dest: '<%= pkg.appJsDir %>/scriptcam.min.js'
            },
            main          : {
                src : '<%= pkg.appJsMainDir %>/main.js',
                dest: '<%= pkg.appJsMainDir %>/main.min.js'
            },
            angulargmap   : {
                src : '<%= pkg.appJsDir %>/angular-gmap.js',
                dest: '<%= pkg.appJsDir %>/angular-gmap.min.js'
            },
            angulargmapnew: {
                src : '<%= pkg.appJsDir %>/angular-gmap-new.js',
                dest: '<%= pkg.appJsDir %>/angular-gmap-new.min.js'
            },
            translation_fr: {
                src : '<%= pkg.appJsDir %>/translations.fr.js',
                dest: '<%= pkg.appJsDir %>/translations.fr.min.js'
            }
        },
        less  : {
            compileCore   : {
                options: {
                    strictMath: true
                },
                files  : {
                    '<%= pkg.appCssDir %>/<%= pkg.name %>.css': [
                        '<%= pkg.appBootstrapLessDir %>/bootstrap.less',
                        '<%= pkg.appLessDir %>/icomoon.less',
                        '<%= pkg.datepickerLessDir %>/datetimepicker.less',
                        '<%= pkg.bowerDir %>/videojs/dist/video-js/video-js.less'
                    ]
                }
            },
            compileTheme  : {
                options: {
                    strictMath: true
                },
                files  : {
                    '<%= pkg.appCssDir %>/<%= pkg.name %>-theme.css': '<%= pkg.appBootstrapLessDir %>/theme.less'
                }
            },
            compileSelect2: {
                options: {
                    strictMath: true
                },
                files  : {
                    '<%= pkg.appCssDir %>/select2-bootstrap.css': '<%= pkg.select2LessDir %>/select2-bootstrap.less'
                }
            },
            minify        : {
                options: {
                    cleancss: true,
                    compress: true,
                    report  : 'min'
                },
                files  : {
                    '<%= pkg.appCssDir %>/<%= pkg.name %>.min.css'      : [
                        '<%= pkg.appCssDir %>/<%= pkg.name %>.css',
                        '<%= pkg.ngprogress %>/ngProgress.css',
                        '<%= pkg.appCssMainDir %>/jquery.fancybox.css',
                        '<%= pkg.scrollbarDir %>/jquery.mCustomScrollbar.css',
                        '<%= pkg.select2Dir %>/select2.css',
                        '<%= pkg.appCssDir %>/select2-bootstrap.css',
                        '<%= pkg.bowerDir %>/jcrop/css/jquery.Jcrop.css',
                        '<%= pkg.angularTree %>/dist/angular-ui-tree.min.css',
                        '<%= pkg.angularSelect2 %>/dist/select.min.css',
                        '<%= pkg.bowerDir %>/angular-tree-control/css/tree-control.css',
                        '<%= pkg.bowerDir %>/angular-tree-control/css/tree-control-attribute.css',
                        '<%= pkg.bowerDir %>/bootstrap-colorselector/lib/bootstrap-colorselector-0.2.0/css/bootstrap-colorselector.css',
                        //'<%= pkg.bowerDir %>/datatables/media/css/jquery.dataTables.css',
                        //'<%= pkg.bowerDir %>/datatables/media/css/dataTables.bootstrap.css',
                    ],
                    '<%= pkg.appCssDir %>/<%= pkg.name %>-theme.min.css': '<%= pkg.appCssDir %>/<%= pkg.name %>-theme.css'
                }
            }
        },
        copy  : {
            jquery   : {
                expand : true,
                cwd    : '<%= pkg.jqueryDir %>/',
                src    : 'jquery.min.js',
                dest   : '<%= pkg.appJsDir %>/',
                flatten: true,
                filter : 'isFile'
            },
            bootstrap: {
                expand : true,
                cwd    : '<%= pkg.bootstrapFontsDir %>/',
                src    : '**',
                dest   : '<%= pkg.appFontsDir %>/',
                flatten: true,
                filter : 'isFile'
            },
            maps     : {
                expand : true,
                cwd    : '<%= pkg.mapsDir %>/',
                src    : '**',
                dest   : '<%= pkg.appJsDir %>/',
                flatten: true,
                filter : 'isFile'
            },
            videojs  : {
                expand : true,
                cwd    : '<%= pkg.bowerDir %>/videojs/dist/font/',
                src    : '**',
                dest   : '<%= pkg.appFontsDir %>/',
                flatten: true,
                filter : 'isFile'
            }
        },
        watch : {
            less: {
                files: [
                    '<%= pkg.bootstrapLessDir %>/*.less',
                    '<%= pkg.appLessDir %>/*.less',
                    '<%= pkg.appBootstrapLessDir %>/*.less',
                    '<%= pkg.datepickerLessDir %>/*.less',
                    '<%= pkg.select2LessDir %>/*.less'
                ],
                tasks: 'less'
            },
            js  : {
                files: '<%= pkg.appJsMainDir %>/main.js',
                tasks: 'uglify:main'
            }
        }
    });
    // Load the plugin
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-imagemin');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    // JS distribution task.
    grunt.registerTask('dist-js', ['concat', 'uglify']);
    // CSS distribution task.
    grunt.registerTask('dist-css', ['less']);
    // Full distribution task.
    grunt.registerTask('dist', ['clean', 'dist-css', 'copy', 'dist-js']);
    // Default task.
    grunt.registerTask('default', ['dist']);
};