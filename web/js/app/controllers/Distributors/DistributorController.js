/**
 * Created by vincentvalot on 14/05/14.
 *
 * Gestion de l'ajout d'une distributor
 */

angular.module('app').controller('DistributorController', ['$scope', '$http', '$filter', '$modal', '$timeout', '$location', 'distributorFactory', function ($scope, $http, $filter, $modal, $timeout, $location, $factory) {
        $scope.loaded = false;

        $scope.modal = false;

        $scope.init = function (distributor) {
            var ajax = false;

            if (typeof distributor == 'object') {
                $scope.distributor = distributor;

            } else if (distributor != undefined) {
                ajax = true;
                $http.get($filter('route')('api_admin_get_distributor', {distributor: distributor}))
                        .success(function (data) {
                            $scope.distributor = data.distributor;

                            $scope.loaded = true;
                            $scope.formatDistributor();

                        })
                        .error(function () {

                        });
            }

            if (!ajax) {
                $scope.formatDistributor();
                $scope.loaded = true;
            }
        };

        $scope.formatDistributor = function () {
            $scope.distributor.rows = 1;

            if ($scope.distributor.logo) {
                if ($scope.isModal)
                    $scope.loadOriginalMedia();

                if ($scope.distributor.logo.type == 101) {
                    var path = $scope.distributor.logo.path;

                    $scope.distributor.logo.poster = (path.substr(0, path.lastIndexOf(".")) + '.jpeg');
                    $scope.distributor.logo.mp4 = (path.substr(0, path.lastIndexOf(".")) + '.mp4').replace('resize', 'mp4');
                    $scope.distributor.logo.webm = (path.substr(0, path.lastIndexOf(".")) + '.webm').replace('resize', 'webm');
                    $scope.distributor.logo.ogv = (path.substr(0, path.lastIndexOf(".")) + '.ogv').replace('resize', 'ogv');
                }
            }
        };

        /**
         * Ouvre le modal d'affichage d'une distributor
         *
         * @param $event
         */
        $scope.openDistributorModal = function ($event) {
            if (!$scope.isModal) {
                $scope.modal = $modal.open({
                    templateUrl: 'modal.distributor.html',
                    size: 'lg-full',
                    controller: 'ModalDistributorController',
                    resolve: {
                        distributor: function () {
                            return $scope.distributor;
                        }
                    }
                });

                $scope.modal.result.then(function (data) {
                    if (data && data.remove) {
                        $scope.$emit('npevent-distributor/remove', data.remove);
                    }

                    $scope.modal = false;
                }, function () {
                    $scope.modal = false;
                });
            }
        };

        /**
         * S'occupe du chargement du media original pour une vue plus grande
         */
        $scope.loadOriginalMedia = function () {
            var toLoad = '';

            if ($scope.distributor.logo.type == 100) {
                $scope.distributor.logo.original = $scope.distributor.logo.path.replace('resize', 'original');
                toLoad = $scope.distributor.logo.original;
            } else if ($scope.distributor.logo.type == 101) {
                toLoad = $scope.distributor.logo.poster;
            }

            $scope.distributor.logo.loading = true;

            angular.element("<img />").attr("src", toLoad).load(function (event) {
                $scope.$$phase || $scope.$apply(function () {
                    $scope.distributor.logo.height = event.target.naturalHeight;
                    $scope.distributor.logo.responsiveHeight = Math.floor($scope.distributor.logo.height <= 578 ? 511 : $scope.distributor.logo.height * 511 / 578);
                    $scope.distributor.loading = false;
                    $scope.distributor.logo.loading = false;
                });
            });
        };
    }])

        .controller('ModalDistributorController', ['$scope', '$modalInstance', 'distributor', function ($scope, $instance, distributor) {
                $scope.distributor = distributor;
                $scope.isModal = true;

                $scope.$on('npevent-distributor/remove', function ($event, id) {
                    $instance.close({
                        'remove': id
                    });
                });

                $scope.ok = function () {
                    $instance.close();
                };
            }])
        .controller('ModalMapController', ['$scope', '$controller', '$timeout', '$q', '$maputils', '$modalInstance', 'position', function ($scope, $controller, $timeout, $q, $maputils, $instance, position) {

                $controller('BaseMapController', {$scope: $scope, $q: $q, $maputils: $maputils});

                $scope.map.center = {
                    latitude: position.lat(),
                    longitude: position.lng()
                };

                $scope.onMapReady = function () {
                    $timeout(function () {
                        $scope.marker = new google.maps.Marker({
                            map: $scope.getMapObject(),
                            position: position,
                            icon: window.location.protocol + '//' + window.location.hostname + '/img/map/map_icon_loc.png'
                        });

                        $scope.map.control.refresh({latitude: $scope.map.center.latitude, longitude: $scope.map.center.longitude});

                        $scope.$apply(function () {
                            $scope.map.loading = false;
                        });
                    });
                };

                $instance.opened.then(function () {
                    $scope.map.ready = true;
                    $scope.deferred.mapReady.resolve();
                });

                $scope.ok = function () {
                    $instance.close();
                };

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                };
            }]);
;