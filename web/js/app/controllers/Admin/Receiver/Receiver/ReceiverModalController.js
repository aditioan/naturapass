angular.module('app')

        .controller('ModalReceiverRemoveController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'entity', function ($scope, $http, $filter, $instance, params, entity) {
                $scope.params = params;

                $scope.data = {
                    entity: entity,
                    loading: false
                };

                $scope.ok = function () {
                    $scope.data.loading = true;

                    var params = [];
                    params[$scope.params.entity.singular] = $scope.data.entity.id;

                    $http._delete($filter('route')($scope.params.routing.remove, params))
                            .success(function () {
                                $scope.data.loading = false;
                                $instance.close();
                            })
                            .error(function () {
                                $scope.data.loading = false;
                            });
                };

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                };
            }]);