/**
 * Created by vincentvalot on 24/07/14.
 *
 * Controlleur de base d'une carte GoogleMaps
 */


angular.module('app')

        .controller('LoungeFormController', ['$scope', '$controller', '$http', '$timeout', '$modal', '$q', '$filter', '$maputils', function ($scope, $controller, $http, $timeout, $modal, $q, $filter, $maputils) {


                $controller('BaseMapController', {$scope: $scope, $q: $q, $maputils: $maputils});

                $scope.geolocation = {
                    latitude: '',
                    longitude: '',
                    address: ''
                };

                $scope.params = {
                    entity: {
                        singular: 'lounge',
                        plural: 'lounges'
                    },
                    routing: {
                        list: {
                            pending: 'api_v1_get_lounge_pending',
                            owning: 'api_v1_get_lounge_owning',
                            get: 'api_v1_get_lounges'
                        },
                        invitation: {
                            mail: 'api_v1_post_lounge_invite_mail',
                            user: 'api_v1_post_lounge_invite_user',
                            group: 'api_v1_post_lounge_invite_group'
                        },
                        subscribers: {
                            get: 'api_v1_get_lounge_subscribers',
                            put: 'api_v1_put_lounge_user_join',
                            post: 'api_v1_post_lounge_join',
                            remove: 'api_v1_delete_lounge_join',
                            participation: 'api_v1_put_lounge_subscriber_participation',
                            admin: 'api_v1_put_lounge_subscriber_admin'
                        },
                        home: 'naturapass_lounge_homepage',
                        show: 'naturapass_lounge_show',
                        remove: 'api_v1_delete_lounge',
                        get: 'api_v1_get_lounge'
                    }
                };

                $scope.favoriteAddresses = [];
                $scope.address = {
                    loading: true
                };
                $scope.selectedAddress = '';

                $scope.init = function () {
                    $scope.address.loading = true;
                    $http.get($filter('route')('api_v1_get_user_addresses'))
                            .success(function (response) {
                                $scope.favoriteAddresses = response.addresses;
                                $scope.address.loading = false;
                            });
                };

                $scope.chooseFavoriteAddress = function () {
                    $scope.geolocation.address = $scope.selectedAddress.address;
                    $scope.geolocation.latitude = $scope.selectedAddress.latitude;
                    $scope.geolocation.longitude = $scope.selectedAddress.longitude;
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
                            title: $filter('trans')('lounge.attributes.meetingAddress', {}, 'lounge'),
                            icon: window.location.protocol + '//' + window.location.hostname + '/img/map/map_icon_loc.png'
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
                    });
                };

                /**
                 * Recherche une adresse tapée et mets à jour le centre de la carte
                 * @param $event                  */                 $scope.searchAddress = function ($event) {
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


                /**
                 * Ouvre le modal de suppression d'un salon
                 *
                 * @param id
                 * @param name
                 */
                $scope.openDeleteModal = function (id, name) {
                    var $instance = $modal.open({
                        controller: 'ModalLoungeGroupRemoveController',
                        templateUrl: 'modal.remove-entity.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return {id: id, name: name};
                            }
                        }
                    });

                    $instance.result.then(function () {
                        document.location.href = $filter('route')($scope.params.routing.home);
                    });
                };

                $scope.submit = function () {
                    $('form[name="lounge"]').submit();
                };

                $scope.filterSubscribers = function (subscriber) {
                    return subscriber.access == 2 || subscriber.access == 3;
                };
            }]);