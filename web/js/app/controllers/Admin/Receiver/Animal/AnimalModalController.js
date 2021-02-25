angular.module('app')

//        .controller('ModalAnimalRemoveController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'entity', function ($scope, $http, $filter, $instance, params, entity) {
//                $scope.params = params;
//
//                $scope.data = {
//                    entity: entity,
//                    loading: false
//                };
//
//                $scope.ok = function () {
//                    $scope.data.loading = true;
//
//                    var params = [];
//                    params[$scope.params.entity.singular] = $scope.data.entity.id;
//
//                    $http._delete($filter('route')($scope.params.routing.remove, params))
//                            .success(function () {
//                                $scope.data.loading = false;
//                                $instance.close();
//                            })
//                            .error(function () {
//                                $scope.data.loading = false;
//                            });
//                };
//
//                $scope.cancel = function () {
//                    $instance.dismiss('cancel');
//                };
//            }])
        .controller('ModalAnimalNewController', ['$scope', '$http', '$filter', '$modalInstance', 'add', 'entity', function ($scope, $http, $filter, $instance, add, entity) {
                $scope.param = {add: add};

                $scope.data = {
                    entity: (add == 1) ? {id: "new", title: "", nodes: []} : entity,
                    loading: false
                };

                $scope.ok = function () {
                    $scope.data.loading = true;
                    if (add == 1) {
                        entity.nodes.push($scope.data.entity);
                    } else {
                        entity.name_fr = $scope.data.entity.name_fr;
                    }
                    $instance.close();

                };

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                };
            }])
        .controller('ModalAnimalNew2Controller', ['$scope', '$http', '$filter', '$modalInstance', 'entity', function ($scope, $http, $filter, $instance, entity) {

                $scope.data = {
                    entity: {id: "new", title: "", nodes: []},
                    loading: false
                };

                $scope.ok = function () {
                    $scope.data.loading = true;
                    entity.push($scope.data.entity);
                    $instance.close();

                };

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                };
            }])
        .controller('ModalAnimalRemoveController', ['$scope', '$http', '$filter', '$modalInstance', 'entity', function ($scope, $http, $filter, $instance, entity) {
                $scope.data = {
                    entity: entity,
                    loading: false
                };


                $scope.ok = function () {
                    $instance.close();
                };

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                };
            }]);