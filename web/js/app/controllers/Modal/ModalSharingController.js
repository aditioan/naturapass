/**
 * Created by vincentvalot on 28/05/14.
 */

angular.module('app').controller('ModalSharingController', ['$scope', '$filter', '$facebook', '$modalInstance', 'sharings', 'current', 'groups', 'withouts', function ($scope, $filter, $facebook, $instance, sharings, current, groups, withouts, social) {
        if (!social || social == undefined) {
            social = {
                facebook: false,
                google: false
            }
        }

        $scope.data = {
            sharings: sharings,
            current: current,
            loading: false,
            social: social,
            json: {
                withouts: $filter('json')(withouts),
                groups: $filter('json')(groups)
            }
        };

        $scope.getFacebookLoginStatus = function () {
            $scope.data.loading = true;

            $facebook.getLoginStatus().then(function (response) {
                if (response.status == "not_authorized") {
                    $facebook.login().then(function (response) {
                        $http.put($filter('route')('api_v1_put_facebook_user'), {user: {facebook_id: response.authResponse.userID}});
                        $scope.data.loading = false;
                    }, function (error) {
                        $scope.data.loading = false;
                        $scope.data.social.facebook = false;
                    });
                } else if (response.status == "connected") {
                    $scope.data.loading = false;
                }
            }, function (error) {
                $scope.data.loading = false;
                $scope.data.social.facebook = false;
            });
        }

        $scope.ok = function () {
            $instance.close({
                current: $scope.data.current,
                groups: $('.modal-groups-select2').select2('data'),
                withouts: $('.modal-withouts-select2').select2('data'),
                social: $scope.data.social
            });
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };

        $scope.groupsOptions = {
            allowClear: true,
            minimumInputLength: 3,
            multiple: true,
            ajax: {
                url: $filter('route')('api_v1_get_groups_search'),
                dataType: 'json',
                data: function (term, page) {
                    return {
                        name: term,
                        limit: 10, // page size
                        page: page, // page number
                        select: true
                    };
                },
                results: function (data, page) {
                    var more = (page * 10) < data.total;
                    return {results: data.groups};
                }
            },
            initSelection: function (element, callback) {
                callback(groups);
            }
        }

        $scope.withoutsOptions = {
            allowClear: true,
            minimumInputLength: 3,
            multiple: true,
            ajax: {
                url: $filter('route')('api_v1_get_user_friends', {user: $scope.$parent.connectedUser.id}),
                dataType: 'json',
                data: function (term, page) {
                    return {
                        name: term,
                        limit: 10, // page size
                        page: page // page number
                    };
                },
                results: function (data, page) {
                    return {results: data.friends};
                }
            },
            initSelection: function (element, callback) {
                callback(withouts);
            }
        }
    }]);
