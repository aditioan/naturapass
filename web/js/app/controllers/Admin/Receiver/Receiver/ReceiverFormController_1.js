angular.module('app').controller('ReceiverFormController', ['$scope', '$controller', '$http', '$timeout', '$modal', '$q', '$filter', '$maputils', function ($scope, $controller, $http, $timeout, $modal, $q, $filter, $maputils) {
        $scope.submit = function () {
            $('#hiddenCities').val($('.modal-cities-select2').select2('data'));
            $('#hiddenUsers').val($('.modal-users-select2').select2('data'));
            $('form[name="receiver"]').submit();
        };
        $scope.init = function (adress, users) {
//            $scope.address.selected = adress;
        };
        $scope.data = {
            json: {
                cities: $filter('json')(),
                users: $filter('json')()
            }
        };

        $scope.refreshAddresses = function (address) {
            var params = {address: address, sensor: false};
            return $http.get('http://maps.googleapis.com/maps/api/geocode/json', {params: params})
                    .then(function (response) {
                        $scope.addresses = response.data.results
                    });
        };

        $scope.refreshUsers = function (user) {
            return $http.get($filter('route')('api_v2_get_users_search', {q: user, page_limit: 100}))
                    .then(function (response) {
                        $scope.users = response.data.users
                    });
        };

        $scope.citiesOptions = {
            allowClear: true,
            minimumInputLength: 3,
            multiple: true,
            ajax: {
                url: 'http://maps.googleapis.com/maps/api/geocode/json',
                dataType: 'json',
                data: function (term, page) {
                    return {
                        address: term,
                        sensor: false
                    };
                },
                results: function (data, page) {
                    var result = [];
                    $.each(data.results, function (index, node) {
                        result.push({id: node.place_id, text: node.formatted_address});
                    });
                    return {results: result};
                }
            }
//            initSelection: function (element, callback) {
//                callback(users);
//            }
        };
        $scope.usersOptions = {
            allowClear: true,
            minimumInputLength: 3,
            multiple: true,
            ajax: {
                url: $filter('route')('api_v2_get_users_searchless'),
                dataType: 'json',
                data: function (term, page) {
                    return {
                        q: term,
                        limit: 10, // page size
                        select: true
                    };
                },
                results: function (data, page) {
                    return {results: data.users};
                }
            }
//            initSelection: function (element, callback) {
//                callback(users);
//            }
        };


    }]);