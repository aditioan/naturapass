angular.module('app').controller('EditPublicationFormController', ['$rootScope', '$scope', '$http', '$modal', '$timeout', '$filter', '$location', '$facebook', '$maputils', 'factory:Publication', function ($rootScope, $xscope, $http, $modal, $timeout, $filter, $location, $facebook, $maputils, $factory) {
    $xscope.currentPane = 'Publication';
    $xscope.specification = true;
    $xscope.show = false;
    $xscope.publication = $factory.getModel();
    $xscope.publication.showOnFeed = true;
    $xscope.publication.geolocation.active = false;

    $xscope.isLoading = false;

    $xscope.fromMap = false;

    $xscope.map = {
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

    $xscope.marker;
    $xscope.savedGeolocation = false;

    $xscope.fileUploadScope = {};

    $xscope.sharings = [];
    $xscope.currentSharing = {};
    $xscope.defaultSharing = {};

    $xscope.fileProgress = 0;

    $xscope.isFileLoading = false;
    $xscope.hasMediaError = false;
    $xscope.fileUploadOptions = {
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
            $xscope.fileUploadScope = event.targetScope;

            $xscope.$apply(function () {
                $xscope.fileProgress = parseInt(data.loaded / data.total * 100, 10);
            });
        }
    };

    $xscope.tagsOptions = {
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

    $xscope.openObservation = function (data, edit) {

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
                $rootScope.$broadcast('npevent-publication/update', params.publication);
            } else {
                $rootScope.$broadcast('npevent-publication/update', data.publication);
            }
            $xscope.resetForm();
        });
    };

    $xscope.open = function () {
        var modalInstance = $modal.open({
            templateUrl: 'modal.sharing.html',
            size       : 'lg',
            controller : 'ModalSharingController',
            resolve    : {
                sharings: function () {
                    return $xscope.sharings;
                },
                current : function () {
                    return $xscope.currentSharing;
                },
                groups  : function () {
                    return $xscope.savedGroups;
                },
                withouts: function () {
                    return $xscope.savedWithouts;
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
            $xscope.savedGroups = params.groups;
            $xscope.savedWithouts = params.withouts;

            $xscope.publication.groups = [];
            $xscope.publication.sharing.withouts = [];

            $xscope.publication.social = params.social;

            $xscope.changeSharing(params.current)

            $.each(params.groups, function (index, element) {
                $xscope.publication.groups.push(element.id);
            });
            $.each(params.withouts, function (index, element) {
                $xscope.publication.sharing.withouts.push(element.id);
            });
        });
    };

    $xscope.$on('npevent-map/set-geolocation', function ($event, data) {
        $xscope.publication.geolocation.active = true;
        $xscope.savedGeolocation = data.position;

        $xscope.fromMap = true;

        $xscope.toggleGeolocation();
    });

    $xscope.initEdit = function () {
        $xscope.sharings = [];
        angular.element('.sharing-data').first().children().each(function () {
            var sharing = {
                share: $(this).data('sharing'),
                icon : $(this).attr('class'),
                text : $(this).html()
            };

            $xscope.sharings.push(sharing);

            if ($(this).data('sharing') == angular.element('.sharing-data').first().data('default')) {
                $xscope.currentSharing = sharing;
                $xscope.defaultSharing = sharing;
                $xscope.publication.sharing.share = $(this).data('sharing');
            }
        });

    };

    $xscope.resetForm = function () {
        $xscope.publication = {};
        $xscope.publication = $factory.getModel();
        $xscope.publication.showOnFeed = true;

        $xscope.publication.geolocation.active = false;
        $xscope.publication.sharing.share = $xscope.defaultSharing.share;
        $xscope.currentSharing = $xscope.defaultSharing;

        $xscope.savedGroups = [];
        $xscope.savedWithouts = [];

        if (angular.element('[ng-controller="PublicationListController"]').data('group')) {
            $xscope.savedGroups = [
                {
                    id  : angular.element('[ng-controller="PublicationListController"]').data('group'),
                    text: angular.element('[ng-controller="PublicationListController"]').data('name')
                }
            ];
            $xscope.publication.groups = [$xscope.savedGroups[0].id];
        }

        if ($xscope.fileUploadScope.queue) {
            $xscope.fileUploadScope.queue.pop();
            $xscope.fileProgress = 0;
        }

        $('.form-tags-select2').select2('data', []);
        $xscope.isPersisting = false;
    };

    $xscope.buildPublication = function (data) {
        data.publication.savedGroups = $xscope.savedGroups;
        data.publication.savedWithouts = $xscope.savedWithouts;

        if ($xscope.specification) {
            $xscope.openObservation(data, false);
        } else {
            $xscope.resetForm();
            $xscope.$emit('npevent-publication/added', data.publication);
        }

    };
    $xscope.initEdit = function () {
        $xscope.sharings = [];
        angular.element('.sharing-data').first().children().each(function () {
            var sharing = {
                share: $(this).data('sharing'),
                icon : $(this).attr('class'),
                text : $(this).html()
            };
            $xscope.sharings.push(sharing);
            if ($xscope.publication.sharing.share == sharing.share) {
                $xscope.currentSharing = sharing;
                $xscope.defaultSharing = sharing;
                $xscope.publication.sharing.share = $(this).data('sharing');
            }
        });
    };
    $xscope.$on('editPublicationForm', function (event, data) {
        $xscope.publication = data;
        $xscope.backupPublication = angular.copy($xscope.publication);

        if (($xscope.publication.geolocation.latitude != "") && ((typeof $xscope.publication.geolocation.latitude != 'undefined'))) {
            $xscope.publication.geolocation.active = true;
            $xscope.toggleGeolocation(true);
            setTimeout(function () {
                setEditColorSelector($xscope.publication.publicationcolor);
            });
        }
        $xscope.show = true;
        $xscope.initEdit();
    });
    $xscope.openSharingModal = function () {
        var modalInstance = $modal.open({
            templateUrl: 'modal.sharing.html',
            size       : 'lg',
            controller : 'ModalSharingController',
            resolve    : {
                sharings: function () {
                    return $xscope.sharings;
                },
                current : function () {
                    return $xscope.currentSharing;
                },
                groups  : function () {
                    return $xscope.publication.savedGroups;
                },
                withouts: function () {
                    return $xscope.publication.savedWithouts;
                }
            }
        });

        modalInstance.result.then(function (params) {
            $xscope.publication.loading = true;

            $xscope.publication.sharing.share = params.current.share;
            $xscope.currentSharing = params.current;

            $xscope.publication.savedGroups = params.groups;
            $xscope.publication.savedWithouts = params.withouts;

            $xscope.publication.groups = [];
            $xscope.publication.sharing.withouts = [];

            angular.forEach(params.groups, function (value) {
                this.push(value.id);
            }, $xscope.publication.groups);

            angular.forEach(params.withouts, function (value) {
                this.push(value.id);
            }, $xscope.publication.sharing.withouts);
            $xscope.publication.loading = false;
        });
    };
    $xscope.cancelEditing = function () {
        $xscope.publication = angular.copy($xscope.backupPublication);
        $xscope.publication.show = true;
        $xscope.publication.editing = false;
        $("#corps-container").before($("#edit-publication"));
        $xscope.show = false;
        $rootScope.$broadcast('npevent-publication/cancel', $xscope.publication);
        $rootScope.$broadcast('npevent-publication/update', $xscope.publication);
    };
    $xscope.updatePublication = function ($event) {
        $xscope.publication.editing = false;
        if (!$xscope.publication.geolocation.active) {
            delete $xscope.publication.geolocation;
        }
        if ($xscope.publication.editdate) {
            $xscope.publication.date = $xscope.publication.editdate
        }
        $factory.updateMedia($xscope.publication)
            .success(function (response) {
                $xscope.publication.show = true;
                $xscope.resetForm();
                $("#corps-container").before($("#edit-publication"));
                $xscope.show = false;
                $rootScope.$broadcast('finishPublication', $xscope.publication);
                if ($xscope.specification) {
                    var aGroups = [];
                    var aSavedGroups = [];
                    $.each(response.publication.groups, function (index, element) {
                        aGroups.push(element.id);
                        aSavedGroups.push({id: element.id, text: element.name});
                    });
                    response.publication.savedGroups = aSavedGroups;
                    response.publication.groups = aGroups;
                    $xscope.openObservation(response, true);
                }
            })
            .error(function (response) {
                alert("UPDATE FAIL");
            });

    };

    $xscope.changeSharing = function (sharing) {
        $xscope.currentSharing = sharing;
        $xscope.publication.sharing.share = sharing.share;
    }

    $xscope.$on('fileuploadadd', function (event, data) {
        $xscope.fileAdded = true;

        $xscope.fileProgress = 0;

        if (event.targetScope.queue.length) {
            event.targetScope.queue.pop();
        }

        $xscope.fileUploadScope = event.targetScope;

        $xscope.savedGeolocation = false;
        $xscope.isFileLoading = true;
    });

    $xscope.$on('fileuploaddone', function (event, data) {
        if (data.jqXHR.responseJSON.geolocation) {
            $xscope.isLoading = true;
            $xscope.map.loading = true;

            $xscope.savedGeolocation = new google.maps.LatLng(data.jqXHR.responseJSON.geolocation.latitude, data.jqXHR.responseJSON.geolocation.longitude);

            if ($xscope.publication.geolocation.active) {
                $xscope.updateGeolocation(function () {
                    $xscope.$apply(function () {
                        $xscope.isLoading = false;
                        $xscope.map.loading = false;
                    });
                });
            } else {
                $xscope.isLoading = false;
                $xscope.map.loading = false;
            }
        }

        if (data.jqXHR.responseJSON.date) {
            $xscope.$apply(function () {
                $xscope.publication.date = moment(data.jqXHR.responseJSON.date).format("DD/MM/YYYY HH:mm");
                $xscope.publication.sendingDate = moment(data.jqXHR.responseJSON.date).format("DD/MM/YYYY HH:mm");
            });
        }

        $xscope.fileUploadScope = event.targetScope;

        $xscope.isFileLoading = false;

        $timeout(function () {
            $xscope.fileAdded = false;
        }, 5000);
    });

    $xscope.changePane = function (pane) {

        var $replace = angular.element('#tab' + (pane == 'Publication' ? 'Media' : 'Publication')).find('.map-media');

        if (angular.element('#tab' + pane).find('.map-media').length) {
            angular.element('#tab' + pane).find('.map-media').replaceWith($replace);
        } else {
            angular.element('#tab' + pane).find('.map-container').append($replace);
        }

        $xscope.currentPane = pane;
    }

    $xscope.toggleGeolocation = function () {

        $xscope.map.loading = true;
        // var map = $xscope.map.control.getGMap();
        if ($xscope.publication.geolocation.active) {
            setTimeout(function () {
                initMap($xscope.publication.geolocation.latitude, $xscope.publication.geolocation.longitude, "");
            }, 1000)
            $xscope.map.loading = false;

        } else {
            $xscope.publication.publicationcolor = "";
            $xscope.publication.geolocation.address = "";
            $xscope.publication.geolocation.latitude = "";
            $xscope.publication.geolocation.longitude = "";
            $xscope.publication.geolocation.altitude = "";

            $xscope.map.loading = false;
        }
    }

    $xscope.searchAddress = function ($event) {
        if ($event == undefined || ($event && $event.keyCode === 13)) {
            $event ? $event.preventDefault() : '';

            $xscope.map.loading = true;

            $maputils.geocode($xscope.publication.geolocation.address, function (position) {
                $xscope.map.control.refresh({latitude: position.lat, longitude: position.lng});
                $xscope.marker.setPosition(new google.maps.LatLng(position.lat, position.lng));

                $xscope.map.loading = false;

                $xscope.$apply(function () {

                    if (/([A-Za-zÀ-ÿ-]*, ?[A-Za-zÀ-ÿ-]*)$/.test(position.address)) {
                        $xscope.publication.geolocation.address = RegExp.$1;
                    } else {
                        $xscope.publication.geolocation.address = position.address;
                    }

                    $xscope.publication.geolocation.latitude = position.lat;
                    $xscope.publication.geolocation.longitude = position.lng;
                });
            });
        }
    };

    /**
     *
     */
    $xscope.markerDragged = function () {
        var position = $xscope.marker.getPosition();

        $xscope.map.control.refresh({latitude: position.lat(), longitude: position.lng()});

        $maputils.geocode(position, function (response) {
            $xscope.$apply(function () {
                if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test(response.address)) {
                    $xscope.publication.geolocation.address = RegExp.$1;
                } else {
                    $xscope.publication.geolocation.address = response.address;
                }
                $xscope.publication.geolocation.latitude = position.lat();
                $xscope.publication.geolocation.longitude = position.lng();
            });
        });
    }

    /**
     * Mets à jour les données de géolocalisation
     */
    $xscope.updateGeolocation = function (callback) {
        if ($xscope.savedGeolocation instanceof google.maps.LatLng) {
            $xscope.marker.setPosition($xscope.savedGeolocation);
            $xscope.map.control.refresh({
                latitude : $xscope.savedGeolocation.lat(),
                longitude: $xscope.savedGeolocation.lng()
            });

            $maputils.geocode($xscope.savedGeolocation, function (aPosition) {
                $xscope.$apply(function () {
                    $xscope.publication.geolocation.latitude = $xscope.savedGeolocation.lat();
                    $xscope.publication.geolocation.longitude = $xscope.savedGeolocation.lng();

                    $maputils.elevation({locations: [new google.maps.LatLng(aPosition.lat, aPosition.lng)]}, function (elevation) {
                        $xscope.publication.geolocation.altitude = elevation[0].elevation;
                    });

                    if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test(aPosition.address)) {
                        $xscope.publication.geolocation.address = RegExp.$1;
                    } else {
                        $xscope.publication.geolocation.address = aPosition.address;
                    }

                    $xscope.marker.setTitle(aPosition.address);
                });

                $xscope.map.control.refresh({
                    latitude : $xscope.savedGeolocation.lat(),
                    longitude: $xscope.savedGeolocation.lng()
                });

                if (callback) {
                    callback();
                }
            }, callback);
        } else if ($xscope.connectedUser && $xscope.connectedUser.address) {
            $xscope.map.center.latitude = $xscope.connectedUser.address.latitude;
            $xscope.map.center.longitude = $xscope.connectedUser.address.longitude;

            $xscope.marker.setPosition(new google.maps.LatLng($xscope.map.center.latitude, $xscope.connectedUser.address.longitude));
            $xscope.marker.setTitle($xscope.connectedUser.address.address);

            $xscope.publication.geolocation.latitude = $xscope.map.center.latitude;
            $xscope.publication.geolocation.longitude = $xscope.map.center.longitude;
            if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test($xscope.connectedUser.address.address)) {
                $xscope.publication.geolocation.address = RegExp.$1;
            } else {
                $xscope.publication.geolocation.address = $xscope.connectedUser.address.address;
            }
            $xscope.publication.geolocation.altitude = $xscope.map.center.altitude;

            $timeout(function () {
                $xscope.map.control.refresh({
                    latitude : $xscope.map.center.latitude,
                    longitude: $xscope.connectedUser.address.longitude
                });
                $xscope.isLoading = false;
                $xscope.map.loading = false;
            });

        } else {
            $maputils.position(function (aPosition) {
                if ($xscope.publication.geolocation.active) {
                    if (($xscope.publication.geolocation.latitude != "") && ((typeof $xscope.publication.geolocation.latitude != 'undefined'))) {
                        aPosition.lat = $xscope.publication.geolocation.latitude;
                        aPosition.lng = $xscope.publication.geolocation.longitude;
                    }
                }
                $xscope.marker.setPosition(new google.maps.LatLng(aPosition.lat, aPosition.lng));
                $xscope.map.control.refresh({latitude: aPosition.lat, longitude: aPosition.lng});

                $xscope.$apply(function () {
                    $xscope.publication.geolocation.latitude = aPosition.lat;
                    $xscope.publication.geolocation.longitude = aPosition.lng;

                    if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test(aPosition.address)) {
                        $xscope.publication.geolocation.address = RegExp.$1;
                    } else {
                        $xscope.publication.geolocation.address = aPosition.address;
                    }

                    $xscope.marker.setTitle(aPosition.address);
                });

                $maputils.elevation({locations: [new google.maps.LatLng(aPosition.lat, aPosition.lng)]}, function (elevation) {
                    $xscope.publication.geolocation.altitude = elevation[0].elevation;
                });

                $xscope.map.control.refresh({latitude: aPosition.lat, longitude: aPosition.lng});

                if (callback) {
                    callback();
                }
            }, callback);
        }
    }

}])
