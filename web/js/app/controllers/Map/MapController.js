/**
 * Created by vincentvalot on 24/06/14.
 *
 *
 * Controllers:
 *      MapController
 *
 *      MapAddPublicationController
 */

angular.module('app')

        /**
         * Controller
         *
         * Gère la map de NaturaPass
         */
        .controller('MapController', ['$scope', '$controller', '$location', '$http', '$modal', '$timeout', '$q', '$filter', '$maputils', function ($scope, $controller, $location, $http, $modal, $timeout, $q, $filter, $maputils) {
                $controller('BaseMapController', {$scope: $scope, $q: $q, $maputils: $maputils});

                $scope.isInitiated = false;

                $scope.group = [];
                $scope.sharing = -1;
                $scope.reset = false;
                $scope.waiting = false;

                $scope.markers = {
                    width: 55,
                    height: 55 + 55 / 7,
                    shadowOffset: 5,
                    shrink: 10,
                    icons: {
                        colors: {
                            owner: "#8dbb1c",
                            normal: "#f28c14",
                            distributor: "#696969"
                        },
                        dragRadius: 12.5,
                        text: "/img/map/map_icon_text.png",
                        distributor: "/img/map/map_icon_distributor.png",
                        video: "/img/map/map_icon_video.png",
                        move: "/img/map/map_icon_move.png",
                        data: {
                            ownerText: '',
                            ownerVideo: '',
                            text: '',
                            video: '',
                            distributor: ''
                        }
                    }
                };

                /**
                 * Variable de gestion de la Google Maps
                 * @type {{center: {latitude: number, longitude: number}, zoom: number, bounds: {northeast: {latitude: string, longitude: string}, southwest: {latitude: string, longitude: string}}, loading: boolean, data: {target: {}, type: string, adding: boolean, confirmedGeolocation: boolean, input: string}, options: {mapTypeControl: boolean}, control: {}, window: {}, windowScope: {}, movingMarker: {}, events: {}}}
                 */
                $scope.map.data = {
                    target: {},
                    type: 'hybrid',
                    adding: false,
                    confirmedGeolocation: false,
                    input: ''
                };

                $scope.map.window = new google.maps.InfoWindow({
                    content: "holding..."
                });

                $scope.map.movingMarker = {};

                $scope.loadedBounds = [];

                $scope.oms = {};
                $scope.publications = [];
                $scope.distributors = [];

                $scope.deferred.httpRequest = $q.defer();

                /**
                 * Vérifie si une zone est déjà dans les zones chargées
                 * @param google.maps.LatLngBounds bounds
                 */
                $scope.inLoadedBounds = function (bounds) {
                    if (!bounds instanceof google.maps.LatLngBounds)
                        return;

                    var inLoadedBounds = false;

                    angular.forEach($scope.loadedBounds, function (area) {
                        if (!inLoadedBounds) {
                            inLoadedBounds =
                                    bounds.getNorthEast().lat() <= area.getNorthEast().lat()
                                    && bounds.getNorthEast().lng() <= area.getNorthEast().lng()
                                    && bounds.getSouthWest().lat() >= area.getSouthWest().lat()
                                    && bounds.getSouthWest().lng() >= area.getSouthWest().lng()
                        }
                    });

                    if (!inLoadedBounds) {
                        $scope.loadedBounds.push(bounds);
                    }

                    return inLoadedBounds;
                };

                /**
                 * Permets de modifier la manière dont est chargée la carte
                 */
                $scope.initGoogleMaps = function () {
                    var vars = $location.url().split('/');
                    vars.shift();
                    $scope.reset = true;

                    if (vars.length >= 3) {
                        $scope.map.center.latitude = vars[0];
                        $scope.map.center.longitude = vars[1];

                        if (/s[0-9]+/.test(vars[2])) {
                            var share = vars[2];
                            if (/g[0-9]+(-[0-9]+)*/.test(vars[2])) {
                                share = share.substring(0, vars[2].indexOf('g'));
                            }
                            share = share.replace('s', '');
                            $scope.sharing = share;
                        }
                        if (/g[0-9]+(-[0-9]+)*/.test(vars[2])) {
                            var groups = vars[2];
                            if (/s[0-9]+/.test(vars[2])) {
                                groups = groups.substring(vars[2].indexOf('g'));
                            }
                            groups = groups.replace('g', '');
                            $scope.group = $.map(groups.split('-'), Number);
                        }
                        if (/z[0-9]+/.test(vars[3])) {
                            $scope.map.zoom = parseInt(vars[3].replace('z', ''));
                        }
                        if (/h/.test(vars[4])) {
                            $scope.map.data.type = 'hybrid';
                        } else {
                            $scope.map.data.type = 'roadmap';
                        }
                        $scope.deferred.mapReady.resolve();
                    } else {
                        $scope.deferred.userConnected.promise.then(function () {
                            $scope.setMapOnUserLocation();
                        });
                    }
                };

                $scope.printMap = function () {
                    $scope.changeFilterMenu();

                    document.location.href = $filter('route')('naturapass_main_printable_map') + url;
                };

                /**
                 * Effectue une opération de nettoyage des données déjà chargées
                 */
                $scope.clearLoadedBounds = function () {
                    var toDelete = {};

                    angular.forEach($scope.loadedBounds, function (first) {
                        angular.forEach($scope.loadedBounds, function (second, secondIndex) {
                            if (second.getNorthEast().lat() < first.getNorthEast().lat()
                                    && second.getNorthEast().lng() < first.getNorthEast().lng()
                                    && second.getSouthWest().lat() > first.getSouthWest().lat()
                                    && second.getSouthWest().lng() > first.getSouthWest().lng()) {
                                toDelete[secondIndex] = true;
                            }
                        });
                    });

                    angular.forEach(toDelete, function (value, index) {
                        $scope.loadedBounds.splice(index, 1);
                    });
                };

                /**
                 * Charge les images pour les icônes de textes et de vidéos
                 */
                $scope.loadMarkersImages = function () {
                    $("<img />").attr("src", $scope.markers.icons.text).load(function (event) {
                        $scope.markers.icons.data.text = $scope.drawMarkerIcon(event.target.src);
                        $scope.markers.icons.data.ownerText = $scope.drawMarkerIcon(event.target.src, true);
                    });

                    $("<img />").attr("src", $scope.markers.icons.video).load(function (event) {
                        $scope.markers.icons.data.video = $scope.drawMarkerIcon(event.target.src);
                        $scope.markers.icons.data.ownerVideo = $scope.drawMarkerIcon(event.target.src, true);
                    });

                    $("<img />").attr("src", $scope.markers.icons.move).load();
                };

                $scope.removeData = function () {

                };

                /**
                 * Ajoute des écouteurs sur le menu de gauche
                 */
                $scope.addLeftMenuListeners = function () {
                    /**
                     * Evenement: Changement du filtre dans le menu
                     */
                    $scope.$on('npevent-menu/filter-changed', function ($event, filter) {
//                        $scope.sharing = -1;

                        if ($scope.map.loading) {
                            $scope.waiting = true;
                        }

                        if (filter['group']) {
                            $scope.group = filter['group'];
                        }
                        else {
                            $scope.group = [];
                        }
                        if (filter['sharing'] > -1) {
                            $scope.sharing = filter['sharing'];
                        }
                        else {
                            $scope.sharing = -1;
                        }
                        $scope.reset = true;

                        if ($scope.map.ready && !$scope.map.loading) {
                            $scope.loadPublications(true);
                        }
                        $scope.changeFilterMenu();
                    });

                    /**
                     * Evenement: A l'ajout d'une publication
                     */
                    $scope.$on('npevent-map/add-publication', function ($event) {
                        if ($scope.map.ready) {
                            $scope.showMapOverlay();
                        } else {
                            $scope.deferred.mapReady.then(function () {
                                $scope.showMapOverlay();
                            });
                        }
                    });
                };

                $scope.addLeftMenuListeners();

                /**
                 * Construit l'objet OverlappingMarkerSpiderfier
                 */
                $scope.initOMS = function () {
                    $scope.oms = new OverlappingMarkerSpiderfier($scope.getMapObject(), {
                        keepSpiderfied: true,
                        circleSpiralSwitchover: 6, // 'Infinity' pour ne pas changer la position des markers en spirale ou 0 pour toujours afficher en spirale
                        nearbyDistance: 25,
                        circleFootSeparation: 55,
                        spiralFootSeparation: 58,
                        spiralLengthStart: 25,
                        spiralLengthFactor: 8,
                        legWeight: 1
                    });
                };

                /**
                 * Ajoute les listeners sur l'objet OverlappingMarkerSpiderFier
                 */
                $scope.addOMSListeners = function () {
                    $scope.oms.addListener('click', function (marker, event) {
                        angular.element('.windows-container').append($scope.map.window.getContent());
                        if (marker.publication) {
                            $scope.map.window.setContent(angular.element('.publication-infowindow.infowindow' + marker.publication.id)[0]);
                        }
                        else if (marker.distributor) {
                            $scope.map.window.setContent(angular.element('.distributor-infowindow.infowindow' + marker.distributor.id)[0]);
                        }
                        $scope.map.window.open($scope.getMapObject(), marker);

                    }).addListener('spiderfy', function (markers) {
                        angular.element('.windows-container').append($scope.map.window.getContent());
                        $scope.map.window.close();

                    }).addListener('unspiderfy', function (markers) {
                        angular.element('.windows-container').append($scope.map.window.getContent());
                        $scope.map.window.close();
                    });
                };

                $scope.onMapReady = function () {
                    $scope.loadMarkersImages();

                    $timeout(function () {
                        $scope.changeMapType($scope.map.data.type);

                        $scope.addMapControls();

                        $scope.initOMS();

                        $scope.addOMSListeners();

                        /**
                         * Attends le chargement complet de la carte
                         */
                        google.maps.event.addListenerOnce($scope.getMapObject(), 'tilesloaded', function () {
                            $scope.loadPublications(true);

                            /**
                             * Effectue un reload de la carte à chaque mouvement
                             */
                            google.maps.event.addListener($scope.getMapObject(), 'idle', function () {
                                if (!$scope.map.data.adding) {
                                    $scope.loadPublications();
                                }
                                $scope.changeFilterMenu();
                            });
                        });

                        // google.maps.event.addListener($scope.map.window, 'closeclick', function () {
                        //     angular.element('.windows-container').append($scope.map.window.getContent());
                        // });

                        $http.get($filter('route')('api_admin_get_distributors', {limit: 10000000}))
                                .success(function (data) {
                                    angular.forEach(data.distributors, function (distributor) {
                                        //todo prechargement image
                                        $scope.addMarkerDistributor(distributor);
                                    });
                                    $scope.map.loading = false;
                                    $scope.deferred.httpRequest = false;
                                })
                                .error(function (data) {
                                    $scope.map.loading = false;
                                    $scope.deferred.httpRequest = false;
                                });
                    });
                };

                /**
                 * recharge les données pour l'impression
                 */
                $scope.changeFilterMenu = function () {
                    var url = '#/' + $scope.map.center.latitude + '/' + $scope.map.center.longitude;
                    if ($scope.sharing != -1 || $scope.group) {
                        url += '/';
                        if ($scope.sharing != -1) {
                            url += 's' + $scope.sharing
                        }
                        if ($scope.group) {
                            url += 'g' + $scope.group.join('-');
                        }
                    }
                    if (!jQuery.isEmptyObject($scope.map.control))
                        url += '/z' + $scope.getMapObject().getZoom();
                    else
                        url += '/z' + $scope.map.zoom;
                    url += '/' + $scope.map.data.type.charAt(0);

                    $scope.map.printable = $filter('route')('naturapass_main_printable_map') + url;
                };

                /**
                 * Ouvre le modal d'un distributeur
                 * @param publication
                 */
                $scope.openDistributorModal = function (distributor) {
                    distributor.fromMap = true;

                    var modal = $modal.open({
                        templateUrl: 'modal.distributor.html',
                        size: distributor.logo ? 'lg-full' : '',
                        controller: 'ModalDistributorController',
                        resolve: {
                            distributor: function () {
                                return distributor;
                            }
                        }
                    });

                    modal.result.then(function (data) {
                        if (data && data.remove) {
                            var markers = $scope.oms.getMarkers();
                            angular.forEach(markers, function (marker) {
                                if (marker.distributor.id == data.remove) {
                                    marker.setMap(null);
                                    delete marker;
                                }
                            });
                        }

                        $scope.modal = false;
                    });
                };
                /**
                 * Ouvre le modal d'une publication
                 * @param publication
                 */
                $scope.openPublicationModal = function (publication) {
                    publication.fromMap = true;

                    var modal = $modal.open({
                        templateUrl: 'modal.publication.html',
                        size: publication.media ? 'lg-full' : '',
                        controller: 'ModalPublicationController',
                        resolve: {
                            publication: function () {
                                return publication;
                            },
                            connectedUser: function () {
                                return $scope.connectedUser;
                            }
                        }
                    });

                    modal.result.then(function (data) {
                        if (data && data.remove) {
                            var markers = $scope.oms.getMarkers();
                            angular.forEach(markers, function (marker) {
                                if (marker.publication.id == data.remove) {
                                    marker.setMap(null);
                                    delete marker;
                                }
                            });
                        }

                        $scope.modal = false;
                    });
                };

                /**
                 * Ouvre le modal d'ajout d'une publication, et traite un nouvel ajout
                 * @param publication
                 */
                $scope.openAddingModal = function () {
                    var modalInstance = $modal.open({
                        templateUrl: 'modal.add-publication.html',
                        size: 'lg',
                        controller: 'MapAddPublicationController',
                        resolve: {
                            position: function () {
                                return new google.maps.LatLng($scope.map.center.latitude, $scope.map.center.longitude)
                            }
                        }
                    });

                    modalInstance.result.then(function (params) {
                        $scope.map.data.adding = false;
                        $scope.map.data.confirmedGeolocation = false;
                        $scope.hideMapOverlay();
                        $scope.addMarker(params.publication);
                    }, function () {
                        $scope.map.data.adding = false;
                        $scope.map.data.confirmedGeolocation = false;
                        $scope.hideMapOverlay();
                    });
                };

                /**
                 * Ajoute un marker sur la map
                 * @param publication
                 */
                $scope.addMarkerDistributor = function (distributor) {
                    var iconUrl = "",
                            iconType = "text";

                    if (distributor.logo) {
                        if (distributor.logo.type == 100) {
                            iconType = "image";
                            iconUrl = distributor.logo.path;
                        } else {
                            var path = distributor.logo.path;
                            distributor.logo.poster = (path.substr(0, path.lastIndexOf(".")) + '.jpeg');
                            distributor.logo.mp4 = (path.substr(0, path.lastIndexOf(".")) + '.mp4').replace('resize', 'mp4');
                            distributor.logo.webm = (path.substr(0, path.lastIndexOf(".")) + '.webm').replace('resize', 'webm');
                            distributor.logo.ogv = (path.substr(0, path.lastIndexOf(".")) + '.ogv').replace('resize', 'ogv');

                            iconType = "video";
                        }
                    }

                    $scope.distributors.push(distributor);

                    var options = {
                        position: new google.maps.LatLng(distributor.geolocation.latitude, distributor.geolocation.longitude),
                        distributor: distributor,
                        iconUrl: iconUrl,
                        iconType: iconType,
                        map: $scope.map.control.getGMap()
                    };

                    var promise = $scope.setMarkerInfosDistributor(options, iconType, iconUrl);

                    promise.then(function () {
                        var marker = new google.maps.Marker(options);

                        $scope.addMarkerListenersDistributor(marker);
                        $scope.oms.addMarker(marker);
                        $scope.removeData();
                    });
                };

                /**
                 * Ajoute un marker sur la map
                 * @param publication
                 */
                $scope.addMarker = function (publication) {
                    var iconUrl = "",
                            iconType = "text";

                    if (publication.media) {
                        if (publication.media.type == 100) {
                            iconType = "image";
                            iconUrl = publication.media.path;
                        } else {
                            var path = publication.media.path;
                            publication.media.poster = (path.substr(0, path.lastIndexOf(".")) + '.jpeg');
                            publication.media.mp4 = (path.substr(0, path.lastIndexOf(".")) + '.mp4').replace('resize', 'mp4');
                            publication.media.webm = (path.substr(0, path.lastIndexOf(".")) + '.webm').replace('resize', 'webm');
                            publication.media.ogv = (path.substr(0, path.lastIndexOf(".")) + '.ogv').replace('resize', 'ogv');

                            iconType = "video";
                        }
                    }

                    $scope.publications.push(publication);

                    var options = {
                        position: new google.maps.LatLng(publication.geolocation.latitude, publication.geolocation.longitude),
                        publication: publication,
                        iconUrl: iconUrl,
                        iconType: iconType,
                        map: $scope.map.control.getGMap()
                    };

                    var promise = $scope.setMarkerInfos(options, iconType, iconUrl, false, $scope.connectedUser.id === publication.owner.id);

                    promise.then(function () {
                        var marker = new google.maps.Marker(options);

                        $scope.addMarkerListeners(marker);
                        $scope.oms.addMarker(marker);
                        $scope.removeData();
                    });
                }

                /**
                 * Enlève et supprime tous les markers de la map
                 */
                $scope.resetMarkers = function () {
                    var markers = $scope.oms.getMarkers();
                    angular.forEach(markers, function (marker) {
                        marker.setMap(null);
                    });

                    $scope.oms.clearMarkers();
                }

                /**
                 * Charge les publications dans le champ de la carte, selon les élements du menu
                 * @param reset
                 */
                $scope.loadPublications = function (reset) {
                    var bounds = $scope.getMapObject().getBounds();

                    var params = {
                        swLat: bounds.getSouthWest().lat(),
                        swLng: bounds.getSouthWest().lng(),
                        neLat: bounds.getNorthEast().lat(),
                        neLng: bounds.getNorthEast().lng()
                    }

                    if ($scope.inLoadedBounds($scope.getMapObject().getBounds()) && !reset) {
                        $scope.clearLoadedBounds();
                        return;
                    }

                    $scope.map.loading = true;

                    if (reset) {
                        $scope.resetMarkers();

                        if ($scope.deferred.httpRequest) {
                            $scope.deferred.httpRequest.resolve();
                        }
                    }
                    $scope.deferred.httpRequest = $q.defer();
                    if ($scope.sharing >= 0) {
                        params.sharing = $scope.sharing;
                    }
                    if ($scope.group.length) {
                        params.groups = $scope.group;
                    }
                    if ($scope.reset) {
                        params.reset = "1";
                    }

                    $http.get($filter('route')('api_v2_get_publication_map', params), {timeout: $scope.deferred.httpRequest.promise})
                            .success(function (data) {
                                $scope.reset = false;

                                angular.forEach(data.publications, function (publication) {

                                    publication.savedGroups = [];
                                    var groups = [];
                                    angular.forEach(publication.groups, function (element, index) {
                                        publication.savedGroups.push({
                                            id: element.id,
                                            text: element.name
                                        });
                                        groups.push(element.id);
                                    });
                                    publication.groups = groups;

                                    publication.savedWithouts = [];
                                    var withouts = [];
                                    angular.forEach(publication.sharing.withouts, function (element, index) {
                                        publication.savedWithouts.push({
                                            id: element.id,
                                            text: element.firstname + ' ' + element.lastname
                                        });
                                        withouts.push(element.id);
                                    });
                                    publication.sharing.withouts = withouts;

                                    //todo prechargement image
                                    if (publication.media && publication.media.type === 100) {
                                        var img = new Image();
                                        img.src = publication.media.path;
                                        img.onload = function () {
                                            $scope.addMarker(publication);
                                        };
                                    } else {
                                        $scope.addMarker(publication);
                                    }

                                });
                                $scope.map.loading = false;

                                $scope.deferred.httpRequest = false;
                            })
                            .error(function (data) {
                                $scope.map.loading = false;

                                $scope.deferred.httpRequest = false;
                            })
                }

                /**
                 * Ajoute l'overlay cible sur la carte
                 */
                $scope.showMapOverlay = function () {
                    $scope.map.data.target = new Target({map: $scope.getMapObject()});
                    $scope.map.data.target.bindTo('center', $scope.getMapObject());

                    $scope.map.data.adding = true;
                };

                /**
                 * Cache l'overlay cible de la carte
                 */
                $scope.hideMapOverlay = function () {
                    $scope.map.data.target.setMap(null);
                    $scope.map.data.adding = false;

                    google.maps.event.clearListeners($scope.getMapObject(), 'center_changed');
                };

                /**
                 * Ajoute les listeners sur un Marker passé en paramètre
                 *
                 * @param {GMarker} oMarker
                 * @param {boolean} isOwner
                 * @param {integer} publicationId
                 * @returns {undefined}
                 */
                $scope.addMarkerListenersDistributor = function (marker) {
                    google.maps.event.addListener(marker, 'click', function (event) {
                        if ($scope.map.movingMarker instanceof google.maps.Marker) {
                            $scope.map.movingMarker.setOptions({draggable: false, zIndex: 500});
                            $scope.setMarkerInfos($scope.map.movingMarker, $scope.map.movingMarker.iconType, $scope.map.movingMarker.iconUrl, false, $scope.map.movingMarker.publication.owner.id === $scope.connectedUser.id);
                            $scope.map.movingMarker = null;
                        }
                    });

                    google.maps.event.addListener(marker, 'mouseover', function (event) {
                        this.setOptions({zIndex: 2000});
                    });

                    google.maps.event.addListener(marker, 'mouseout', function (event) {
                        this.setOptions({zIndex: 500});
                    });

                };
                /**
                 * Ajoute les listeners sur un Marker passé en paramètre
                 *
                 * @param {GMarker} oMarker
                 * @param {boolean} isOwner
                 * @param {integer} publicationId
                 * @returns {undefined}
                 */
                $scope.addMarkerListeners = function (marker) {
                    if (marker.publication.owner.id === $scope.connectedUser.id) {
                        google.maps.event.addListener(marker, 'click', function (event) {
                            if ($scope.map.movingMarker == this) {
                                this.setOptions({draggable: false, zIndex: 500});
                                $scope.setMarkerInfos(this, this.iconType, this.iconUrl, false, true);

                                $scope.map.movingMarker = null;
                            } else {
                                this.setOptions({draggable: true, zIndex: 2000});
                                $scope.setMarkerInfos(this, this.iconType, this.iconUrl, true, true);

                                this.oldPosition = this.getPosition();

                                if ($scope.map.movingMarker instanceof google.maps.Marker) {
                                    $scope.map.movingMarker.setOptions({draggable: false, zIndex: 500});
                                    $scope.setMarkerInfos($scope.map.movingMarker, $scope.map.movingMarker.iconType, $scope.map.movingMarker.iconUrl, false, true);
                                }

                                $scope.map.movingMarker = this;
                            }
                        });

                        google.maps.event.addListener(marker, 'dragend', function (event) {
                            $scope.map.movingMarker = null;

                            this.setOptions({draggable: false, zIndex: 500});
                            $scope.setMarkerInfos(this, this.iconType, this.iconUrl, false, true);

                            $maputils.geocode(event.latLng, function (result) {
                                if (/([A-Za-zÀ-ÿ-]*, ?[A-Za-zÀ-ÿ-]*)$/.test(result.address)) {
                                    result.address = RegExp.$1;
                                }

                                $http.put($filter('route')('api_v1_put_publication_geolocation', {publication: marker.publication.id}), {
                                    geolocation: {
                                        latitude: event.latLng.lat(),
                                        longitude: event.latLng.lng(),
                                        address: result.address
                                    }
                                })
                                        .success(function () {
                                            marker.publication.geolocation = {
                                                latitude: event.latLng.lat(),
                                                longitude: event.latLng.lng(),
                                                address: result.address
                                            };
                                        })
                                        .error(function () {
                                            marker.setPosition(marker.oldPosition);
                                        });
                            });
                        });
                    } else {
                        google.maps.event.addListener(marker, 'click', function (event) {
                            if ($scope.map.movingMarker instanceof google.maps.Marker) {
                                $scope.map.movingMarker.setOptions({draggable: false, zIndex: 500});
                                $scope.setMarkerInfos($scope.map.movingMarker, $scope.map.movingMarker.iconType, $scope.map.movingMarker.iconUrl, false, $scope.map.movingMarker.publication.owner.id === $scope.connectedUser.id);
                                $scope.map.movingMarker = null;
                            }
                        });
                    }

                    google.maps.event.addListener(marker, 'mouseover', function (event) {
                        this.setOptions({zIndex: 2000});
                    });

                    google.maps.event.addListener(marker, 'mouseout', function (event) {
                        this.setOptions({zIndex: 500});
                    });

                };

                /**
                 * Dessine l'icon suivant le type
                 *
                 * @param {string} url Url de l'image
                 * @param {boolean} dragIcon
                 */
                $scope.drawMarkerIconDistributor = function (url) {
                    var tw = $scope.markers.width / 5,
                            th = $scope.markers.width / 7,
                            canvas = document.createElement("canvas"),
                            context = canvas.getContext("2d"),
                            offset = 0;

                    canvas.setAttribute("width", $scope.markers.width + $scope.markers.shadowOffset + offset);
                    canvas.setAttribute("height", $scope.markers.height + $scope.markers.shadowOffset + offset);

                    // shadow
                    context.shadowOffsetX = 2;
                    context.shadowOffsetY = 2;
                    context.shadowBlur = 5;
                    context.shadowColor = 'rgba(0, 0, 0, .6)';
                    // rectangle
                    context.fillStyle = "#fff";
                    context.fillRect(offset, offset, $scope.markers.width, $scope.markers.width);
                    // triangle
                    context.moveTo(($scope.markers.width - tw) * .5 + offset, $scope.markers.width + offset);
                    context.lineTo(($scope.markers.width + tw) * .5 + offset, $scope.markers.width + offset);
                    context.lineTo($scope.markers.width * .5 + offset, $scope.markers.width + th + offset);
                    context.closePath();
                    context.fill();

                    // end shadow
                    context.shadowColor = 'rgba(0, 0, 0, 0)';

                    // background (owner|normal)
                    context.fillStyle = $scope.markers.icons.colors.distributor;
                    context.fillRect(offset + 3, offset + 3, $scope.markers.width - 6, $scope.markers.width - 6);

                    var thumb = new Image();
                    thumb.src = url;
                    context.drawImage(thumb, 6 + offset, 6 + offset, $scope.markers.width - 12, $scope.markers.width - 12);

                    return canvas.toDataURL('image/png');
                };

                /**
                 * Dessine l'icon suivant le type
                 *
                 * @param {string} url Url de l'image
                 * @param {boolean} dragIcon
                 */
                $scope.drawMarkerIcon = function (url, isOwner, dragIcon) {
                    var tw = $scope.markers.width / 5,
                            th = $scope.markers.width / 7,
                            canvas = document.createElement("canvas"),
                            context = canvas.getContext("2d"),
                            offset = dragIcon === true ? $scope.markers.icons.dragRadius : 0;

                    canvas.setAttribute("width", $scope.markers.width + $scope.markers.shadowOffset + offset);
                    canvas.setAttribute("height", $scope.markers.height + $scope.markers.shadowOffset + offset);

                    // shadow
                    context.shadowOffsetX = 2;
                    context.shadowOffsetY = 2;
                    context.shadowBlur = 5;
                    context.shadowColor = 'rgba(0, 0, 0, .6)';
                    // rectangle
                    context.fillStyle = "#fff";
                    context.fillRect(offset, offset, $scope.markers.width, $scope.markers.width);
                    // triangle
                    context.moveTo(($scope.markers.width - tw) * .5 + offset, $scope.markers.width + offset);
                    context.lineTo(($scope.markers.width + tw) * .5 + offset, $scope.markers.width + offset);
                    context.lineTo($scope.markers.width * .5 + offset, $scope.markers.width + th + offset);
                    context.closePath();
                    context.fill();

                    // end shadow
                    context.shadowColor = 'rgba(0, 0, 0, 0)';

                    // background (owner|normal)
                    context.fillStyle = isOwner ? $scope.markers.icons.colors.owner : $scope.markers.icons.colors.normal;
                    context.fillRect(offset + 3, offset + 3, $scope.markers.width - 6, $scope.markers.width - 6);

                    var thumb = new Image();
                    thumb.src = url;
                    context.drawImage(thumb, 6 + offset, 6 + offset, $scope.markers.width - 12, $scope.markers.width - 12);

                    if (dragIcon === true) { // drag icon
                        var di = new Image();
                        di.src = $scope.markers.icons.move;
                        context.drawImage(di, 1, 0, $scope.markers.icons.dragRadius * 2, $scope.markers.icons.dragRadius * 2);
                        context.restore();
                    }

                    return canvas.toDataURL('image/png');
                };

                /**
                 * Applique les informations générées au marker
                 * @param marker
                 * @param icon
                 * @param options
                 */
                $scope.applyMarkerInfos = function (marker, icon, options) {
                    if (marker instanceof google.maps.Marker) {
                        marker.setOptions(options);
                        marker.setIcon(icon);
                    } else {
                        marker.options = options;
                        marker.icon = icon;
                    }
                }

                /**
                 * Construit les élements d'icones d'un marker
                 *
                 * @param marker
                 * @param type
                 * @param url
                 * @param isOwner
                 */
                $scope.setMarkerInfosDistributor = function (marker, type, url) {
                    var deferred = $q.defer();

//                    setTimeout(function () {
                    var icon = {};
                    var options = {};


                    icon = {
                        size: new google.maps.Size($scope.markers.width - $scope.markers.shrink + $scope.markers.shadowOffset, $scope.markers.height - $scope.markers.shrink + $scope.markers.shadowOffset),
                        anchor: new google.maps.Point(($scope.markers.width - $scope.markers.shrink) * .5, $scope.markers.height - $scope.markers.shrink),
                        scaledSize: new google.maps.Size($scope.markers.width - $scope.markers.shrink + $scope.markers.shadowOffset, $scope.markers.height - $scope.markers.shrink + $scope.markers.shadowOffset)
                    };

                    options = {
                        visible: true,
                        anchorPoint: new google.maps.Point(0, -$scope.markers.height + $scope.markers.shrink)
                    };

                    switch (type) {
                        case "text":
                            url = $scope.markers.icons.distributor;
                            break;
                        case "video":
                            url = $scope.markers.icons.video;
                            break;
                    }

                    $("<img />").attr("src", url).load(function (event) {
                        icon = {
                            url: $scope.drawMarkerIconDistributor(event.target.src),
                            size: new google.maps.Size($scope.markers.width + $scope.markers.shadowOffset + $scope.markers.icons.dragRadius, $scope.markers.height + $scope.markers.shadowOffset + $scope.markers.icons.dragRadius),
                            anchor: new google.maps.Point($scope.markers.width * .5 + $scope.markers.icons.dragRadius, $scope.markers.height + $scope.markers.icons.dragRadius)
                        };

                        options = {
                            visible: true,
                            anchorPoint: new google.maps.Point(0, -$scope.markers.height)
                        };

                        $scope.applyMarkerInfos(marker, icon, options);
                        deferred.resolve();
                    });

//                    });

                    return deferred.promise;
                };
                /**
                 * Construit les élements d'icones d'un marker selon son type et le propriétaire de la publication liée
                 *
                 * @param marker
                 * @param type
                 * @param url
                 * @param isOwner
                 */
                $scope.setMarkerInfos = function (marker, type, url, draggable, isOwner) {
                    var deferred = $q.defer();

//                    setTimeout(function () {
                    var icon = {};
                    var options = {};

                    if (draggable) {
                        switch (type) {
                            case "text":
                                url = $scope.markers.icons.text;
                                break;
                            case "video":
                                url = $scope.markers.icons.video;
                                break;
                        }

                        $("<img />").attr("src", url).load(function (event) {
                            icon = {
                                url: $scope.drawMarkerIcon(event.target.src, isOwner, true),
                                size: new google.maps.Size($scope.markers.width + $scope.markers.shadowOffset + $scope.markers.icons.dragRadius, $scope.markers.height + $scope.markers.shadowOffset + $scope.markers.icons.dragRadius),
                                anchor: new google.maps.Point($scope.markers.width * .5 + $scope.markers.icons.dragRadius, $scope.markers.height + $scope.markers.icons.dragRadius)
                            };

                            options = {
                                visible: true,
                                anchorPoint: new google.maps.Point(0, -$scope.markers.height)
                            };

                            $scope.applyMarkerInfos(marker, icon, options);
                            deferred.resolve();
                        });

                    } else {

                        icon = {
                            size: new google.maps.Size($scope.markers.width - $scope.markers.shrink + $scope.markers.shadowOffset, $scope.markers.height - $scope.markers.shrink + $scope.markers.shadowOffset),
                            anchor: new google.maps.Point(($scope.markers.width - $scope.markers.shrink) * .5, $scope.markers.height - $scope.markers.shrink),
                            scaledSize: new google.maps.Size($scope.markers.width - $scope.markers.shrink + $scope.markers.shadowOffset, $scope.markers.height - $scope.markers.shrink + $scope.markers.shadowOffset)
                        }

                        options = {
                            visible: true,
                            anchorPoint: new google.maps.Point(0, -$scope.markers.height + $scope.markers.shrink)
                        };

                        switch (type) {
                            case "text":
                                icon.url = isOwner ? $scope.markers.icons.data.ownerText : $scope.markers.icons.data.text;

                                $scope.applyMarkerInfos(marker, icon, options);
                                deferred.resolve();
                                break;
                            case "video":
                                icon.url = isOwner ? $scope.markers.icons.data.ownerVideo : $scope.markers.icons.data.video;

                                $scope.applyMarkerInfos(marker, icon, options);
                                deferred.resolve();
                                break;
                            case "image":
                                $("<img />").attr("src", url).load(function (event) {
                                    icon = {
                                        url: $scope.drawMarkerIcon(event.target.src, isOwner),
                                        size: new google.maps.Size($scope.markers.width + $scope.markers.shadowOffset, $scope.markers.height + $scope.markers.shadowOffset),
                                        anchor: new google.maps.Point($scope.markers.width * .5, $scope.markers.height)
                                    };

                                    options.anchorPoint = new google.maps.Point(0, -$scope.markers.height);

                                    $scope.applyMarkerInfos(marker, icon, options);
                                    deferred.resolve();
                                });
                                break;
                        }

                    }
//                    });

                    return deferred.promise;
                };

                /**
                 * Change le type de la map de satellite à routière et inversement
                 * @param type
                 */
                $scope.changeMapType = function (type) {
                    $scope.map.data.type = type;

                    if (type === 'roadmap') {
                        $scope.map.control.getGMap().setMapTypeId(google.maps.MapTypeId.ROADMAP);
                    } else {
                        $scope.map.control.getGMap().setMapTypeId(google.maps.MapTypeId.HYBRID);
                    }
                    $scope.changeFilterMenu();
                };
            }])

        /**
         * Controller
         *
         * Modal d'ajout d'une publication sur la carte
         */
        .controller('MapAddPublicationController', ['$scope', '$timeout', '$modalInstance', 'position', function ($scope, $timeout, $instance, position) {
                $instance.opened.then(function () {
                    $timeout(function () {
                        $scope.$broadcast('npevent-map/set-geolocation', {position: position})
                    });
                });

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                };

                $scope.$on('npevent-publication/added', function ($event, publication) {
                    $instance.close({
                        publication: publication
                    });
                });
            }]);
;