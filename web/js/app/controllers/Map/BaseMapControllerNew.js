angular.module('app')

    .config(['uiGmapGoogleMapApiProvider', function (GoogleMapApi) {
        GoogleMapApi.configure({
            v        : '3.17',
            libraries: ''
        });
    }])

    .controller("BaseMapController", ['$scope', '$q', '$filter', '$maputils', 'uiGmapGoogleMapApi', 'uiGmapIsReady', function ($scope, $q, $filter, $maputils, GoogleMapApi, uiGmapIsReady) {

        $scope.deferred = {
            mapReady     : $q.defer(),
            userConnected: $q.defer()
        };

        $scope.mapInstance = null;

        var mapOptions = {
            zoomControl   : true,
            scaleControl  : true,
            mapTypeControl: false,
            rotateControl : true,
            mapTypeControlOptions: {
                mapTypeIds: ['cadastre, 1:25000, hybrid, roadmap, satellite, terrain']
            },
        };

        $scope.map = {
            title         : "Naturapass Map",
            center        : {latitude: 45.0, longitude: 4},
            zoom          : 15,
            bounds        : {
                northeast: {
                    latitude : '',
                    longitude: ''
                },
                southwest: {
                    latitude : '',
                    longitude: ''
                }
            },
            mapTypeControl: false,
            options       : mapOptions,
            loading       : true,
        };

        /**
         * Evenement: Connexion de l'utilisateur
         */
        $scope.$on('npevent-user/connected', function (event, user) {
            $scope.connectedUser = user;
            $scope.deferred.userConnected.resolve();
        });

        /**
         * Recherche une adresse tapée et mets à jour le centre de la carte
         * @param $event
         */
        $scope.searchAddress = function ($event) {
            $scope.map.data.input= $('#search_place').val();
            if (($event && $event.keyCode === 13) || !$event) {
                $maputils.geocode($scope.map.data.input, function (response) {
                    $scope.map.data.input = response.address;

                    $scope.map.bounds = {
                        northeast: {
                            latitude : response.viewport.getNorthEast().lat(),
                            longitude: response.viewport.getNorthEast().lng()
                        },
                        southwest: {
                            latitude : response.viewport.getSouthWest().lat(),
                            longitude: response.viewport.getSouthWest().lng()
                        }
                    };

                    var center = response.viewport.getCenter();

                    GoogleMapApi.then(function (maps) {
                        maps.visualRefresh = true;
                        $scope.defaultBounds = new google.maps.LatLngBounds(
                            new google.maps.LatLng($scope.map.bounds.northeast.latitude, $scope.map.bounds.northeast.longitude),
                            new google.maps.LatLng($scope.map.bounds.southwest.latitude, $scope.map.bounds.southwest.longitude));
                    });

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
         * Ajoute les contrôles sur la map
         */
        $scope.addMapControls = function () {
            var $controls = document.getElementById('google-maps-controls-new');
            $scope.mapInstance.controls[google.maps.ControlPosition.TOP_RIGHT].push($controls);
            $controls.className = $controls.className.replace('hide', '');
            $controls.index = 1;
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

        uiGmapIsReady.promise(1).then(function (instances) {
            instances.forEach(function (inst) {
                var map = inst.map;
                var uuid = map.uiGmap_id;
                var mapInstanceNumber = inst.instance; // Starts at 1.

                // Add IGN overlay type Cadastre
                var cadastreMapType = new google.maps.ImageMapType({
                        getTileUrl: function(tileCoord,zoom) {
                            var url= "https://wxs.ign.fr/vwpo4wuhvibyhjw1w96wvhbi/geoportail/wmts?" +
                                "&REQUEST=GetTile&SERVICE=WMTS&VERSION=1.0.0" +
                                "&STYLE=normal" +
                                "&TILEMATRIXSET=PM" +
                                "&FORMAT=image/png"+
                                "&LAYER=CADASTRALPARCELS.PARCELS"+
                            "&TILEMATRIX=" + zoom +
                                "&TILEROW=" + tileCoord.y +
                                "&TILECOL=" + tileCoord.x ;
                            return url;
                        },
                    tileSize: new google.maps.Size(256,256),
                    name: "Cadastre",
                    maxZoom: 18
                });

                // Add IGN overlay type 1:25000
                var ignMapType = new google.maps.ImageMapType({
                        getTileUrl: function(tileCoord,zoom) {
                            var url= "https://wxs.ign.fr/vwpo4wuhvibyhjw1w96wvhbi/geoportail/wmts?" +
                                "&REQUEST=GetTile&SERVICE=WMTS&VERSION=1.0.0" +
                                "&STYLE=normal" +
                                "&TILEMATRIXSET=PM" +
                                "&FORMAT=image/jpeg"+
                                "&LAYER=GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-EXPRESS.STANDARD"+
                            "&TILEMATRIX=" + zoom +
                                "&TILEROW=" + tileCoord.y +
                                "&TILECOL=" + tileCoord.x ;
                            return url;
                        },
                    tileSize: new google.maps.Size(256,256),
                    name: "1:25000",
                    maxZoom: 18
                });

                map.mapTypes.set('cadastre', cadastreMapType);
                //map.setMapTypeId('cadastre');
                map.mapTypes.set('1:25000', ignMapType);
                //map.setMapTypeId('1:25000');
                map.setMapTypeId('hybrid');
                //console.log(map);
                $scope.mapInstance = map;
            });

            $scope.spiderOptions = {
                keepSpiderfied: true
            };
        });

        /**
         * S'occupe de la gestion de la map quand elle est chargée
         */
        $scope.onMapReady = function () {
            //console.log($scope.mapInstance);
        };

        /**
         * Retourne l'objet de Map
         *
         * @returns object
         */
        $scope.getMapObject = function () {
            return $scope.mapInstance;
        };

    }]);
