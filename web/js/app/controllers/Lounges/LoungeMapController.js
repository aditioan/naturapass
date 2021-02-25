/**
 * Created by vincentvalot on 24/07/14.
 */

angular.module('app').controller('LoungeMapController', ['$scope', '$controller', '$http', '$modal', '$timeout', '$q', '$filter', '$maputils', 'socket', function ($scope, $controller, $http, $modal, $timeout, $q, $filter, $maputils, socket) {

        $controller('MapController', {$scope: $scope, $http: $http, $modal: $modal, $q: $q, $filter: $filter, $maputils: $maputils});
        /**
         * Variable de gestion de la Google Maps
         * @type {{center: {latitude: number, longitude: number}, zoom: number, bounds: {northeast: {latitude: string, longitude: string}, southwest: {latitude: string, longitude: string}}, loading: boolean, data: {target: {}, type: string, adding: boolean, confirmedGeolocation: boolean, input: string}, options: {mapTypeControl: boolean}, control: {}, window: {}, windowScope: {}, movingMarker: {}, events: {}}}
         */
        $scope.map = {
            center: {
                latitude: $scope.lounge.meetingAddress ? $scope.lounge.meetingAddress.latitude : 45,
                longitude: $scope.lounge.meetingAddress ? $scope.lounge.meetingAddress.longitude : 4
            },
            showSubscribers: false,
            showMedias: false,
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
            data: {
                target: {},
                type: 'roadmap',
                adding: false,
                confirmedGeolocation: false,
                input: ''
            },
            options: {
                mapTypeControl: false,
                mapTypeId: google.maps.MapTypeId.HYBRID
            },
            control: {},
            window: {},
            windowScope: {},
            movingMarker: {},
            events: {
            }
        };
        $scope.subscribersMarkers = [];
        $scope.addMarkerListeners = function () {
        }

        /**
         * Récupération des membres
         */
        $scope.loadSubscribersGeolocations = function () {
            $http.get($filter('route')('api_v1_get_lounge_subscribers_geolocations', {lounge: $scope.lounge.id}))
                    .success(function (response) {
                        angular.forEach(response.geolocations, function (subscriber) {

                            var marker = new google.maps.Marker({
                                position: new google.maps.LatLng(subscriber.geolocation.latitude, subscriber.geolocation.longitude),
                                subscriber: true,
                                map: $scope.map.control.getGMap(),
                                title: subscriber.user.fullname,
                                icon: {
                                    url: $scope.imageExist(window.location.protocol + '//' + window.location.hostname + subscriber.user.profilepicture),
                                    size: new google.maps.Size(55, 55)
                                }
                            });
                            $scope.subscribersMarkers[subscriber.user.usertag] = marker;
                            $scope.oms.addMarker(marker);
                        });
                    });
        }

        $scope.imageExist = function (url) {
            var img = new Image();
            img.src = url;
            if (img.height != 0)
                return url;
            else
                return "/img/default-avatar.jpg";
        }

        /**
         * Au chargement complet du salon
         */
        $scope.$on('npevent-lounge/loaded', function () {
            /**
             *  Réception d'une mise à jour de géolocalisation
             */
            if (socket) {
                socket.on('npevent-lounge:subscriber-geolocation', function (data) {
                    if ($scope.subscribersMarkers[data.user.usertag] && $scope.lounge.connected.user.usertag != data.user.usertag) {
                        var marker = $scope.subscribersMarkers[data.user.usertag];
                        marker.setPosition(new google.maps.LatLng(data.geolocation.latitude, data.geolocation.longitude));
                        marker.setTitle(data.user.fullname);
                        marker.setIcon({
                            url: window.location.protocol + '//' + window.location.hostname + data.user.profilepicture,
                            size: new google.maps.Size(55, 55)
                        });
                        if ($scope.map.showSubscribers) {
                            $scope.oms.addMarker(marker);
                        }
                    } else if (!$scope.subscribersMarkers[data.user.usertag]) {
                        var marker = new google.maps.Marker({
                            position: new google.maps.LatLng(data.geolocation.latitude, data.geolocation.longitude),
                            title: data.user.fullname,
                            icon: {
                                url: window.location.protocol + '//' + window.location.hostname + data.user.profilepicture,
                                size: new google.maps.Size(55, 55)
                            }
                        });
                        $scope.subscribersMarkers[data.user.usertag] = marker;
                        if ($scope.map.showSubscribers) {
                            $scope.oms.addMarker(marker);
                        }
                    }
                });
            }
            $scope.map.center.latitude = $scope.lounge.meetingAddress.latitude;
            $scope.map.center.longitude = $scope.lounge.meetingAddress.longitude;
            $scope.deferred.mapReady.resolve();
        });
        /**
         * Permets d'afficher ou non les médias sur la carte d'un salon
         */
        $scope.toggleMedias = function () {
            $scope.map.showMedias = !$scope.map.showMedias;
            var medias = $scope.oms.getMarkers();
            if ($scope.map.showMedias) {
                $scope.loadPublications();
                angular.forEach(medias, function (marker) {
                    if (marker.publication) {
                        marker.setMap($scope.map.control.getGMap());
                    }
                });
            } else {
                angular.forEach(medias, function (marker) {
                    if (marker.publication) {
                        marker.setMap(null);
                    }
                });
            }
        }

        /**
         * Permets d'afficher ou non les membres d'un salon
         */
        $scope.toggleSubscribers = function () {
            $scope.map.showSubscribers = !$scope.map.showSubscribers;
            var medias = $scope.oms.getMarkers();
//            console.log($scope.subscribersMarkers);
            if ($scope.map.showSubscribers) {
                if ($scope.subscribersMarkers.length) {
                    angular.forEach(medias, function (marker) {
                        if (marker.subscriber) {
                            marker.setMap($scope.map.control.getGMap());
                        }
                    });
                } else {
                    $scope.loadSubscribersGeolocations();
                }
            } else {
                angular.forEach(medias, function (marker) {
                    if (marker.subscriber) {
                        marker.setMap(null);
                    }
                });
            }
        }

        /**
         * Mettre à jour la géolocalisation de salon
         */
        $scope.updateLoungeGeolocation = function () {
            $http.put($filter('route')('api_v1_put_lounge_geolocation', {lounge: $scope.lounge.id, geolocation: $scope.lounge.geolocation + ''}))
                    .success(function () {
                        if ($scope.lounge.geolocation) {
                            $scope.loadSubscribersGeolocations();
                        }
                    })
                    .error(function () {
                        $scope.lounge.geolocation = !$scope.lounge.geolocation;
                    });
        }

        $scope.removeData = function () {
            if (!$scope.map.showMedias) {
                var medias = $scope.oms.getMarkers();
                angular.forEach(medias, function (marker) {
                    if (marker.publication) {
                        marker.setMap(null);
                    }
                });
            }
        };


        /**
         * Fonction d'initialisation de la map
         */
        $scope.onMapReady = function () {
            $scope.sharing = 3;
            $scope.map.showSubscribers = $scope.lounge.geolocation;
            if ($scope.map.showSubscribers) {
                if ($scope.subscribersMarkers.length) {
                    angular.forEach($scope.subscribersMarkers, function (marker) {
                        marker.setMap($scope.map.control.getGMap());
                    });
                } else {
                    $scope.loadSubscribersGeolocations();
                }
            }

            $scope.map.showMedias = false;
            $timeout(function () {
                $scope.initOMS();
                $maputils.position(function (position) {
                    $scope.oms.addMarker(new google.maps.Marker({
                        position: new google.maps.LatLng(position.lat, position.lng),
                        map: $scope.getMapObject(),
                        title: $filter('trans')('map.position', {}, 'map')
                    }));
                });
                var position = new google.maps.LatLng($scope.lounge.meetingAddress.latitude, $scope.lounge.meetingAddress.longitude);
                /**
                 * Récupération de la position du RDV pour avoir un viewport correct autour de cette zone
                 */
                $maputils.geocode(position, function (response) {
                    var marker = new google.maps.Marker({
                        position: position,
                        map: $scope.getMapObject(),
                        title: $filter('trans')('lounge.attributes.meetingAddress', {}, 'lounge'),
                        icon: window.location.protocol + '//' + window.location.hostname + '/img/map/map_icon_loc.png'
                    });
                    $scope.oms.addMarker(marker);
                    var center = response.viewport.getCenter();
                    $scope.map.control.refresh({latitude: center.lat(), longitude: center.lng()});
                    $scope.map.loading = false;
                });
                $scope.oms.addListener('click', function (marker, event) {
                    if (marker.publication) {
                        $scope.openPublicationModal(marker.publication);
                    }
                });
                google.maps.event.addListenerOnce($scope.getMapObject(), 'tilesloaded', function () {
                    if ($scope.map.showMedias) {
                        $scope.loadPublications(true);
                    }

                    google.maps.event.addListener($scope.getMapObject(), 'idle', function () {
                        if ($scope.map.showMedias) {
                            $scope.loadPublications();
                        }
                    });
                });
            });
            $scope.loadMarkersImages();
        }
    }])