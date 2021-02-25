/**
 * Created by vincentvalot on 14/05/14.
 */

require.config({
    baseUrl: '/js/app',
    paths: {
        'angular': '/js/angular',
        'angular-route': '/js/angular-route'
    },
    shim: {
        'app': {
            deps: ['angular', 'angular-route']
        },
        'angular-route': {
            deps: ['angular']
        }
    }
});

require
(
    [
        'app'
    ],
    function(app) {
        angular.bootstrap(document, ['app']);
    }
);