/**
 * Created by vincentvalot on 12/05/14.
 */

angular.module('app').controller('ParametersController', ['$scope', '$http', '$timeout', '$filter', '$modal', function ($scope, $http, $timeout, $filter, $modal) {
        $scope.emails = [];
        $scope.notifications = [];
        $scope.sharings = [];

        $scope.addresses = [];
        $scope.locks = [];
        $scope.address = {
            loading: true
        };
        $scope.userlock = {
            loading: true
        };

        $scope.friends = {
            loading: false,
            success: false,
            error  : false,
            value  : false
        };

        $scope.help = {
            loading: false,
            success: false,
            error  : false,
            value  : false
        };

        $scope.initiated = false;

        $scope.init = function () {
            $('.emails-data').children().each(function () {
                $scope.emails.push({
                    id         : $(this).data('id'),
                    type       : $(this).data('type'),
                    periodicity: $(this).data('periodicity'),
                    description: $(this).html(),
                    initWanted : parseInt($(this).data('wanted')),
                    wanted     : parseInt($(this).data('wanted')),
                    loading    : false
                });
            });

            $('.notifications-data').children().each(function () {
                $scope.notifications.push({
                    type       : $(this).data('type'),
                    description: $(this).html(),
                    initWanted : parseInt($(this).data('wanted')),
                    wanted     : parseInt($(this).data('wanted')),
                    loading    : false
                });
            });

            $('.sharings-data').children().each(function () {
                $scope.sharings.push({
                    type       : $(this).data('type'),
                    share      : $(this).data('share'),
                    description: $(this).html(),
                    loading    : false
                });
            });

            $scope.address.loading = true;
            $http.get($filter('route')('api_v1_get_user_addresses'))
                .success(function (response) {
                    $scope.addresses = response.addresses;
                    $scope.address.loading = false;
                });

            $scope.userlock.loading = true;
            $http.get($filter('route')('api_v2_get_user_lock'))
                .success(function (response) {
                    $scope.locks = response.locked;
                    $scope.userlock.loading = false;
                });

            $('.emails-data').remove();
            $('.notifications-data').remove();

            $('.sharings-data').remove();

            $scope.initiated = true;
        };

        $scope.openAddAddressModal = function () {
            var instance = $modal.open({
                templateUrl: 'modal.add-address.html',
                size       : 'lg',
                controller : 'ModalParametersAddAddress'
            });

            instance.result.then(function (params) {
                $scope.addresses.push(params.address);
            });
        };

        $scope.deleteAddress = function ($event, address, $index) {
            $event.stopPropagation();
            $event.preventDefault();

            address.loading = true;

            if (address.timeout) {
                $timeout.cancel(address.timeout);
                address.timeout = null;
            }

            $http._delete($filter('route')('api_v1_delete_user_address', {address: address.id}))
                .success(function () {
                    $scope.addresses.splice($index, 1);

                    address.success = true;
                    address.loading = false;
                })
                .error(function () {
                    address.error = true;
                    address.loading = false;

                    address.timeout = $timeout(function () {
                        address.error = false
                    }, 600);
                });
        };

        $scope.deleteUserLock = function ($event, user, $index) {
            $event.stopPropagation();
            $event.preventDefault();

            user.loading = true;

            if (user.timeout) {
                $timeout.cancel(user.timeout);
                user.timeout = null;
            }

            $http._delete($filter('route')('api_v2_delete_user_lock', {user: user.id}))
                .success(function () {
                    $scope.locks.splice($index, 1);

                    user.success = true;
                    user.loading = false;
                })
                .error(function () {
                    user.error = true;
                    user.loading = false;

                    user.timeout = $timeout(function () {
                        user.error = false
                    }, 600);
                });
        };

        $scope.putFavoriteAddress = function (address) {
            address.loading = true;

            if (address.timeout) {
                $timeout.cancel(address.timeout);
                address.timeout = null;
            }

            $http.put($filter('route')('api_v1_put_user_address_favorite', {address: address.id, favorite: address.favorite ? 0 : 1}))
                .success(function () {
                    address.success = true;

                    var saved = address.favorite;

                    angular.forEach($scope.addresses, function (a) {
                        a.favorite = false;
                    });

                    address.favorite = saved ? false : true;

                    address.loading = false;

                    address.timeout = $timeout(function () {
                        address.success = false
                    }, 600);
                })
                .error(function () {
                    address.error = true;
                    address.loading = false;

                    address.timeout = $timeout(function () {
                        address.error = false
                    }, 600);
                });
        };

        $scope.updateHelp = function () {
            $scope.help.loading = true;

            if ($scope.help.timeout) {
                $timeout.cancel($scope.help.timeout);
                $scope.help.timeout = null;
            }

            $http.put($filter('route')('api_v1_put_user_parameters_help', {help: $scope.help.value}))
                .success(function () {
                    $scope.help.loading = false;
                    $scope.help.success = true;

                    $scope.help.timeout = $timeout(function () {
                        $scope.help.success = false
                    }, 600);
                })
                .error(function () {
                    $scope.help.loading = false;
                    $scope.help.value = !$scope.help.value;

                    $scope.help.error = true;

                    $scope.help.timeout = $timeout(function () {
                        $scope.help.error = false
                    }, 600);
                })
        };

        $scope.updateFriends = function () {
            $scope.friends.loading = true;

            if ($scope.friends.timeout) {
                $timeout.cancel($scope.friends.timeout);
                $scope.friends.timeout = null;
            }

            $http.put($filter('route')('api_v1_put_user_parameters_friends', {friends: $scope.friends.value}))
                .success(function () {
                    $scope.friends.loading = false;
                    $scope.friends.success = true;

                    $scope.friends.timeout = $timeout(function () {
                        $scope.friends.success = false
                    }, 600);
                })
                .error(function () {
                    $scope.friends.loading = false;
                    $scope.friends.value = !$scope.friends.value;

                    $scope.friends.error = true;

                    $scope.friends.timeout = $timeout(function () {
                        $scope.friends.error = false
                    }, 600);
                })
        };

        $scope.updateEmail = function (email) {
            email.loading = true;

            if (email.timeout) {
                $timeout.cancel(email.timeout);
                email.timeout = null;
            }

            $http.put($filter('route')('api_v1_put_users_email_wanted', {model: email.id, wanted: email.wanted}))
                .success(function (data, status, headers, config) {
                    email.loading = false;
                    email.success = true;

                    email.timeout = $timeout(function () {
                        email.success = false
                    }, 600);
                })
                .error(function (data, status, headers, config) {
                    email.loading = false;
                    email.wanted = !email.wanted;

                    email.error = true;

                    email.timeout = $timeout(function () {
                        email.error = false
                    }, 600);
                });
        };

        $scope.updateNotification = function (notification) {
            notification.loading = true;

            if (notification.timeout) {
                $timeout.cancel(notification.timeout);
                notification.timeout = null;
            }

            $http.put($filter('route')('api_v1_put_users_notification_wanted', {type: notification.type, wanted: notification.wanted}))
                .success(function (data, status, headers, config) {
                    notification.loading = false;
                    notification.success = true;

                    notification.timeout = $timeout(function () {
                        notification.success = false
                    }, 600);
                })
                .error(function (data, status, headers, config) {
                    notification.loading = false;
                    notification.wanted = !notification.wanted;

                    notification.error = true;

                    notification.timeout = $timeout(function () {
                        notification.error = false
                    }, 600);
                });
        };

        $scope.updateSharing = function (sharing) {
            sharing.loading = true;

            var data = {};
            data[sharing.type] = {share: sharing.share};

            if (sharing.timeout) {
                $timeout.cancel(sharing.timeout);
                sharing.timeout = null;
            }

            $http.put($filter('route')('api_v1_put_users_parameters'), {'parameters': data})
                .success(function (data, status, headers, config) {
                    sharing.loading = false;
                    sharing.success = true;

                    sharing.timeout = $timeout(function () {
                        sharing.success = false
                    }, 600);
                })
                .error(function (data, status, headers, config) {
                    sharing.loading = false;
                    sharing.error = true;

                    sharing.timeout = $timeout(function () {
                        sharing.error = false
                    }, 600);
                });
        };
    }])
    .controller('ModalParametersAddAddress', ['$scope', '$http', '$controller', '$timeout', '$filter', '$q', '$maputils', '$modalInstance', function ($scope, $http, $controller, $timeout, $filter, $q, $maputils, $instance) {
        $controller('BaseMapController', {$scope: $scope, $q: $q, $maputils: $maputils});

        $scope.marker = {};
        $scope.loading = false;

        $scope.address = {};

        $scope.addAddress = function () {
            $scope.loading = true;

            $http.post($filter('route')('api_v1_post_user_address'), {address: $scope.address})
                .success(function (response) {
                    $scope.loading = false;

                    $instance.close(response);
                })
                .error(function (response) {

                })
        }

        $instance.opened.then(function () {
            $scope.setMapOnUserLocation();
        });

        /**
         * Recherche une adresse tap�e et mets � jour le centre de la carte
         * @param $event
         */
        $scope.searchAddress = function ($event) {
            if (($event && $event.keyCode === 13) || !$event) {
                if ($event) {
                    $event.preventDefault();
                }

                $scope.map.loading = true;
                $maputils.geocode($scope.address.address, function (response) {
                    $scope.address.address = response.address;
                    $scope.address.latitude = response.lat;
                    $scope.address.longitude = response.lng;

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

                    $scope.marker.setPosition(new google.maps.LatLng(response.lat, response.lng))

                    var center = response.viewport.getCenter();
                    $scope.map.control.refresh({latitude: center.lat(), longitude: center.lng()});

                    $scope.map.loading = false;
                }, function () {
                    $scope.map.loading = false;
                });
            }
        }

        $scope.close = function () {
            $instance.dismiss('cancel');
        }

        /**
         * Fonction d'initialisation de la map
         */
        $scope.onMapReady = function () {
            $timeout(function () {
                var position = new google.maps.LatLng($scope.map.center.latitude, $scope.map.center.longitude);

                $scope.map.control.refresh({latitude: $scope.map.center.latitude, longitude: $scope.map.center.longitude});

                $maputils.geocode(position, function (response) {
                    $scope.marker = new google.maps.Marker({
                        position : position,
                        map      : $scope.getMapObject(),
                        draggable: true,
                        icon     : window.location.protocol + '//' + window.location.hostname + '/img/map/map_icon_loc.png'
                    });

                    $scope.$apply(function () {
                        $scope.address.address = response.address;
                        $scope.address.latitude = response.lat;
                        $scope.address.longitude = response.lng;

                        $scope.map.loading = false;
                    });

                    $scope.addMapControls();

                    google.maps.event.addListener($scope.marker, 'dragend', function () {
                        $scope.$apply(function () {
                            $scope.map.loading = true;
                        });

                        $scope.map.control.refresh({latitude: $scope.marker.getPosition().lat(), longitude: $scope.marker.getPosition().lng()});

                        $maputils.geocode($scope.marker.getPosition(), function (response) {
                            $scope.$apply(function () {
                                $scope.address.address = response.address;
                                $scope.address.latitude = $scope.marker.getPosition().lat();
                                $scope.address.longitude = $scope.marker.getPosition().lng();

                                $scope.map.loading = false;
                            });
                        }, function (error) {
                            $scope.$apply(function () {
                                $scope.map.error = $scope.getGeocoderError(error);
                                $scope.map.loading = false;
                            });
                        });
                    });
                });
            });
        }
    }]);
