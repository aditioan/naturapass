angular.module('app').controller('ReceiverFormController', ['$scope', '$controller', '$http', '$timeout', '$modal', '$q', '$filter', '$maputils', function ($scope, $controller, $http, $timeout, $modal, $q, $filter, $maputils) {
    $scope.selected = {
        address    : [],
        groups     : [],
        users      : [],
        departments: []
    };
    $scope.loading = {
        cities     : true,
        departments: true,
        groups     : true,
        users      : true
    };
    $scope.data = {
        json: {
            cities: $filter('json')(),
            groups: $filter('json')(),
            users : $filter('json')()
        }
    };

    $scope.submit = function () {
        $scope.selected.address = $('.modal-cities-select2').select2('data');
        $scope.selected.groups = $('.modal-groups-select2').select2('data');
        $scope.selected.users = $('.modal-users-select2').select2('data');
        $scope.selected.departments = $('.modal-departments-select2').select2('data');
        $('form[name="receiver"]').submit();
    };

    $scope.departmentsOptions = {
        allowClear        : true,
        minimumInputLength: 3,
        multiple          : true,
        ajax              : {
            url     : $filter('route')('api_admin_get_locality_department_search'),
            dataType: 'json',
            data    : function (term, page) {
                return {
                    filter: term,
                    limit : 100, // page size
                    page  : page, // page number
                    select: true
                };
            },
            results : function (data, page) {
                return {results: data.departments};
            }
        },
        initSelection     : function (element, callback) {
            var id = $("#id_receiver").val();
            $scope.loading.departments = false;
        }
    };

    $scope.citiesOptions = {
        allowClear        : true,
        minimumInputLength: 3,
        multiple          : true,
        ajax              : {
            url     : $filter('route')('api_admin_get_locality_search'),
            dataType: 'json',
            data    : function (term, page) {
                return {
                    filter: term,
                    limit : 100, // page size
                    page  : page, // page number
                    select: true
                };
            },
            results : function (data, page) {
                return {results: data.localities};
            }
        },
        initSelection     : function (element, callback) {
            var id = $("#id_receiver").val();
            if (id !== "") {
                $http.get($filter('route')('api_admin_get_receiver_locality', {receiver: id}))
                    .success(function (response) {
                        $scope.selected.address = response.localities;
                        callback($scope.selected.address);
                        $scope.loading.cities = false;
                    });
            } else {
                $scope.loading.cities = false;
            }
        }
    };
    $scope.groupsOptions = {
        allowClear        : true,
        minimumInputLength: 3,
        multiple          : true,
        ajax              : {
            url     : $filter('route')('api_v1_get_groups_search'),
            dataType: 'json',
            data    : function (term, page) {
                return {
                    name  : term,
                    limit : 10, // page size
                    page  : page, // page number
                    select: true
                };
            },
            results : function (data, page) {
                return {results: data.groups};
            }
        },
        initSelection     : function (element, callback) {
            var id = $("#id_receiver").val();
            if (id !== "") {
                $http.get($filter('route')('api_admin_get_receiver_group', {receiver: id}))
                    .success(function (response) {
                        $scope.selected.groups = response.groups;
                        callback($scope.selected.groups);
                        $scope.loading.groups = false;
                    });
            } else {
                $scope.loading.groups = false;
            }
        }
    };
    $scope.usersOptions = {
        allowClear        : true,
        minimumInputLength: 3,
        multiple          : true,
        ajax              : {
            url     : $filter('route')('api_backoffice_get_user_search'),
            dataType: 'json',
            data    : function (term, page) {
                return {
                    select2: true,
                    q      : term,
                    limit  : 10, // page size
                    page   : page, // page number
                    select : true
                };
            },
            results : function (data, page) {
                return {results: data.users};
            }
        },
        initSelection     : function (element, callback) {
            var id = $("#id_receiver").val();
            if (id !== "") {
                $http.get($filter('route')('api_admin_get_receiver_user', {receiver: id}))
                    .success(function (response) {
                        $scope.selected.users = response.users;
                        callback($scope.selected.users);
                        $scope.loading.users = false;
                    });
            } else {
                $scope.loading.users = false;
            }
        }
    };

}]);