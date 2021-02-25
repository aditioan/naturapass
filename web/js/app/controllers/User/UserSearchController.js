/**
 * Created by vincentvalot on 18/07/14.
 */

angular.module('app').controller('UserSearchController', ['$scope', '$http', '$modal', '$filter', '$timeout', 'factory:UserFriendship', function ($scope, $http, $modal, $filter, $timeout, $factoryFriendship) {
        $scope.search = '';
        $scope.users = [];
        $scope.recommendations = [];

        $scope.loading = false;

        $scope.$on('npevent-user/connected', function (event, user) {
            $scope.connectedUser = user;

            $scope.recommendationLoading = true;

            $http.get($filter('route')('api_v1_get_graph_friends_recommendations'))
                .success(function (response) {
                    $scope.recommendations = response.recommendations;

                    $scope.recommendationLoading = false;
                })
                .error(function () {
                    $scope.recommendationLoading = false;
                });
        });

        var removeItemInArray = function (source, removeItem) {
        var removeIdx = -1;
        source.some(function (item, idx) {
            if(item.id == removeItem.id) {
                removeIdx = idx;
                return true;
            }
            return false;
        });
        if (removeIdx > -1) {
            source.splice(removeIdx, 1);
            return true;
        }
        return false
    };

        $scope.doSearch = function () {
            $scope.loading = true;

            $http.get($filter('route')('api_v2_get_users_search') + '?q=' + $scope.search)
                .success(function (response) {
                    $scope.users = response.users;

                    $scope.loading = false;
                })
                .error(function () {
                    $scope.loading = false;
                })
        };

        $scope.recommendationViewed = function(user) {
            if (user.pertinence && !user.loading && !user.viewed) {
                user.loading = true;

                $http.put($filter('route')('api_v1_put_graph_recommendation_viewed', {user: user.id}))
                    .success(function() {
                        user.viewed = true;
                        user.loading = false;
                    })
                    .error(function() {
                        user.loading = false;
                    })
            }
        };

        $scope.recommendationUsed = function(user) {
            user.loading = true;

            $http.put($filter('route')('api_v1_put_graph_recommendation_used', {user: user.id}))
                .success(function() {
                    user.loading = false;
                })
                .error(function() {
                    user.loading = false;
                })
        };

        $scope.recommendationRemoved = function(user, $index) {
            user.loading = true;

            $http.put($filter('route')('api_v1_put_graph_recommendation_removed', {user: user.id}))
                .success(function() {
                    user.loading = false;
                    $scope.recommendations.splice($index, 1);
                })
                .error(function() {
                    user.loading = false;
                })
        };

        $scope.openFriendsModal = function (user, mutual) {
            $modal.open({
                controller: 'ModalFriendsController',
                templateUrl: 'modal.profile-friends.html',
                resolve: {
                    user: function () {
                        return user;
                    },
                    mutual: function () {
                        return mutual;
                    }
                }
            })
        };

        $scope.removeRecommendation = function(recommendation, $index) {
            recommendation.friendship = {loading: true};

            $http.put($filter('route')('api_v1_put_graph_recommendation_removed', {user: recommendation.id}))
                .success(function() {
                    $timeout(function() {
                        $scope.recommendations.splice($index, 1);
                    }, 0.6);
                });

        };

        $scope.addRecommendation = function (recommendation, $index) {
            if (typeof recommendation.friendship != 'object') {
                recommendation.friendship = {};
            }
            recommendation.friendship.loading = true;

            $factoryFriendship.ask(recommendation.id)
                .success(function () {
                    $timeout(function() {
                        $scope.recommendations.splice($index, 1);
                    }, 0.6);
                })
                .error(function () {
                    recommendation.friendship.loading = false;
                })
        };

        $scope.removeFriendship = function (user) {
            user.relation.friendship.loading = true;

            $factoryFriendship.remove(user.id)
                .success(function () {
                    user.relation.friendship = {};
                    user.relation.friendship.loading = false;
                    if ($scope.isOwning) {
                        removeItemInArray($scope.users, user);
                    }
                })
                .error(function () {
                    user.relation.friendship.loading = false;
                })
        };

        $scope.cancelFriendship = function (user, $index) {
            user.relation.friendship.loading = true;

            $factoryFriendship.remove(user.id)
                .success(function () {
                    user.relation.friendship = {};
                    user.relation.friendship.loading = false;

                    if (user.pertinence) {
                        $scope.recommendationRemoved(user, $index);
                    }

                    if ($scope.isOwning) {
                        removeItemInArray($scope.users, user);
                    }
                })
                .error(function () {
                    user.relation.friendship.loading = false;
                })
        };

        $scope.confirmFriendship = function (user) {
            user.relation.friendship.loading = true;

            $factoryFriendship.confirm(user.id)
                .success(function (response) {
                    user.relation.friendship = response.relation.friendship;
                    user.relation.friendship.loading = false;
                })
                .error(function () {
                    user.relation.friendship.loading = false;
                })
        };

        $scope.rejectFriendship = function (user) {
            user.relation.friendship.loading = true;

            $factoryFriendship.remove(user.id)
                .success(function (response) {
                    user.relation.friendship = response.relation.friendship;
                    user.relation.friendship.loading = false;
                    if ($scope.isOwning) {
                        removeItemInArray($scope.users, user);
                    }
                })
                .error(function () {
                    user.relation.friendship.loading = false;
                })
        };

        $scope.addFriendship = function (user) {
            if (typeof user.relation.friendship != 'object') {
                user.relation.friendship = {};
            }
            user.relation.friendship.loading = true;

            $factoryFriendship.ask(user.id)
                .success(function (response) {
                    user.relation.friendship = response.relation.friendship;
                    user.relation.friendship.loading = false;

                    if (user.pertinence) {
                        $scope.recommendationUsed(user);
                    }
                })
                .error(function () {
                    user.relation.friendship.loading = false;
                })
        };

        $scope.init = function () {
            $scope.isOwning = true;
            $scope.loading = true;
            var id = angular.element('[ng-controller="UserSearchController"]').data('id');

            $http.get($filter('route')('api_v2_get_user_friends', {
                user: id,
            }))
                .success(function (response) {
                    $scope.loading = false;
                    $scope.users = response['friends'];
                })
                .error(function () {
                    $scope.loading = false;
                });
        }
}])

    .controller('ModalFriendsController', ['$scope', '$http', '$filter', '$modalInstance', 'user', 'mutual', function ($scope, $http, $filter, $instance, user, mutual) {
        $scope.data = {
            friends: {},
            loading: true,
            mutual: mutual
        };

        $http.get($filter('route')('api_v2_get_user_friends', {user: user.id}) + '?mutual=' + (mutual ? 1 : 0))
            .success(function (response) {
                $scope.data.friends = response.friends;
                $scope.data.loading = false;
            })
            .error(function () {
                $scope.data.loading = false;
            });

        $scope.ok = function () {
            $instance.close();
        };
    }]);