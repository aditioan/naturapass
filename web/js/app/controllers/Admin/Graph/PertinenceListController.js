/**
 * Created by vincentvalot on 21/07/14.
 */

angular.module('app').controller('PertinenceListController', ['$scope', '$http', '$filter', function($scope, $http, $filter) {
    $scope.pertinences = [];

    $scope.loadPertinences = function() {
        $http.get($filter('route')('api_v1_get_graph_pertinences'))
            .success(function(response) {
                $scope.pertinences = response.pertinences;
            });
    }

    $scope.updatePertinence = function(pertinence) {
        pertinence.loading = true;

        $http.put($filter('route')('api_v1_put_graph_pertinence', {pertinence: pertinence.type}), {pertinence: {value: pertinence.value, loss: pertinence.loss}})
            .success(function(response) {
                pertinence.loading = false;
            });
    }
}]);