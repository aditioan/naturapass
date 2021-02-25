/**
 * Created by vincentvalot on 24/07/14.
 */

angular.module('app').controller('LoungeMessagesController', ['$scope', '$http', '$modal', '$filter', '$timeout', 'socket', function ($scope, $http, $modal, $filter, $timeout, socket) {
    $scope.messages = [];

    $scope.socket = {
        disconnected: false,
        reconnecting: false
    }

    $scope.previousLoading = false;

    $scope.message = {
        rows: 1,
        content: '',
        loading: false
    };

    $scope.scroll = {
        position: false
    }

    $scope.limit = 10;

    /**
     * Evénement: Au chargement du salon
     */
    $scope.$on('npevent-lounge/loaded', function() {
        if (socket) {
            socket.on('npevent-lounge:message', function(data) {
                if (data.owner.usertag != $scope.connectedUser.usertag) {
                    $scope.messages.push(data);

                    $scope.scroll.position = 'bottom';
                }
            });

            $scope.$on('npevent-socket/connect-error', function(error) {
                $scope.$apply(function() {
                    $scope.socket.disconnected = true;
                    $scope.socket.reconnecting = false;

                    $scope.message.content = $filter('trans')('socket.reconnecting', {}, 'global');
                });
            });

            $scope.$on('npevent-socket/connecting', function(error) {
                $scope.$apply(function() {
                    $scope.socket.reconnecting = true;
                    $scope.message.content = $filter('trans')('socket.reconnecting', {}, 'global');
                });
            });

            $scope.$on('npevent-socket/reconnected', function(error) {
                $scope.$apply(function() {
                    $scope.socket.disconnected = false;
                    $scope.socket.reconnecting = false;

                    $scope.message.content = "";
                });
            });
        }

        $http.get($filter('route')('api_v1_get_lounge_messages', {lounge: $scope.lounge.id, limit: $scope.limit}))
            .success(function (response) {
                angular.forEach(response.messages, function (element) {
                    $scope.messages.unshift(element);
                });

                $scope.scroll.position = 'bottom';
            });
    });

    /**
     * Gère la création d'un message sur le chat
     *
     * @param $event
     */
    $scope.persistMessage = function ($event) {
        if ($event.keyCode === 8 || $event.keyCode === 46) {
            $scope.message.rows = ($scope.message.content.split(/\r\n|\n|\r/) || []).length;
        } else if ($event.keyCode === 13 && $scope.message.lastKey === 16) {
            $scope.message.rows += 1;
        } else if ($event.keyCode === 13) {
            $scope.message.loading = true;

            $http.post($filter('route')('api_v1_post_lounge_message', {lounge: $scope.lounge.id}), {content: $scope.message.content})
                .success(function (response) {
                    $scope.messages.push(response.message);

                    $scope.scroll.position = 'bottom';

                    $scope.message = {
                        rows: 1,
                        content: '',
                        loading: false
                    }
                })
                .error(function () {
                    $scope.message.loading = false;
                });
        }

        $scope.message.lastKey = $event.keyCode;
    };

    /**
     *
     * Récupération des messages plus anciens quand l'utilisateur remonte le scroll
     *
     */
    $scope.onScrolledBack = function () {
        if (!$scope.noMoreMessages) {
            var params = {
                lounge: $scope.lounge.id,
                limit: $scope.limit,
                loaded: $scope.messages.length,
                previous: 1
            };

            $scope.previousLoading = true;

            $http.get($filter('route')('api_v1_get_lounge_messages', params))
                .success(function (response) {
                    $scope.previousLoading = false;

                    if (response.messages.length == 0) {
                        $scope.noMoreMessages = true;
                    }

                    angular.forEach(response.messages, function (element) {
                        $scope.messages.unshift(element);
                    });
                })
                .error(function () {
                    $scope.previousLoading = false;
                });
        }
    };
}]);