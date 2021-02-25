/**
 * Created by vincentvalot on 14/05/14.
 *
 * Gestion de l'ajout d'une publication
 */

angular.module('app').config([
    '$httpProvider', 'fileUploadProvider',
    function ($httpProvider) {
        delete $httpProvider.defaults.headers.common['X-Requested-With'];
    }
]);

angular.module('app').controller('PublicationFormController', ['$scope', '$http', '$modal', '$timeout', '$filter', '$location', '$facebook', '$maputils', 'factory:Publication', function ($scope, $http, $modal, $timeout, $filter, $location, $facebook, $maputils, $factory) {
        $scope.currentPane = 'Publication';
        $scope.specification = true;

        $scope.publication = $factory.getModel();
        $scope.publication.showOnFeed = true;
        $scope.publication.geolocation.active = false;

        $scope.isLoading = false;

        $scope.fromMap = false;
        $scope.geolocationActive = $scope.publication.geolocation.active;

        $scope.map = {
            center      : {
                latitude : 45,
                longitude: 4
            },
            zoom        : 15,
            loading     : true,
            refresh     : false,
            control     : {},
            window      : {},
            windowScope : {},
            movingMarker: {},
            events      : {}
        };

        $scope.marker;
        $scope.savedGeolocation = false;

        $scope.fileUploadScope = {};

        $scope.sharings = [];
        $scope.currentSharing = {};
        $scope.defaultSharing = {};

        $scope.fileProgress = 0;

        $scope.isFileLoading = false;
        $scope.hasMediaError = false;
        $scope.fileUploadOptions = {
            url                     : $filter('route')('api_v1_post_upload_publication_media'),
            dataType                : 'json',
            autoUpload              : true,
            loadImageFileTypes      : /(\.|\/)(gif|jpe?g|png)$/i,
            loadVideoFileTypes      : /(\.|\/)(ogg|wmv|avi|mov|m4v|flv|3gp|mp4)$/i,
            maxFileSize             : 100 * 1000000,
            maxNumberOfFiles        : 1,
            limitConcurrentUploads  : 1,
            disableImageResize      : /Android(?!.*Chrome)|Opera/
                .test(window.navigator.userAgent),
            disableImageMetaDataSave: false, // Otherwise orientation is broken on iOS Safari
            imageOrientation        : true,
            previewOrientation      : true,
            previewThumbnail        : true,
            previewMaxWidth         : 200,
            previewMaxHeight        : 200,
            previewCrop             : true,
            progress                : function (event, data) {
                $scope.fileUploadScope = event.targetScope;
                $scope.$apply(function () {
                    $scope.fileProgress = parseInt(data.loaded / data.total * 100, 10);
                });
            }
        };

        $("#color-select").on("click", function () {
            $(".dropdown-colorselector").addClass("open");
        })

        $(".publication_publicationcolor").find('option[value=""]').attr("data-color", "#333");
        $(".publication_publicationcolor").colorselector();
        $(".edit_publication_publicationcolor").colorselector();
        $(".edit_media_publication_publicationcolor").colorselector();
        function setColorSelector(val) {
            $(".publication_publicationcolor").colorselector("setValue", val);
        }

        function setEditColorSelector(val) {
            $(".edit_publication_publicationcolor").colorselector("setValue", val);
        }

        function setEditMediaColorSelector(val) {
            $(".edit_media_publication_publicationcolor").colorselector("setValue", val);
        }

        $scope.tagsOptions = {
            placeholder       : $filter('trans')('publication.attributes.tags', {}, 'publication'),
            minimumInputLength: 3,
            multiple          : true,
            createSearchChoice: function (term, data) {
                if ($(data).filter(function () {
                        return this.text.localeCompare(term) === 0;
                    }).length === 0) {
                    return {
                        id  : term,
                        text: term
                    };
                }
            },
            ajax              : {
                url     : $filter('route')('api_v1_get_medias_tags'),
                dataType: 'json',
                data    : function (term, page) {
                    return {
                        name : term,
                        limit: 10, // page size
                        page : page // page number
                    };
                },
                results : function (data, page) {
                    var more = (page * 10) < data.total;
                    return {results: data.tags, more: more};
                }
            }
        };

        $scope.openObservation = function (data, edit) {
            var modalInstance = $modal.open({
                templateUrl: 'modal.observation.html',
                controller : 'ModalObservationController',
                backdrop   : false,
                resolve    : {
                    publication: function () {
                        return data;
                    },
                    edit       : function () {
                        return edit;
                    }
                }
            });

            modalInstance.result.then(function (params) {
                if (params != null) {
                    $scope.$emit('npevent-publication/added', params.publication);
                } else {
                    $scope.$emit('npevent-publication/added', data.publication);
                }
                $scope.resetForm();
            });
        };

        $scope.open = function () {
            var modalInstance = $modal.open({
                templateUrl: 'modal.sharing.html',
                size       : 'lg',
                controller : 'ModalSharingController',
                resolve    : {
                    sharings: function () {
                        return $scope.sharings;
                    },
                    current : function () {
                        return $scope.currentSharing;
                    },
                    groups  : function () {
                        return $scope.savedGroups;
                    },
                    withouts: function () {
                        return $scope.savedWithouts;
                    },
                    social  : function () {
                        return {
                            facebook: false,
                            google  : false
                        }
                    }
                }
            });

            modalInstance.result.then(function (params) {
                $scope.savedGroups = params.groups;
                $scope.savedWithouts = params.withouts;

                $scope.publication.groups = [];
                $scope.publication.sharing.withouts = [];

                $scope.publication.social = params.social;

                $scope.changeSharing(params.current)

                $.each(params.groups, function (index, element) {
                    $scope.publication.groups.push(element.id);
                });
                $.each(params.withouts, function (index, element) {
                    $scope.publication.sharing.withouts.push(element.id);
                });
            });
        };

        $scope.$on('npevent-map/set-geolocation', function ($event, data) {
            $scope.publication.geolocation.active = true;
            $scope.savedGeolocation = data.position;

            $scope.fromMap = true;

            $scope.toggleGeolocation();
        });

        $scope.init = function () {
            angular.element('.sharing-data').first().children().each(function () {
                var sharing = {
                    share: $(this).data('sharing'),
                    icon : $(this).attr('class'),
                    text : $(this).html()
                };

                $scope.sharings.push(sharing);

                if ($(this).data('sharing') == angular.element('.sharing-data').first().data('default')) {
                    $scope.currentSharing = sharing;
                    $scope.defaultSharing = sharing;
                    $scope.publication.sharing.share = $(this).data('sharing');
                }
            });

            if (angular.element('[ng-controller="PublicationListController"]').data('group')) {
                $scope.savedGroups = [{
                    id  : angular.element('[ng-controller="PublicationListController"]').data('group'),
                    text: angular.element('[ng-controller="PublicationListController"]').data('name')
                }];
                $scope.publication.groups = [$scope.savedGroups[0].id];

                $scope.$parent.$broadcast('npevent-publication/menu-locked', true);
            }

            google.maps.event.addDomListener(window, 'load', function () {
                $scope.map.loading = false;
            });
        };

        $scope.resetForm = function () {
            $scope.publication = {};
            $scope.publication = $factory.getModel();
            $scope.publication.showOnFeed = true;

            $scope.publication.geolocation.active = false;
            $scope.publication.sharing.share = $scope.defaultSharing.share;
            $scope.currentSharing = $scope.defaultSharing;

            $scope.savedGroups = [];
            $scope.savedWithouts = [];

            if (angular.element('[ng-controller="PublicationListController"]').data('group')) {
                $scope.savedGroups = [
                    {
                        id  : angular.element('[ng-controller="PublicationListController"]').data('group'),
                        text: angular.element('[ng-controller="PublicationListController"]').data('name')
                    }
                ];
                $scope.publication.groups = [$scope.savedGroups[0].id];
            }

            if ($scope.fileUploadScope.queue) {
                $scope.fileUploadScope.queue.pop();
                $scope.fileProgress = 0;
            }

            $('.form-tags-select2').select2('data', []);
            $scope.isPersisting = false;

            $scope.geolocationActive = $scope.publication.geolocation.active;
        };

        $scope.buildPublication = function (data) {
            data.publication.savedGroups = $scope.savedGroups;
            data.publication.savedWithouts = $scope.savedWithouts;

            if ($scope.specification) {
                $scope.openObservation(data, false);
            } else {
                $scope.resetForm();
                $scope.$emit('npevent-publication/added', data.publication);
            }

        };

        $scope.persistPublication = function () {
            $scope.hasMediaError = $scope.currentPane === 'Media' && angular.element('.files').children().length === 0;
            if ($scope.hasMediaError)
                return;

            $scope.isPersisting = true;

            var tags = $('.form-tags-select2').select2('data');
            $scope.publication.media.tags = [];
            $.each(tags, function (index, element) {
                $scope.publication.media.tags.push(element.id);
            });
            $scope.publication.sending = $scope.publication.content.replace(/<[^>]+>/g, '').replace(
                /(((http|https)\:\/\/)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
                '<a href="$1" target="_blank">$1</a>'
            );
            $scope.publication.sending = $scope.publication.sending.replace(/<[^>]+>/g, '').replace(
                /((www)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
                '<a href="http://$1" target="_blank">$1</a>'
            );

            if ($scope.fromMap) {
                if ($scope.publication.showOnFeed == true) {
                    $scope.publication.landmark = false;
                } else {
                    $scope.publication.landmark = true;
                }
                delete $scope.publication.showOnFeed;
            }

            var groupsTmp = $scope.publication.groups;
            $scope.publication.groups = $scope.publication.groups.join(',');
            $factory.persist($scope.publication, $scope.currentPane === 'Publication')
                .success(function (data, status, headers, config) {
                    $scope.publication.groups = groupsTmp;
                    if (status === 201) {
                        if ($scope.publication.social && $scope.publication.social.facebook) {
                            var picture = '';
                            var prefix = $location.protocol() + '://' + $location.host();

                            if (data.publication.media) {
                                if (data.publication.media.type == 100) {
                                    picture = data.publication.media.path.replace('resize', 'original');
                                } else {
                                    picture = data.publication.media.poster;
                                }
                            }

                            $facebook.ui({
                                method: 'share',
                                href  : prefix + $filter('route')('naturapass_publication_show', {publication: data.publication.id})
                            }).then(function (response) {
                                data.publication.facebook_id = response.post_id;

                                $http.put($filter('route')('api_v1_put_facebook_publication', {
                                        publication: data.publication.id,
                                        fid        : response.post_id
                                    }))
                                    .success(function () {
                                        $scope.buildPublication(data);
                                    })
                                    .error(function () {
                                        $scope.buildPublication(data);
                                    });
                            }, function (response) {
                                $scope.buildPublication(data);
                            });
                        } else {
                            $scope.buildPublication(data);
                        }
                    } else if (status === 202) {
                        $scope.resetForm();

                        $modal.open({
                            templateUrl: 'modal.processing-publication.html',
                            size       : 'lg',
                            controller : 'ModalProcessingPublicationController'
                        });

                    }
                })
                .error(function (data, status, headers, config) {
                    $scope.publication.groups = groupsTmp;
                    $scope.isPersisting = false;
                });
        };

        $scope.changeSharing = function (sharing) {
            $scope.currentSharing = sharing;
            $scope.publication.sharing.share = sharing.share;
        }

        $scope.$on('fileuploadadd', function (event, data) {
            $scope.fileAdded = true;

            $scope.fileProgress = 0;

            if (event.targetScope.queue.length) {
                event.targetScope.queue.pop();
            }

            $scope.fileUploadScope = event.targetScope;

            $scope.savedGeolocation = false;
            $scope.isFileLoading = true;
        });

        $scope.$on('fileuploaddone', function (event, data) {
            if (data.jqXHR.responseJSON.geolocation) {
                $scope.isLoading = true;
                $scope.map.loading = true;

                $scope.savedGeolocation = new google.maps.LatLng(data.jqXHR.responseJSON.geolocation.latitude, data.jqXHR.responseJSON.geolocation.longitude);

                if ($scope.publication.geolocation.active) {
                    $scope.updateGeolocation(function () {
                        $scope.$apply(function () {
                            $scope.isLoading = false;
                            $scope.map.loading = false;
                        });
                    });
                } else {
                    $scope.isLoading = false;
                    $scope.map.loading = false;
                }
            }

            if (data.jqXHR.responseJSON.date) {
                $scope.$apply(function () {
                    $scope.publication.date = moment(data.jqXHR.responseJSON.date).format("DD/MM/YYYY HH:mm");
                    $scope.publication.sendingDate = moment(data.jqXHR.responseJSON.date).format("DD/MM/YYYY HH:mm");
                });
            }

            $scope.fileUploadScope = event.targetScope;

            $scope.isFileLoading = false;

            $timeout(function () {
                $scope.fileAdded = false;
            }, 5000);
        });

        $scope.changePane = function (pane) {

            var $replace = angular.element('#tab' + (pane == 'Publication' ? 'Media' : 'Publication')).find('.map-media');
            if (angular.element('#tab' + pane).find('.map-media').length) {
                angular.element('#tab' + pane).find('.map-media').replaceWith($replace);
            } else {
                angular.element('#tab' + pane).find('.map-container').append($replace);
            }
            $scope.currentPane = pane;
            setTimeout(function () {
                setColorSelector($scope.publication.publicationcolor);
            });
        }

        $scope.toggleGeolocation = function () {
            $scope.map.loading = true;
            $scope.geolocationActive = !$scope.geolocationActive;
            if ($scope.publication.geolocation.active) {

                if ($scope.marker) {
                    $scope.marker.setMap(null);
                }
                $scope.marker = new google.maps.Marker({
                    map      : $scope.map.control.getGMap(),
                    draggable: true,
                    icon     : window.location.protocol + '//' + window.location.hostname + '/img/map/map_icon_loc.png'
                });
                google.maps.event.addListener($scope.marker, 'dragend', $scope.markerDragged);

                $scope.updateGeolocation(function () {
                    $scope.$apply(function () {
                        $scope.map.loading = false;
                    });
                });
            } else {
                // setColorSelector();
                // $scope.publication.publicationcolor = "";
                $scope.publication.geolocation.address = "";
                $scope.publication.geolocation.latitude = "";
                $scope.publication.geolocation.longitude = "";
                $scope.publication.geolocation.altitude = "";
                $scope.map.loading = false;
            }
        }

        $scope.searchAddress = function ($event) {
            if ($event == undefined || ($event && $event.keyCode === 13)) {
                $event ? $event.preventDefault() : '';

                $scope.map.loading = true;

                $maputils.geocode($scope.publication.geolocation.address, function (position) {
                    $scope.map.control.refresh({latitude: position.lat, longitude: position.lng});
                    $scope.marker.setPosition(new google.maps.LatLng(position.lat, position.lng));

                    $scope.map.loading = false;

                    $scope.$apply(function () {

                        if (/([A-Za-zÀ-ÿ-]*, ?[A-Za-zÀ-ÿ-]*)$/.test(position.address)) {
                            $scope.publication.geolocation.address = RegExp.$1;
                        } else {
                            $scope.publication.geolocation.address = position.address;
                        }

                        $scope.publication.geolocation.latitude = position.lat;
                        $scope.publication.geolocation.longitude = position.lng;
                    });
                });
            }
        };

        /**
         *
         */
        $scope.markerDragged = function () {
            var position = $scope.marker.getPosition();

            $scope.map.control.refresh({latitude: position.lat(), longitude: position.lng()});

            $maputils.geocode(position, function (response) {
                $scope.$apply(function () {
                    if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test(response.address)) {
                        $scope.publication.geolocation.address = RegExp.$1;
                    } else {
                        $scope.publication.geolocation.address = response.address;
                    }

                    $scope.publication.geolocation.latitude = position.lat();
                    $scope.publication.geolocation.longitude = position.lng();
                });
            });
        }

        /**
         * Mets à jour les données de géolocalisation
         */
        $scope.updateGeolocation = function (callback) {
            if ($scope.savedGeolocation instanceof google.maps.LatLng) {
                $scope.marker.setPosition($scope.savedGeolocation);
                $scope.map.control.refresh({
                    latitude : $scope.savedGeolocation.lat(),
                    longitude: $scope.savedGeolocation.lng()
                });

                $maputils.geocode($scope.savedGeolocation, function (aPosition) {
                    $scope.$apply(function () {
                        $scope.publication.geolocation.latitude = $scope.savedGeolocation.lat();
                        $scope.publication.geolocation.longitude = $scope.savedGeolocation.lng();

                        $maputils.elevation({locations: [new google.maps.LatLng(aPosition.lat, aPosition.lng)]}, function (elevation) {
                            $scope.publication.geolocation.altitude = elevation[0].elevation;
                        });

                        if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test(aPosition.address)) {
                            $scope.publication.geolocation.address = RegExp.$1;
                        } else {
                            $scope.publication.geolocation.address = aPosition.address;
                        }

                        $scope.marker.setTitle(aPosition.address);
                    });

                    $scope.map.control.refresh({
                        latitude : $scope.savedGeolocation.lat(),
                        longitude: $scope.savedGeolocation.lng()
                    });

                    if (callback) {
                        callback();
                    }
                }, callback);
            } else if ($scope.connectedUser && $scope.connectedUser.address) {
                $scope.map.center.latitude = $scope.connectedUser.address.latitude;
                $scope.map.center.longitude = $scope.connectedUser.address.longitude;

                $scope.marker.setPosition(new google.maps.LatLng($scope.map.center.latitude, $scope.connectedUser.address.longitude));
                $scope.marker.setTitle($scope.connectedUser.address.address);

                $scope.publication.geolocation.latitude = $scope.map.center.latitude;
                $scope.publication.geolocation.longitude = $scope.map.center.longitude;
                if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test($scope.connectedUser.address.address)) {
                    $scope.publication.geolocation.address = RegExp.$1;
                } else {
                    $scope.publication.geolocation.address = $scope.connectedUser.address.address;
                }
                $scope.publication.geolocation.altitude = $scope.map.center.altitude;

                $timeout(function () {
                    $scope.map.control.refresh({
                        latitude : $scope.map.center.latitude,
                        longitude: $scope.connectedUser.address.longitude
                    });
                    $scope.isLoading = false;
                    $scope.map.loading = false;
                });

            } else {
                $maputils.position(function (aPosition) {
                    $scope.marker.setPosition(new google.maps.LatLng(aPosition.lat, aPosition.lng));
                    $scope.map.control.refresh({latitude: aPosition.lat, longitude: aPosition.lng});

                    $scope.$apply(function () {
                        $scope.publication.geolocation.latitude = aPosition.lat;
                        $scope.publication.geolocation.longitude = aPosition.lng;

                        if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test(aPosition.address)) {
                            $scope.publication.geolocation.address = RegExp.$1;
                        } else {
                            $scope.publication.geolocation.address = aPosition.address;
                        }

                        $scope.marker.setTitle(aPosition.address);
                    });

                    $maputils.elevation({locations: [new google.maps.LatLng(aPosition.lat, aPosition.lng)]}, function (elevation) {
                        $scope.publication.geolocation.altitude = elevation[0].elevation;
                    });

                    $scope.map.control.refresh({latitude: aPosition.lat, longitude: aPosition.lng});

                    if (callback) {
                        callback();
                    }
                }, callback);
            }
        }
    }

    ])
    .controller('ModalProcessingPublicationController', ['$scope', '$modalInstance', function ($scope, $instance) {
        $scope.ok = function () {
            $instance.close();
        };
    }]);