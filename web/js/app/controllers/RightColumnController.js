/**
 * Created by vincentvalot on 10/09/14.
 */

angular.module('app').controller('RightColumnController',
    ['$scope', '$controller', '$http', '$filter', '$timeout', 'factory:UserFriendship',
        function ($scope, $controller, $http, $filter, $timeout, $factoryFriendship) {

            $scope.recommendations = [];

            $scope.$on('npevent-user/connected', function (event, user) {
                $scope.connectedUser = user;

                $scope.recommendationLoading = true;

                $http.get($filter('route')('api_v1_get_graph_friends_recommendations', {limit: 3}))
                    .success(function (response) {
                        $scope.recommendations = response.recommendations;

                        $scope.recommendationLoading = false;
                    })
                    .error(function () {
                        $scope.recommendationLoading = false;
                    });
            });

            $scope.removeRecommendation = function (recommendation) {
                recommendation.friendship = {loading: true};

                $http.put($filter('route')('api_v1_put_graph_recommendation_removed', {user: recommendation.id}))
                    .success(function () {
                        $timeout(function () {
                            $scope.recommendations.splice($scope.recommendations.indexOf(recommendation), 1);
                        }, 0.6);
                    });
            };

            $scope.addFriendship = function (recommendation) {
                $factoryFriendship.ask(recommendation.id)
                    .success(function () {
                        $timeout(function () {
                            $scope.recommendations.splice($scope.recommendations.indexOf(recommendation), 1);
                        }, 0.6)
                    });
            };
        }]);