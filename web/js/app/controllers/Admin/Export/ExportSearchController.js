/**
 * Created by vincentvalot on 14/05/14.
 */
angular.module('app').controller('ExportSearchController', ['$scope', '$http', '$filter', '$location', function ($scope, $http, $filter, $location) {
    $scope.selected = {
        groups    : [],
        users     : [],
        localities: [],
        insees    : [],
    };
    $scope.treeOptions = {
        multiSelection: true,
    };
    $scope.startDate = moment().startOf('month').format('YYYY-MM-DD');
    $scope.endDate = moment().endOf('month').format('YYYY-MM-DD');
    $scope.dataTree = [];
    $scope.modelCategories = [];

    $scope.params = {
        users     : {
            route  : $filter('route')('api_backoffice_get_user_search'),
            data   : "q",
            results: "users"
        },
        groups    : {
            route  : $filter('route')('api_backoffice_get_groups_admin_search'),
            data   : "name",
            results: "groups"
        },
        localities: {
            route  : $filter('route')('api_backoffice_get_locality_search'),
            data   : "filter",
            results: "localities"
        },
        insees    : {
            route  : $filter('route')('api_backoffice_get_locality_insee_search'),
            data   : "filter",
            results: "insees"
        }
    };
    $scope.data = {
        json: {
            groups    : $filter('json')(),
            localities: $filter('json')(),
            insees    : $filter('json')(),
            users     : $filter('json')()
        }
    };

    $scope.submit = function () {
        $scope.selected.groups = $('.modal-groups-select2').select2('data');
        $scope.selected.users = $('.modal-users-select2').select2('data');
        $scope.selected.localities = $('.modal-localities-select2').select2('data');
        $scope.selected.insees = $('.modal-insees-select2').select2('data');
        $("#startDate").val($scope.startDate);
        $("#endDate").val($scope.endDate);
        $('form[name="fdc"]').submit();
    };

    $scope.initExportSearch = function () {
        $('input[name="daterange"]').daterangepicker(
            {
                locale   : {
                    format          : 'DD MMM YYYY',
                    applyClass      : 'btn-green',
                    applyLabel      : "Valider",
                    fromLabel       : "De",
                    toLabel         : "�",
                    cancelLabel     : 'Annuler',
                    customRangeLabel: 'Choix',
                    firstDay        : 1,
                },
                ranges   : {
                    'Aujourd\'hui'     : [moment(), moment()],
                    'Hier'             : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    '7 derniers jours' : [moment().subtract(6, 'days'), moment()],
                    '30 derniers jours': [moment().subtract(29, 'days'), moment()],
                    'Mois en cours'    : [moment().startOf('month'), moment().endOf('month')],
                    'Mois dernier'     : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                startDate: moment().startOf('month'),
                endDate  : moment().endOf('month')
            },
            function (start, end, label) {
                $scope.startDate = start.format('YYYY-MM-DD');
                $scope.endDate = end.format('YYYY-MM-DD');
            });
        $http.get($filter('route')('api_admin_get_categories'))
            .success(function (data) {
                $scope.dataTree = data.tree;
            })
            .error(function (data) {
            });
    }

    $scope.multipleOptions = function (type) {
        var route = $scope.params[type].route,
            data = $scope.params[type].data,
            results = $scope.params[type].results;
        return {
            allowClear        : true,
            minimumInputLength: 3,
            multiple          : true,
            ajax              : {
                url     : route,
                dataType: 'json',
                data    : function (term, page) {
                    var a = {
                        select2: true,
                        limit  : 10, // page size
                        page   : page, // page number
                        select : true
                    };
                    a[data] = term;
                    return a;
                },
                results : function (data, page) {
                    return {results: data[results]};
                }
            }
        };
    };

}]);