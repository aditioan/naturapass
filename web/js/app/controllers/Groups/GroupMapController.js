/**
 * Created by vincentvalot on 01/08/14.
 */
angular.module('app').controller('GroupMapController', ['$scope', '$controller', '$http', '$modal', '$timeout', '$q', '$filter', '$maputils', '$modalInstance', 'group', 'connectedUser', function ($scope, $controller, $http, $modal, $timeout, $q, $filter, $maputils, $instance, group, connectedUser) {

    $controller('MapController', {$scope: $scope, $http: $http, $modal: $modal, $q: $q, $filter: $filter, $maputils: $maputils});

    $scope.addMarkerListeners = function () {
    }

    $instance.opened.then(function () {
        $scope.setMapOnUserLocation();
    });

    $scope.onMapReady = function () {
        $scope.connectedUser = connectedUser;
        $scope.group = group.id;

        $scope.loadMarkersImages();

        $timeout(function () {
            $scope.initOMS();

            $scope.oms.addListener('click', function (marker, event) {
                if (marker.publication) {
                    $scope.openPublicationModal(marker.publication);
                }
            });

            $scope.oms.addMarker(new google.maps.Marker({
                position: new google.maps.LatLng($scope.map.center.latitude, $scope.map.center.longitude),
                map     : $scope.getMapObject(),
                title   : $filter('trans')('map.position', {}, 'map'),
                icon    : window.location.protocol + '//' + window.location.hostname + '/img/map/map_icon_loc.png'
            }));

            $scope.map.control.refresh({latitude: $scope.map.center.latitude, longitude: $scope.map.center.longitude});

            google.maps.event.addListenerOnce($scope.getMapObject(), 'tilesloaded', function () {
                $scope.loadPublications(true);

                google.maps.event.addListener($scope.getMapObject(), 'idle', function () {
                    $scope.loadPublications();
                });
            });
        });
    };

    /**
     * Charge les publications dans le champ de la carte, selon les élements du menu
     * @param reset
     */
    $scope.loadPublications = function (reset) {
        reset = reset || false;
        $scope.map.loading = true;

        var params = {
            swLat: $scope.map.bounds.southwest.latitude,
            swLng: $scope.map.bounds.southwest.longitude,
            neLat: $scope.map.bounds.northeast.latitude,
            neLng: $scope.map.bounds.northeast.longitude,
            group: $scope.group
        }

        if (reset) {
            params.reset = 1;
        }

        $http.get($filter('route')('api_v1_get_publications_map', params))
            .success(function (response) {
                angular.forEach(response.publications, function (publication) {
                    $scope.addMarker(publication);
                });

                $scope.map.loading = false;
            })
            .error(function () {
                $scope.map.loading = false;
            })
    }

    $scope.close = function () {
        $instance.close();
    }
}])