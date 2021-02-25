angular.module('app').controller('ExportListingController', ['$scope', '$http', '$filter', '$location', function ($scope, $http, $filter, $location) {
    $scope.tables = [];
    $scope.tfoot = {};
    $scope.showTfoot = [];
    $scope.showPanelCard = [];

    $scope.exportExcel = function (card) {
        window.open($filter('route')('api_admin_get_observations_excel', {card: card}), "_blank", null);
    };

    $scope.hide = function (card) {
        $scope.showPanelCard[card] = false;
    };

    $scope.show = function (card) {
        $scope.showPanelCard[card] = true;
    };

    $scope.init = function () {
        $http.get($filter('route')('api_admin_get_observations'))
            .success(function (data) {
                $scope.tables = data;
                $.each(data, function (card, tables) {
                    $scope.tfoot[card] = {};
                    $scope.showPanelCard[card] = true;
                    var arrayObject = {}, sum = false;
                    $.each(tables.labels, function (index, label) {
                        if (label.type == 10 || label.type == 11) {
                            arrayObject[label.name] = 0;
                            sum = true;
                            $.each(tables.list, function (index, list) {
                                $.each(list.observation.attachments, function (index, attachment) {
                                    if (label.name == attachment.label) {
                                        arrayObject[label.name] += parseFloat(attachment.value);
                                    }
                                });
                            });
                        }

                    });
                    if (sum) {
                        $scope.tfoot[card] = arrayObject;
                    }
                    $scope.showTfoot[card] = (Object.keys($scope.tfoot).length > 0) ? true : false;
                });
                setTimeout(function () {
                    $('.datatable').dataTable({
                        dom              : 'Bfrtip',
                        responsive       : true,
                        "oLanguage"      : {
                            "sUrl": "//cdn.datatables.net/plug-ins/1.10.10/i18n/French.json"
                        },
                        "iDisplayLength" : -1,
                        "sPaginationType": "full_numbers",
                        "order"          : [[0, "asc"]],
                        buttons          : [
                            {extend: 'colvis', text: 'Afficher/Masquer des colonnes'},
                        ]
                    });
                }, 1000);

            })
            .error(function (data) {
            });
    }
}]);

