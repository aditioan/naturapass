
angular.module('app')
        .controller('GameListingController', ['$scope', '$http', '$filter', '$timeout', function ($scope, $http, $filter) {
                $scope.gamesOpen = [];
                $scope.loadingOpen = false;
                $scope.openSize = 0;
                $scope.gamesClosed = [];
                $scope.loadingClosed = false;
                $scope.closedSize = 0;
                $scope.init = function () {
                    $scope.loadingOpen = true;
                    $http.get($filter('route')('api_admin_get_games_open', {limit: 30, offset: 0}))
                            .success(function (response) {
                                angular.forEach(response.games, function (element) {
                                    $scope.gamesOpen.push(element);
                                    $scope.openSize++;
                                });
                                $scope.loadingOpen = false;
                            })
                            .error(function (response) {
                                $scope.loadingOpen = false;
                            });
                    $scope.loadingClosed = true;
                    $http.get($filter('route')('api_admin_get_games_closed', {limit: 30, offset: 0}))
                            .success(function (response) {
                                angular.forEach(response.games, function (element) {
                                    $scope.gamesClosed.push(element);
                                    $scope.closedSize++;
                                });
                                $scope.loadingClosed = false;
                            })
                            .error(function (response) {
                                $scope.loadingClosed = false;

                            });
                };
            }])
        .controller('GameDetailController', ['$scope', '$http', '$filter', '$timeout', function ($scope, $http, $filter) {
                $scope.game = {};
                $scope.loading = false;
                $scope.init = function () {
                    $scope.loading = true;
                    var id = angular.element('[ng-controller="GameDetailController"]').data('game');
                    $http.get($filter('route')('api_admin_get_game', {game: id}))
                            .success(function (response) {
                                $scope.game = response.game;
                                $scope.loading = false;
                            })
                            .error(function (response) {
                                $scope.loading = false;
                            });
                };
            }])
        .controller('GameAddFormController', ['$scope', '$http', '$filter', '$timeout', function ($scope, $http, $filter) {

                $scope.persistGame = function () {
                    $scope.game.loading = true;

                    delete $scope.game.loading;

                    $http.post($filter('route')('api_admin_post_game'), {game: $scope.game})
                            .success(function (response) {
                                $scope.game.loading = false;
                            })
                            .error(function (response) {
                                $scope.game.loading = false;
                            });
                };

                $scope.loadGame = function () {
                    var idGame = $('#gameID').val();
                    $http.get($filter('route')('api_admin_get_game', {game: idGame}))
                            .success(function (response) {
                                angular.forEach(response.game, function (element) {
                                    $scope.game.push(element);
                                });
                            })
                            .error(function (response) {

                            });
                };
            }])
        .controller('GameFormController', ['$scope', '$http', '$filter', '$timeout', function ($scope, $http, $filter) {
                $scope.submit = function () {
                    $('form[name="game"]').submit();
                };

                $scope.init = function () {
                    if ($("input[name='game[type]']:checked").val() == 0)
                        $('.divChallenge').hide();
                    else
                        $('.divChallenge').show();
                };
                $('input:radio').change(function () {
                    if ($(this).val() == 0)
                        $('.divChallenge').hide();
                    else
                        $('.divChallenge').show();
                });

            }])
        .controller('GameListController', ['$scope', '$http', '$filter', '$timeout', function ($scope, $http, $filter, $timeout) {

                $scope.games = [];
                $scope.loadGame = function () {
                    $http.get($filter('route')('api_admin_get_games', {limit: 15, offset: 0}))
                            .success(function (response) {
                                angular.forEach(response.games, function (element) {
                                    $scope.games.push(element);
                                });
                            })
                            .error(function (response) {

                            });
                };

                $scope.deleteGame = function (index, game) {
                    game.loading = true;

                    $http._delete($filter('route')('api_admin_delete_game', {game: game.id}))
                            .success(function (response) {
                                $scope.games.splice(index, 1);
                            })
                            .error(function (response) {
                                game.loading = false;
                            });
                };
            }]);