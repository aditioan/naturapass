angular.module('app')

        .controller('DistributorFormController', ['$scope', '$controller', '$http', '$timeout', '$modal', '$q', '$filter', '$maputils', function ($scope, $controller, $http, $timeout, $modal, $q, $filter, $maputils) {


                $controller('BaseMapController', {$scope: $scope, $q: $q, $maputils: $maputils});

                $scope.geolocation = {
                    latitude: '',
                    longitude: '',
                    address: ''
                };

                /**
                 * Fonction d'initialisation de la map
                 */
                $scope.onMapReady = function () {
                    var location = new google.maps.LatLng($scope.map.center.latitude, $scope.map.center.longitude);
                    $maputils.geocode(location, function (response) {
                        $scope.position = new google.maps.Marker({
                            position: new google.maps.LatLng(response.lat, response.lng),
                            map: $scope.map.control.getGMap(),
                            draggable: true,
                            title: $filter('trans')('distributor.attributes.meetingAddress', {}, 'distributor'),
                            icon: 'http://' + window.location.hostname + '/img/map/map_icon_loc.png'
                        });

                        google.maps.event.addListener($scope.position, "dragend", function (event) {
                            $scope.selectedAddress = '';
                            $scope.$apply(function () {
                                $scope.map.loading = true;
                            });

                            $scope.map.control.refresh({latitude: $scope.position.getPosition().lat(), longitude: $scope.position.getPosition().lng()});

                            $maputils.geocode($scope.position.getPosition(), function (position) {
                                $scope.$apply(function () {
                                    $scope.geolocation.address = position.address;
                                    $scope.geolocation.latitude = $scope.position.getPosition().lat();
                                    $scope.geolocation.longitude = $scope.position.getPosition().lng();
                                    $scope.map.loading = false;
                                });
                            }, function (error) {
                                $scope.$apply(function () {
                                    $scope.map.error = $scope.getGeocoderError(error);
                                    $scope.map.loading = false;
                                });
                            });
                        });

                        $scope.$apply(function () {
                            $scope.geolocation.address = response.address;
                            $scope.geolocation.latitude = response.lat;
                            $scope.geolocation.longitude = response.lng;

                            $scope.map.loading = false;
                        });
                    }, function (error) {
                        $scope.map.error = $scope.getGeocoderError(error);
                        $scope.map.loading = false;
                        $scope.position = new google.maps.Marker({
                            map: $scope.map.control.getGMap(),
                            draggable: true,
                            title: $filter('trans')('distributor.attributes.meetingAddress', {}, 'distributor'),
                            icon: 'http://' + window.location.hostname + '/img/map/map_icon_loc.png'
                        });
                    });
                };

                /**
                 * Recherche une adresse tapée et mets à jour le centre de la carte
                 * @param $event                  */
                $scope.searchAddress = function ($event) {
                    if (($event && $event.keyCode === 13) || !$event) {
                        $scope.selectedAddress = '';
                        $scope.map.loading = true;

                        $maputils.geocode($scope.geolocation.address, function (result) {
                            $scope.map.control.refresh({latitude: result.lat, longitude: result.lng});

                            $scope.position.setPosition(new google.maps.LatLng(result.lat, result.lng));

                            $scope.$apply(function () {
                                $scope.geolocation.address = result.address;
                                $scope.geolocation.latitude = result.lat;
                                $scope.geolocation.longitude = result.lng;

                                $scope.map.loading = false;
                            });
                        }, function (error) {
                            $scope.$apply(function () {
                                $scope.map.error = $scope.getGeocoderError(error);

                                $scope.map.loading = false;
                            });
                        });
                    }
                };

                $scope.submit = function () {
                    $('form[name="distributor"]').submit();
                };
            }]);