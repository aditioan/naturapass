/**
 * Created by vincentvalot on 06/08/14.
 */


angular.module('app')

        /**
         * Controller
         *
         * Gère la map de NaturaPass
         */
        .controller('BaseMapController', ['$scope', '$q', '$filter', '$maputils', function ($scope, $q, $filter, $maputils) {

                $scope.deferred = {
                    mapReady: $q.defer(),
                    userConnected: $q.defer()
                };

                /**
                 * Variable de gestion de la Google Maps
                 * @type {{center: {latitude: number, longitude: number}, zoom: number, bounds: {northeast: {latitude: string, longitude: string}, southwest: {latitude: string, longitude: string}}, loading: boolean, data: {target: {}, type: string, adding: boolean, confirmedGeolocation: boolean, input: string}, options: {mapTypeControl: boolean}, control: {}, window: {}, windowScope: {}, movingMarker: {}, events: {}}}
                 */
                $scope.map = {
                    center: {
                        latitude: 45,
                        longitude: 4
                    },
                    zoom: 15,
                    bounds: {
                        northeast: {
                            latitude: '',
                            longitude: ''
                        },
                        southwest: {
                            latitude: '',
                            longitude: ''
                        }
                    },
                    loading: true,
                    options: {
                        scaleControl: true,
                        mapTypeControl: false,
                        mapTypeId: google.maps.MapTypeId.HYBRID
                    },
                    control: {},
                    events: {
                    },
                    printable: ''
                };

                /**
                 * Evenement: Connexion de l'utilisateur
                 */
                $scope.$on('npevent-user/connected', function (event, user) {
                    $scope.connectedUser = user;
                    $scope.deferred.userConnected.resolve();
                });

                /**
                 * Ajoute les contrôles sur la map
                 */
                $scope.addMapControls = function () {
                    var $controls = document.getElementById('google-maps-controls');

                    $scope.getMapObject().controls[google.maps.ControlPosition.TOP_RIGHT].push($controls);
                    $controls.className = $controls.className.replace('hide', '');
                    $controls.index = 1;
                };

                /**
                 * Recherche une adresse tapée et mets à jour le centre de la carte
                 * @param $event
                 */
                $scope.searchAddress = function ($event) {
                    if (($event && $event.keyCode === 13) || !$event) {
                        $maputils.geocode($scope.map.data.input, function (response) {
                            $scope.map.data.input = response.address;

                            $scope.map.bounds = {
                                northeast: {
                                    latitude: response.viewport.getNorthEast().lat(),
                                    longitude: response.viewport.getNorthEast().lng()
                                },
                                southwest: {
                                    latitude: response.viewport.getSouthWest().lat(),
                                    longitude: response.viewport.getSouthWest().lng()
                                }
                            };

                            var center = response.viewport.getCenter();
                            $scope.map.control.refresh({latitude: center.lat(), longitude: center.lng()});
                        });
                    }
                };

                /**
                 * Récupération de la position utilisateur pour charger la carte à sa position
                 */
                $scope.setMapOnUserLocation = function () {
                    if (typeof $scope.geolocation !== "undefined" && typeof $scope.geolocation.latitude !== "undefined" && typeof $scope.geolocation.longitude !== "undefined" && $scope.geolocation.longitude != "" && $scope.geolocation.latitude != "") {
                        $scope.map.center.latitude = $scope.geolocation.latitude;
                        $scope.map.center.longitude = $scope.geolocation.longitude;

                        $scope.deferred.mapReady.resolve();
                    }
                    else if ($scope.connectedUser && $scope.connectedUser.address) {
                        $scope.map.center.latitude = $scope.connectedUser.address.latitude;
                        $scope.map.center.longitude = $scope.connectedUser.address.longitude;

                        $scope.deferred.mapReady.resolve();
                    } else {
                        $maputils.geolocation(function (response) {
                            $scope.map.center.latitude = response.lat;
                            $scope.map.center.longitude = response.lng;

                            $scope.deferred.mapReady.resolve();
                        }, function () {
                            $scope.map.center.latitude = 45;
                            $scope.map.center.longitude = 7;

                            $scope.deferred.mapReady.resolve();
                        });
                    }
                };

                /**
                 * Permets de modifier la manière dont est chargée la carte
                 */
                $scope.initGoogleMaps = function () {
                    $scope.deferred.userConnected.promise.then(function () {
                        $scope.setMapOnUserLocation();
                    });
                };

                $scope.deferred.mapReady.promise.then(function () {
                    $scope.map.ready = true;

                    $scope.onMapReady();
                });

                /**
                 * S'occupe de la gestion de la map quand elle est chargée
                 */
                $scope.onMapReady = function () {
                };

                /**
                 * Retourne l'objet de Map
                 *
                 * @returns object
                 */
                $scope.getMapObject = function () {
                    return $scope.map.control.getGMap();
                };

                /**
                 * Retourne une erreur pour un code geocoder donné
                 *
                 * @param status
                 * @returns string
                 */
                $scope.getGeocoderError = function (status) {
                    switch (status) {
                        case google.maps.GeocoderStatus.ZERO_RESULTS:
                            return $filter('trans')('map.geocoder.errors.zero_results', {}, 'map');
                            break;
                        default:
                            return $filter('trans')('map.geocoder.errors.others', {}, 'map');
                            break;
                            /*                case google.maps.GeocoderStatus.OVER_QUERY_LIMIT:
                             break;
                             case google.maps.GeocoderStatus.REQUEST_DENIED:
                             break;
                             case google.maps.GeocoderStatus.INVALID_REQUEST:
                             break;*/
                    }
                };
            }]);