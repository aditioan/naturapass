/**
 * Created by vincentvalot on 14/05/14.
 */

angular.module('infinite-scroll').value('THROTTLE_MILLISECONDS', 250);

var app = angular.module('app', [
    'naturapass.directives',
    'ngRoute',
    'ngFacebook',
    'ipCookie',
    'blueimp.fileupload',
    'infinite-scroll',
    'ui.bootstrap',
    'google-maps',
    'ui.tree',
    'ui.select',
    "checklist-model",
    'treeControl',
])
    .config(['$routeProvider', '$locationProvider', function ($routeProvider, $locationProvider) {
        $locationProvider.html5Mode(false);
    }])
    .config(['$facebookProvider', function ($facebookProvider) {
        $facebookProvider.setAppId($('html').data("fid"));
        $facebookProvider.setPermissions('public_profile,email,publish_actions,user_birthday,user_friends');
        $facebookProvider.setCustomInit({
            channelUrl: Routing.generate('naturapass_main_fbchannel'),
            xfbml     : true,
            version   : $('html').data("fvers")
        });
    }])

    .config(['$httpProvider', function ($httpProvider) {
        $httpProvider.defaults.useXDomain = true;
        delete $httpProvider.defaults.headers.common['X-Requested-With'];

        $httpProvider.responseInterceptors.push(['$q', function ($q) {
            return function (promise) {
                return promise.then(function (response) {
                    return response;
                }, function (response) {
                    switch (response.status) {
                        case 503:
                            window.location.href = Routing.generate('naturapass_main_homepage');
                            break;
                        case 401:
                            window.location.href = Routing.generate('naturapass_main_homepage');
                            break;
                    }

                    return $q.reject(response);

                });

            }

        }]);

    }]);

app.factory('socket', ['$rootScope', function ($rootScope) {
    if (typeof io != 'undefined') {

        var socket = io.connect(window.location.protocol + '//' + window.location.hostname, {
            secure              : window.location.protocol === 'https:',
            reconnection        : true,
            reconnectionDelay   : 5000,
            reconnectionDelayMax: 5000,
            reconnectionAttempts: 5
        });

        /**
         * Gestion de la reconnexion d'une socket, pour la mise Ã  jour de l'utilisateur
         */
        socket.on('reconnect', function () {
            if ($rootScope.connectedUser) {
                socket.emit('npevent-user:connected', $rootScope.connectedUser);
                $rootScope.$broadcast('npevent-socket/reconnected');
            }
        });

        /**
         * Gestion d'erreur d'une socket
         */
        socket.on('connect_error', function (error) {
            $rootScope.$broadcast('npevent-socket/connect-error', error);
        });

        /**
         * Gestion de l'erreur de reconnexion Ã  la socket serveur
         */
        socket.on('reconnect_error', function (error) {
            $rootScope.$broadcast('npevent-socket/connect-error', error);
        });

        /**
         * Gestion de la reconnexion Ã  la socket serveur
         */
        socket.on('reconnecting', function (error) {
            $rootScope.$broadcast('npevent-socket/connecting', error);
        });

        return {
            on  : function (eventName, callback) {
                socket.on(eventName, function () {
                    var args = arguments;
                    $rootScope.$apply(function () {
                        callback.apply(socket, args);
                    });
                });
            },
            emit: function (eventName, data, callback) {
                socket.emit(eventName, data, function () {
                    var args = arguments;
                    $rootScope.$apply(function () {
                        if (callback) {
                            callback.apply(socket, args);
                        }
                    });
                })
            }
        };
    }
}]);

app.factory('Auth', ['$http', '$filter', '$rootScope', 'socket', function ($http, $filter, $rootScope, socket) {
    var authenticated = false;

    return {
        isAuthenticated: function () {
            return authenticated;
        },
        getUser        : function () {
            $http.get($filter('route')('api_v2_get_user_connected')).success(function (data) {
                if (data.user) {
                    $rootScope.connectedUser = data.user;
                    $rootScope.$broadcast('npevent-user/connected', data.user);

                    authenticated = true;

                    socket && socket.emit('npevent-user:connected', $rootScope.connectedUser);
                }
            });
        }
    };
}]);

app.run(['$rootScope', '$location', '$http', 'Auth', function ($rootScope, $location, $http, Auth) {
    moment.locale(Translator.locale);
    videojs.options.flash.swf = "/swf/video-js.swf";

    Auth.getUser();

    $http._delete = $http['delete'];

    (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id))
            return;
        js = d.createElement(s);
        js.id = id;
        js.src = "//connect.facebook.net/fr_FR/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
}]);


