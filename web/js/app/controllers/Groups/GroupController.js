/**
 * Created by vietlh
 */

angular.module('app').controller('GroupController', ['$scope', '$http', '$modal', '$filter', function ($scope, $http, $modal, $filter) {
        $scope.group = {};
        $scope.loaded = false;
        $scope.filter = [];

        $scope.params = {
            entity: {
                singular: 'group',
                plural: 'groups'
            },
            routing: {
                subscribers: {
                    get: 'api_v1_get_group_subscribers',
                    put: 'api_v1_put_group_user_join',
                    post: 'api_v1_post_group_join',
                    remove: 'api_v1_delete_group_join',
                    admin: 'api_v1_put_group_subscriber_admin'
                }
            }
        };

        $scope.emails = [];
        $scope.notifications = [];

        $scope.$on('npevent-user/connected', function (event, user) {
            $scope.connectedUser = user;
        });

        $scope.initGroup = function () {
            $('.emails-data').children().each(function () {
                $scope.emails.push({
                    id         : $(this).data('id'),
                    type       : $(this).data('type'),
                    periodicity: $(this).data('periodicity'),
                    period     : $(this).data('period'),
                    description: $(this).html(),
                    initWanted : parseInt($(this).data('wanted')),
                    wanted     : parseInt($(this).data('wanted')),
                    loading    : false
                });
            });

            $('.notifications-data').children().each(function () {
                $scope.notifications.push({
                    type       : $(this).data('type'),
                    description: $(this).html(),
                    initWanted : parseInt($(this).data('wanted')),
                    wanted     : parseInt($(this).data('wanted')),
                    period     : $(this).data('period'),
                    loading    : false
                });
            });
            var id = angular.element('[ng-controller="GroupController"]').data('group');

            if (id > 0) {
                $http.get($filter('route')('api_v1_get_group', {group: id}))
                    .success(function (response) {
                        $scope.group = response.group;

                        $scope.group.admins = $filter('filter')($scope.group.subscribers, {access: 3});

                        $scope.loaded = true;

                        $scope.$broadcast('npevent-group/loaded', $scope.group.id);
                        $scope.filter["group"] = [$scope.group.id];
                        $scope.$parent.$broadcast('npevent-menu/filter-changed', $scope.filter);
                    })
                    .error(function () {
                        $scope.loaded = true;
                    });
            } else {
                $scope.loaded = true;
            }
        };

        $scope.openAdministrationModal = function () {
            $modal.open({
                controller: 'ModalLoungeGroupSubscribersController',
                templateUrl: 'modal.subscribers.html',
                resolve: {
                    params: function () {
                        return $scope.params;
                    },
                    entity: function () {
                        return $scope.group;
                    },
                    method: function () {
                        return 'admin';
                    }
                }
            });
        }

        $scope.openBanishModal = function () {
            $modal.open({
                controller: 'ModalLoungeGroupSubscribersController',
                templateUrl: 'modal.subscribers.html',
                resolve: {
                    params: function () {
                        return $scope.params;
                    },
                    entity: function () {
                        return $scope.group;
                    },
                    method: function () {
                        return 'banish';
                    }
                }
            });
        }

        $scope.openValidationModal = function () {
            $modal.open({
                controller: 'ModalLoungeGroupSubscribersController',
                templateUrl: 'modal.subscribers.html',
                resolve: {
                    params: function () {
                        return $scope.params;
                    },
                    entity: function () {
                        return $scope.group;
                    },
                    method: function () {
                        return 'validation';
                    }
                }
            });
        };

        $scope.openSubscribersModal = function () {
            $modal.open({
                controller: 'ModalLoungeGroupSubscribersController',
                templateUrl: 'modal.subscribers.html',
                resolve: {
                    params: function () {
                        return $scope.params;
                    },
                    entity: function () {
                        return $scope.group;
                    },
                    method: function () {
                        return false;
                    }
                }
            });
        }

        $scope.openMapModal = function () {
            $modal.open({
                templateUrl: 'modal.group-map.html',
                controller: 'GroupMapController',
                size: 'lg',
                resolve: {
                    group: function () {
                        return $scope.group;
                    },
                    connectedUser: function () {
                        return $scope.connectedUser;
                    }
                }
            })
        }

        $scope.filterSubscribers = function (subscriber) {
            return subscriber.access == 2 || subscriber.access == 3;
        };
    }]);