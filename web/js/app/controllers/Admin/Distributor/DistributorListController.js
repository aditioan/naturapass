angular.module('app')

        .controller('DistributorListController', ['$scope', '$http', '$modal', '$filter', '$controller', function ($scope, $http, $modal, $filter, $controller) {
                $scope.params = {
                    entity: {
                        singular: 'distributor',
                        plural: 'distributors'
                    },
                    routing: {
                        list: {
                            get: 'api_admin_get_distributors'
                        },
                        home: 'admin_distributor_homepage',
                        show: 'admin_distributor_edit',
                        remove: 'api_admin_delete_distributor',
                        get: 'api_admin_get_distributor'
                    }
                };

                $scope.entities = [];
                $scope.input = {};
                $scope.filterNoMore = false;

                $scope.offset = 0;
                $scope.limit = 3;
                $scope.busy = false;

                $scope.loading = true;

                $scope.$on('npevent-user/connected', function (event, user) {
                    $scope.connectedUser = user;
                });

                $scope.loadEntities = function () {
                    $scope.busy = true;

                    $scope.loading = true;
                    $scope.filterNoMore = false;
                    var filterList = '';
                    if (typeof $scope.input.filterList !== "undefined") {
                        filterList = $scope.input.filterList;
                    }

                    $http.get($filter('route')($scope.params.routing.list.get, {limit: $scope.limit, offset: $scope.offset, filter: filterList}))
                            .success(function (response) {

                                $scope.loading = false;

                                angular.forEach(response[$scope.params.entity.plural], function (element) {
                                    element.texts = {};
                                    $scope.entities.push(element);
                                });

                                if (response[$scope.params.entity.plural].length != 0) {
                                    $scope.busy = false;
                                    $scope.offset += $scope.limit;
                                } else {
                                    $scope.busy = true;
                                }

                                if ($scope.params.invited) {
                                    $scope.busy = true;
                                }
                                if (filterList != '' && $scope.busy && !$scope.loading && $scope.entities.length == 0) {
                                    $scope.filterNoMore = true;
                                }
                            })
                            .error(function () {
                                $scope.busy = false;
                                $scope.loading = false;
                            });
                };

                /**
                 * Gère la recherche d'un membre
                 *
                 * @param $event
                 */
                $scope.persistSearchList = function ($event) {
                    if ($event.keyCode === 13) {
                        $scope.entities = [];
                        $scope.offset = 0;
                        $scope.limit = 3;
                        $scope.loadEntities();
                    }
                };

                $scope.openDeleteModal = function ($index, entity) {
                    var $instance = $modal.open({
                        controller: 'ModalDistributorRemoveController',
                        templateUrl: 'modal.remove-entity.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return entity;
                            }
                        }
                    });

                    $instance.result.then(function () {
                        $scope.entities.splice($index, 1);
                    });
                };
            }]);