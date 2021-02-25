angular.module('app')

        .controller('BrandFormController', ['$scope', '$controller', '$http', '$timeout', '$modal', '$q', '$filter', '$maputils', function ($scope, $controller, $http, $timeout, $modal, $q, $filter, $maputils) {

                $scope.partnerActive = 0;

                $scope.submit = function () {
                    $('form[name="brand"]').submit();
                };
            }]);