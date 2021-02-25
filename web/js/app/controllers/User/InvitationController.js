/**
 * Created by vincentvalot on 08/07/14.
 */

angular.module('app')

    .controller('InvitationController', ['$scope', '$http', '$modal', '$filter', '$facebook', function ($scope, $http, $modal, $filter, $facebook) {
        $scope.facebook = {
            loading: false
        };

        $scope.openFacebookModal = function() {
            $facebook.getLoginStatus().then(function() {
                $facebook.api('/me/friends').then(
                    function (response) {
                        var $instance = $modal.open({
                            templateUrl: 'modal.invite-facebook-friends.html',
                            controller: 'ModalInviteFacebookFriendsController',
                            resolve: {
                                friends: function () {
                                    return response.data;
                                }
                            }
                        });

                        $instance.result.then(function() {
                            $scope.facebook.loading = false;
                        });
                    }, function (error) {
                        $scope.facebook.loading = false;
                        $scope.facebook.error = error;
                    });
            } , function(error) {
                $scope.facebook.loading = false;
                $scope.facebook.error = error;
            });
        };

        $scope.facebook = function () {
            $scope.facebook.loading = true;

            $facebook.getLoginStatus().then(function(response) {
                if (response.status == "not_authorized") {
                    $facebook.login().then(function (response) {
                        $scope.openFacebookModal();
                        $scope.updateUserFacebook(response.authResponse.userID);
                    }, function() {
                        $scope.facebook.loading = false;
                    });
                } else if (response.status == "connected") {
                    $scope.openFacebookModal();
                }
            } , function() {
                $scope.facebook.loading = false;
            });
        };

        $scope.updateUserFacebook = function(fid) {
            $http.put($filter('route')('api_v1_put_facebook_user'), {user: {facebook_id: fid}});
        }
    }])

    .controller('ModalInviteFacebookFriendsController', ['$scope', '$http', '$filter', '$facebook', '$modalInstance', 'factory:UserFriendship', 'friends', function($scope, $http, $filter, $facebook, $instance, $factoryFriendship, friends) {

        $scope.data = {
            friends: [],
            loading: true
        };

        $scope.data.sendInvitation = function(friend) {
            friend.loading = true;

            $factoryFriendship.ask(friend.id)
                .success(function() {
                    friend.loading = false;
                    friend.success = true;
                })
                .error(function() {
                    friend.loading = false;
                    friend.error = true;
            });
        };

        $instance.opened.then(function() {
            angular.forEach(friends, function(element) {
                $http.get($filter('route')('api_v1_get_facebook_user', {fid: element.id}))
                    .success(function(result) {
                        $scope.data.friends.push(result.user);
                        $scope.data.loading = false;
                    })
                    .error(function() {
                        $scope.data.loading = false;
                    });
            });
        });

        $scope.ok = function() {
            $instance.close();
        }
    }]);