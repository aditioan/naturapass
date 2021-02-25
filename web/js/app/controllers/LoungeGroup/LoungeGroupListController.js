angular.module('app')

        .controller('LoungeGroupListController', ['$scope', '$http', '$modal', '$filter', function ($scope, $http, $modal, $filter) {
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

                    $http.get($filter('route')($scope.params.invited ? $scope.params.routing.list.pending : ($scope.params.owning ? $scope.params.routing.list.owning : $scope.params.routing.list.get), {limit: $scope.limit, offset: $scope.offset, filter: filterList}))
                            .success(function (response) {

                                $scope.loading = false;

                                angular.forEach(response[$scope.params.entity.plural], function (element) {
                                    element.texts = {};
                                    element.texts.admins = $filter('transchoice')('group.attributes.admin', element.nbAdmins - 1, {'count': element.nbAdmins - 1, 'other': element.nbAdmins - 1}, 'group');

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

                /**
                 * Accepte l'invitation de rejoindre une entité
                 * @param entity
                 */
                $scope.validateInvitation = function (entity) {
                    entity.loading = true;

                    var params = [];
                    params[$scope.params.entity.singular] = entity.id;
                    params['user'] = $scope.connectedUser.id;

                    $http.put($filter('route')($scope.params.routing.subscribers.put, params))
                            .success(function (response) {
                                entity.connected.access = 2;
                                entity.nbSubscribers++;
                                entity.loading = false;

                                entity.subscribers.push({
                                    access: 2,
                                    user: $scope.connectedUser
                                })
                            })
                            .error(function () {
                                entity.loading = false;
                            });
                }

                /**
                 * Supprimer un membre de l'entité
                 * @param entity
                 */
                $scope.removeSubscriber = function (entity) {
                    entity.loading = true;

                    var params = [];
                    params[$scope.params.entity.singular] = entity.id;
                    params['user'] = $scope.connectedUser.id;

                    $http._delete($filter('route')($scope.params.routing.subscribers.remove, params))
                            .success(function (response) {
                                if (entity.connected.access == 2) {
                                    entity.nbSubscribers--;
                                }

                                entity.connected = false;
                                entity.loading = false;
                            })
                            .error(function () {
                                entity.loading = false;
                            });
                }

                /**
                 * Joint un utilisateur, quelque soit sa condition au entité
                 * @param entity
                 */
                $scope.join = function (entity) {
                    entity.loading = true;

                    var params = [];
                    params[$scope.params.entity.singular] = entity.id;
                    params['user'] = $scope.connectedUser.id;

                    $http.post($filter('route')($scope.params.routing.subscribers.post, params))
                            .success(function (response) {
                                entity.loading = false;
                                entity.connected = response;
                                if (response.access == 2) {
                                    entity.nbSubscribers++;
                                }
                                if ($scope.params.entity.singular == "group") {
                                    entity.connected.mailable = true;
                                }
                            })
                            .error(function () {
                                entity.loading = false;
                            });
                }

                $scope.openDeleteModal = function ($index, entity) {
                    var $instance = $modal.open({
                        controller: 'ModalLoungeGroupRemoveController',
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
                }

                $scope.openSubscribersModal = function (entity) {
                    $modal.open({
                        controller: 'ModalLoungeGroupSubscribersController',
                        templateUrl: 'modal.subscribers.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return entity;
                            },
                            method: function () {
                                return false;
                            }
                        }
                    });
                }

                $scope.openValidationModal = function (entity) {
                    $modal.open({
                        controller: 'ModalLoungeGroupSubscribersController',
                        templateUrl: 'modal.subscribers.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return entity;
                            },
                            method: function () {
                                return 'validation';
                            }
                        }
                    });
                }

                $scope.filterSubscribers = function (subscriber) {
                    return subscriber.access == 2 || subscriber.access == 3;
                };
            }]);