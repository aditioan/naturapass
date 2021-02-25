
angular.module('app')
        .controller('AnimalTreeController', ['$scope', '$http', '$modal', '$filter', function ($scope, $http, $modal, $filter) {
                $scope.data = [];
                $scope.field = {editing: ""};

                $scope.toggle = function (scope) {
                    scope.toggle();
                };

                $scope.moveLastToTheBeginning = function () {
                    var a = $scope.data.pop();
                    $scope.data.splice(0, 0, a);
                };

                $scope.newSubItem = function (scope) {
                    var nodeData = scope.$modelValue;
                    nodeData.nodes.push({
                        id: nodeData.id * 10 + nodeData.nodes.length,
                        title: nodeData.title + '.' + (nodeData.nodes.length + 1),
                        nodes: []
                    });
                };

                $scope.collapseAll = function () {
                    $scope.$broadcast('collapseAll');
                };

                $scope.expandAll = function () {
                    $scope.$broadcast('expandAll');
                };

                $scope.openNewModal = function ($index, entity, add) {
                    var $instance = $modal.open({
                        controller: 'ModalAnimalNewController',
                        templateUrl: 'modal.add-entity.html',
                        resolve: {
                            add: function () {
                                return add;
                            },
                            entity: function () {
                                return entity;
                            }
                        }
                    });
                };

                $scope.openNewAnimal = function () {
                    var $instance = $modal.open({
                        controller: 'ModalAnimalNew2Controller',
                        templateUrl: 'modal.add-entity.html',
                        resolve: {
                            entity: function () {
                                return $scope.data;
                            }
                        }
                    });
                };
                $scope.openDeleteModal = function ($index, entity, $this) {
                    var $instance = $modal.open({
                        controller: 'ModalAnimalRemoveController',
                        templateUrl: 'modal.remove-entity.html',
                        resolve: {
                            entity: function () {
                                return entity;
                            }
                        }
                    });

                    $instance.result.then(function () {
                        $this.remove();
                    });
                };

                $scope.init = function () {
                    $scope.loadingWaiting = true;
                    $http.get($filter('route')('api_admin_get_animal_all'))
                            .success(function (response) {
                                $scope.data = response.tree;
                                $scope.loadingWaiting = false;
                            })
                            .error(function (response) {
                                $scope.loadingWaiting = false;
                            });
                };

            }]);