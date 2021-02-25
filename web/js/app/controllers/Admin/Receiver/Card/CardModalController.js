angular.module('app')

        .controller('ModalCardRemoveController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'entity', function ($scope, $http, $filter, $instance, params, entity) {
                $scope.params = params;

                $scope.data = {
                    entity: entity,
                    loading: false
                };

                $scope.dataDuplicate = {
                    "card":
                        {
                            "name": ""
                        },
                    "label":
                        [
                        
                        ]
                }

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

                $scope.duplicate = function () {
                    $scope.data.loading = true;

                    $scope.dataDuplicate.card.name = $scope.data.entity.name + " / COPIE";

                    angular.forEach($scope.data.entity.labels, function (element) {
                        element.id = "new";
                        if (element.required == false) {
                            element.required = 0;
                        } else {
                            element.required = 1;
                        }
                        if (element.contents.length > 0) {
                            element.allowContent = true;
                        } else {
                            element.allowContent = false;
                        }
                        $scope.dataDuplicate.label.push(element);
                    });

                    $http.post('/api/admin/duplicates/cards', $scope.dataDuplicate)
                            .success(function () {
                                $scope.data.loading = false;
                                $instance.close();
                            })
                            .error(function () {
                                $scope.data.loading = false;
                            });
                }

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                };
            }]);