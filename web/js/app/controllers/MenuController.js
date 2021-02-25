/**
 * Created by vincentvalot on 27/05/14.
 */


angular.module('app').controller('MenuController', ['$scope', '$http', '$location', '$modal', '$timeout', '$filter', function ($scope, $http, $location, $modal, $timeout, $filter) {
    $scope.sharing = 3;
    $scope.group = [];
    $scope.locked = false;
    $scope.gamesMenuOpen = [];
    $scope.filter = [];
    $scope.loadingFilter = true;

    $scope.initMenu = function (publication) {

        var groupIds = [];
        $(".menu-group-in-mur").each(function () {
            groupIds.push($(this).attr('groupid'));
        });

        $http.get($filter('route')('api_v1_get_users_parameter_filter'))
                .success(function (response) {
                    if (response.sharingFilter >= 0) {
                        $scope.sharing = response.sharingFilter;
                        $scope.filter["sharing"] = $scope.sharing;
                    } else {
                        $scope.sharing = -1;
                    }
                    if (response.groupFilter) {
                        angular.forEach(response.groupFilter, function (groupFilter) {
                            if ($scope.group.indexOf(groupFilter) == -1 && groupIds.indexOf(groupFilter) > -1) {
                                $scope.group.push(groupFilter);
                            }
                        });
                        $scope.filter["group"] = $scope.group;
                    } else {
                        $scope.group = false;
                    }
                    var params = $location.search();

                    if (publication && ($scope.sharing >= 0 || response.groupFilter)) {
                        $timeout(function () {
                            $scope.$parent.$broadcast('npevent-menu/filter-changed', $scope.filter);
                        });
                    }

                    if (params.hasOwnProperty('landmark')) {
                        $timeout(function () {
                            $scope.$parent.$broadcast('npevent-map/add-publication');
                        });
                    }
                    $scope.loadingFilter = false;
                });

        $http.get($filter('route')('api_admin_get_games_open', {limit: 30, offset: 0}))
                .success(function (response) {
                    angular.forEach(response.games, function (element) {
                        $scope.gamesMenuOpen.push(element);
                    });
                });
    };

    $scope.$on('npevent-publication/menu-locked', function ($event, locked) {
        $scope.locked = locked;

        if ($scope.locked) {
            $scope.sharing = false;
            $scope.group = [];
        }
    });

    $scope.$on('npevent-menu/update', function ($event, data) {
        $scope.sharing = data.sharing;
        $scope.group = data.group;
    });

    $scope.mapAddPublication = function () {
        $scope.$parent.$broadcast('npevent-map/add-publication');
    };

    $scope.updateSharing = function (sharing) {
        $scope.loadingFilter = true;
        if (!$scope.locked) {
            if ($scope.sharing === sharing) {
                $http._delete($filter('route')('api_v1_delete_user_parameters_sharingfilter', {sharingFilter: sharing}))
                        .success(function () {
                            $scope.sharing = -1;
                            $scope.filter["sharing"] = $scope.sharing;
                            $scope.$parent.$broadcast('npevent-menu/filter-changed', $scope.filter);
                            $scope.loadingFilter = false;
                        })
                        .error(function () {
                            // handle failure
                            $scope.loadingFilter = false;
                        });
            } else {
                $http.put($filter('route')('api_v1_put_user_parameters_sharingfilter', {sharingFilter: sharing}))
                        .success(function () {
//                            $scope.group = false;
                            $scope.sharing = sharing;
                            $scope.filter["sharing"] = $scope.sharing;
                            $scope.$parent.$broadcast('npevent-menu/filter-changed', $scope.filter);
                            $scope.loadingFilter = false;
                        })
                        .error(function () {
                            // handle failure
                            $scope.loadingFilter = false;
                        });
            }
        }
    };

    $scope.updateGroup = function (group) {
        $scope.loadingFilter = true;
        if (!$scope.locked) {
            var index = $scope.group.indexOf(group);
            if ($scope.group.length > 0 && index > -1) {
                $http._delete($filter('route')('api_v1_delete_user_parameters_groupfilter', {groupFilter: group}))
                        .success(function () {
                            var index = $scope.group.indexOf(group);
                            $scope.group.splice(index, 1);
                            $scope.filter["group"] = $scope.group;
//                            $scope.sharing = -1;
                            $scope.$parent.$broadcast('npevent-menu/filter-changed', $scope.filter);
                            $scope.loadingFilter = false;
                        })
                        .error(function () {
                            // handle failure
                            $scope.loadingFilter = false;
                        });
            } else {
                $http.put($filter('route')('api_v1_put_user_parameters_groupfilter', {groupFilter: group}))
                        .success(function () {
                            if ($scope.group.indexOf(group) === -1) {
                                $scope.group.push(group);
                            }
                            $scope.filter["group"] = $scope.group;
//                            $scope.sharing = -1;
                            $scope.$parent.$broadcast('npevent-menu/filter-changed', $scope.filter);
                            $scope.loadingFilter = false;
                        })
                        .error(function () {
                            // handle failure
                            $scope.loadingFilter = false;
                        });
            }
        }
    };

    /**
     * Signaler un problème
     */
    $scope.reportProblem = function () {
        $modal.open({
            templateUrl: 'modal.report-problem.html',
            size: 'lg',
            controller: 'ModalReportProblemController'
        });
    };

    /**
     * Signaler un problème
     */
    $scope.contactNaturapass = function () {
        $modal.open({
            templateUrl: 'modal.contact-naturapass.html',
            size: 'lg',
            controller: 'ModalContactNaturaPassController'
        });
    };

    /**
     * Signaler un problème
     */
    $scope.reportAbuse = function () {
        $modal.open({
            templateUrl: 'modal.report-abuse.html',
            size: 'lg',
            controller: 'ModalReportAbuseController'
        });
    };
}])

    .controller('ModalContactNaturaPassController', ['$scope', '$http', '$filter', '$modalInstance', function ($scope, $http, $filter, $modalInstance) {
            $scope.email = {error: false};
            $scope.loading = false;
            $scope.error = false;

            $scope.ok = function () {
                $scope.email.error = false;
                $scope.loading = true;

                $http.post($filter('route')('api_v1_post_user_contact'), {'email': $scope.email})
                        .success(function () {
                            $modalInstance.close();
                            $scope.loading = false;
                        })
                        .error(function (data) {
                            $scope.email.error = data[0].message;
                            $scope.loading = false;
                        });

            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        }])

    .controller('ModalReportAbuseController', ['$scope', '$http', '$filter', '$modalInstance', function ($scope, $http, $filter, $modalInstance) {
            $scope.email = {error: false};
            $scope.loading = false;
            $scope.error = false;

            $scope.ok = function () {
                $scope.email.error = false;
                $scope.loading = true;

                $http.post($filter('route')('api_v1_post_user_reportabuse'), {'email': $scope.email})
                        .success(function () {
                            $modalInstance.close();
                            $scope.loading = false;
                        })
                        .error(function (data) {
                            $scope.email.error = data[0].message;
                            $scope.loading = false;
                        });

            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        }])

/**
 * Controller
 *
 * Signalement d'un problème
 */
.controller('ModalReportProblemController', ['$scope', '$http', '$filter', '$modalInstance', function ($scope, $http, $filter, $modalInstance) {
    $scope.email = {error: false};
    $scope.loading = false;
    $scope.error = false;

    $scope.ok = function () {
        $scope.email.error = false;
        $scope.loading = true;

        $http.post($filter('route')('api_v1_post_user_problem'), {'email': $scope.email})
            .success(function () {
                $modalInstance.close();
                $scope.loading = false;
            })
            .error(function (data) {
                $scope.email.error = data[0].message;
                $scope.loading = false;
            });

    };

    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
}]);
;