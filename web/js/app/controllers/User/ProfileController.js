/**
 * Created by vincentvalot on 01/07/14.
 */

angular.module('app').controller('ProfileController', ['$scope', '$http', '$modal', '$filter', '$timeout', '$location', 'factory:UserFriendship', function ($scope, $http, $modal, $filter, $timeout, $location, $factoryFriendship) {

        $scope.profile = {};
        $scope.connectedUser = {};

        $scope.loaded = false;

        $scope.$on('npevent-user/connected', function (event, user) {
            $scope.connectedUser = user;
        });

        $scope.openNewMessage = function (user) {
            angular.element($('#docking-panel')).scope().openNewMessage(user);
        };

        $scope.initProfile = function () {
            var tag = angular.element('[ng-controller="ProfileController"]').data('profile');

            $http.get($filter('route')('api_v2_get_user', {user: tag}))
                    .success(function (data) {
                        $scope.profile = data.user;

                        if (!$scope.profile.relation) {
                            $scope.profile.relation = {};
                        }

                        $scope.loaded = true;

                        var params = $location.search();
                        if (params.hasOwnProperty('friends') && $scope.hasOwnProperty('friends')) {
                            $scope.openFriendsModal();
                        }
                    })
                    .error(function () {
                        $scope.loaded = true;
                    });
        };

        $scope.openFriendsModal = function (mutual) {
            var $instance = $modal.open({
                controller: 'ProfileFriendsController',
                templateUrl: 'modal.profile-friends.html',
                size: 'lg',
                resolve: {
                    user: function () {
                        return $scope.profile;
                    },
                    mutual: function () {
                        return mutual;
                    }
                }
            });

            $instance.result.then(function (response) {
                if (mutual) {
                    $scope.profile.relation.mutualFriends = response.friends;
                } else {
                    $scope.profile.friends = response.friends;
                }
            });
        };

        $scope.removeFriendship = function () {
            $scope.profile.relation.friendship.loading = true;

            $factoryFriendship.remove($scope.profile.id)
                    .success(function () {
                        $scope.profile.relation.friendship = {};
                        $scope.profile.relation.friendship.loading = false;

                        $scope.profile.nbFriends--;
                    })
                    .error(function () {
                        $scope.profile.friendship.loading = false;
                    })
        };

        $scope.cancelFriendship = function () {
            $scope.profile.relation.friendship.loading = true;

            $factoryFriendship.remove($scope.profile.id)
                    .success(function () {
                        $scope.profile.relation.friendship = {};
                        $scope.profile.relation.friendship.loading = false;
                    })
                    .error(function () {
                        $scope.profile.relation.friendship.loading = false;
                    })
        };

        $scope.confirmFriendship = function () {
            $scope.profile.relation.friendship.loading = true;
            window.console.log($scope.profile.id);

            $factoryFriendship.confirm($scope.profile.id)
                    .success(function (response) {
                        $scope.profile.relation = response.relation;
                        $scope.profile.relation.friendship.loading = false;
                    })
                    .error(function () {
                        $scope.profile.relation.friendship.loading = false;
                    })
        };

        $scope.rejectFriendship = function () {
            $scope.profile.relation.friendship.loading = true;

            $factoryFriendship.remove($scope.profile.id)
                    .success(function (response) {
                        $scope.profile.relation = response.relation;
                        $scope.profile.relation.friendship.loading = false;
                    })
                    .error(function () {
                        $scope.profile.relation.friendship.loading = false;
                    })
        };

        $scope.addFriendship = function () {
            if (typeof $scope.profile.relation.friendship != 'object') {
                $scope.profile.relation.friendship = {};
            }
            $scope.profile.relation.friendship.loading = true;

            $factoryFriendship.ask($scope.profile.id)
                    .success(function (response) {
                        $scope.profile.relation = response.relation;
                        $scope.profile.relation.friendship.loading = false;
                    })
                    .error(function () {
                        $scope.profile.relation.friendship.loading = false;
                    })
        };
    }])

        .controller('ProfileFriendsController', ['$scope', '$http', '$filter', '$modalInstance', 'factory:UserFriendship', 'user', 'mutual', function ($scope, $http, $filter, $instance, $factoryFriendship, user, mutual) {
                $scope.data = {
                    friends: {},
                    loading: true,
                    mutual: mutual
                };

                $http.get($filter('route')('api_v2_get_user_friends', {user: user.id}), {params: {mutual: mutual ? 1 : 0}})
                        .success(function (response) {
                            $scope.data.friends = response.friends;
                            $scope.data.loading = false;
                        })
                        .error(function () {
                            $scope.data.loading = false;
                        });

                $scope.ok = function () {
                    $instance.close({
                        'friends': $scope.data.friends.length
                    });
                };

                $scope.removeFriendship = function (friend) {
                    friend.relation.friendship.loading = true;

                    $factoryFriendship.remove(friend.id)
                            .success(function () {
                                friend.relation.friendship = {};
                                friend.relation.friendship.loading = false;
                            })
                            .error(function () {
                                friend.relation.friendship.loading = false;
                            })
                };

                $scope.cancelFriendship = function (friend, $index) {
                    friend.relation.friendship.loading = true;

                    $factoryFriendship.remove(friend.id)
                            .success(function () {
                                friend.relation.friendship = {};
                                friend.relation.friendship.loading = false;
                            })
                            .error(function () {
                                friend.relation.friendship.loading = false;
                            })
                };

                $scope.confirmFriendship = function (friend) {
                    friend.relation.friendship.loading = true;

                    $factoryFriendship.confirm(friend.id)
                            .success(function (response) {
                                friend.relation = response.relation;
                                friend.relation.friendship.loading = false;
                            })
                            .error(function () {
                                friend.relation.friendship.loading = false;
                            })
                };

                $scope.rejectFriendship = function (friend) {
                    friend.relation.friendship.loading = true;

                    $factoryFriendship.remove(friend.id)
                            .success(function (response) {
                                friend.relation = response.relation;
                                friend.relation.friendship.loading = false;
                            })
                            .error(function () {
                                friend.relation.friendship.loading = false;
                            })
                };

                $scope.addFriendship = function (friend) {
                    if (typeof friend.relation.friendship != 'object') {
                        friend.relation.friendship = {};
                    }
                    friend.relation.friendship.loading = true;

                    $factoryFriendship.ask(friend.id)
                            .success(function (response) {
                                friend.relation = response.relation;
                                friend.relation.friendship.loading = false;
                            })
                            .error(function () {
                                friend.relation.friendship.loading = false;
                            })
                };
            }]);