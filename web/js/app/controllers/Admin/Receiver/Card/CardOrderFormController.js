angular.module('app').controller('CardOrderFormController', ['$scope', '$controller', '$http', '$timeout', '$modal', '$q', '$filter', '$maputils', function ($scope, $controller, $http, $timeout, $modal, $q, $filter, $maputils) {

    $scope.init = function () {
        $("#sortable").sortable({
            placeholder: "list-group-item",
            cancel     : ".list-group-item-danger",
            stop       : function (e, ui) {
                $('#sortable li').each(function (i, e) {
                    $(this).find('input').val((i + 1));
                });
            }
        });
        $("#sortable").disableSelection();
    };
    
    $scope.submit = function () {
        $('form[name="card"]').submit();
    };

}
])
;