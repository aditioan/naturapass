angular.module('app').controller('ZoneFormController', ['$scope', '$controller', '$http', '$timeout', '$modal', '$q', '$filter', '$maputils', function ($scope, $controller, $http, $timeout, $modal, $q, $filter, $maputils) {
        $scope.submit = function () {
            $('form[name="zone"]').submit();
        };
    }]);