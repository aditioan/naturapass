angular.module('app').controller('AnimalFormController', ['$scope', '$controller', '$http', '$timeout', '$modal', '$q', '$filter', '$maputils', function ($scope, $controller, $http, $timeout, $modal, $q, $filter, $maputils) {

        $scope.submit = function () {
            $('form[name="animal"]').submit();
        };


    }]);