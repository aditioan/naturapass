angular.module('app').controller('EditMediaPublicationFormController', ['$scope', '$http', '$modal', '$timeout', '$filter', '$location', '$facebook', '$maputils', 'factory:Publication', function ($escope, $http, $modal, $timeout, $filter, $location, $facebook, $maputils, $factory) {
    $escope.currentPane = 'Publication';
    $escope.specification = true;
    $escope.show = false;
    $escope.publication = $factory.getModel();
    $escope.publication.showOnFeed = true;
    $escope.publication.geolocation.active = false;
    $escope.isLoading = false;

    $escope.fromMap = false;

    $escope.map = {
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

    $escope.marker;
    $escope.savedGeolocation = false;

    $escope.fileUploadScope = {};

    $escope.sharings = [];
    $escope.currentSharing = {};
    $escope.defaultSharing = {};

    $escope.fileProgress = 0;

    $escope.isFileLoading = false;
    $escope.hasMediaError = false;
    $escope.fileUploadOptions = {
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
            $escope.fileUploadScope = event.targetScope;
            $escope.$apply(function () {
                $escope.fileProgress = parseInt(data.loaded / data.total * 100, 10);
            });
        }
    };

    $escope.tagsOptions = {
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

    $escope.openObservation = function (data, edit) {
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
                $escope.$emit('npevent-publication/update', params.publication);
            } else {
                $escope.$emit('npevent-publication/update', data.publication);
            }
            $escope.resetForm();
        });
    };

    $escope.open = function () {
        var modalInstance = $modal.open({
            templateUrl: 'modal.sharing.html',
            size       : 'lg',
            controller : 'ModalSharingController',
            resolve    : {
                sharings: function () {
                    return $escope.sharings;
                },
                current : function () {
                    return $escope.currentSharing;
                },
                groups  : function () {
                    return $escope.savedGroups;
                },
                withouts: function () {
                    return $escope.savedWithouts;
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
            $escope.savedGroups = params.groups;
            $escope.savedWithouts = params.withouts;

            $escope.publication.groups = [];
            $escope.publication.sharing.withouts = [];

            $escope.publication.social = params.social;

            $escope.changeSharing(params.current)

            $.each(params.groups, function (index, element) {
                $escope.publication.groups.push(element.id);
            });
            $.each(params.withouts, function (index, element) {
                $escope.publication.sharing.withouts.push(element.id);
            });
        });
    };

    $escope.$on('npevent-map/set-geolocation', function ($event, data) {
        $escope.publication.geolocation.active = true;
        $escope.savedGeolocation = data.position;

        $escope.fromMap = true;

        $escope.toggleGeolocation();
    });

    $escope.initEditMedia = function () {
        $escope.sharings = [];
        angular.element('.sharing-data').first().children().each(function () {
            var sharing = {
                share: $(this).data('sharing'),
                icon : $(this).attr('class'),
                text : $(this).html()
            };
            $escope.sharings.push(sharing);
            if ($escope.publication.sharing.share == sharing.share) {
                $escope.currentSharing = sharing;
                $escope.defaultSharing = sharing;
                $escope.publication.sharing.share = $(this).data('sharing');
            }
        });

    };
    $escope.openSharingModal = function () {
        var modalInstance = $modal.open({
            templateUrl: 'modal.sharing.html',
            size       : 'lg',
            controller : 'ModalSharingController',
            resolve    : {
                sharings: function () {
                    return $escope.sharings;
                },
                current : function () {
                    return $escope.currentSharing;
                },
                groups  : function () {
                    return $escope.publication.savedGroups;
                },
                withouts: function () {
                    return $escope.publication.savedWithouts;
                }
            }
        });

        modalInstance.result.then(function (params) {
            $escope.publication.loading = true;

            $escope.publication.sharing.share = params.current.share;
            $escope.currentSharing = params.current;

            $escope.publication.savedGroups = params.groups;
            $escope.publication.savedWithouts = params.withouts;

            $escope.publication.groups = [];
            $escope.publication.sharing.withouts = [];

            angular.forEach(params.groups, function (value) {
                this.push(value.id);
            }, $escope.publication.groups);

            angular.forEach(params.withouts, function (value) {
                this.push(value.id);
            }, $escope.publication.sharing.withouts);
            $escope.publication.loading = false;
        });
    };

    $escope.resetForm = function () {
        $escope.publication = {};
        $escope.publication = $factory.getModel();
        $escope.publication.showOnFeed = true;

        $escope.publication.geolocation.active = false;
        $escope.publication.sharing.share = $escope.defaultSharing.share;
        $escope.currentSharing = $escope.defaultSharing;

        $escope.savedGroups = [];
        $escope.savedWithouts = [];

        if (angular.element('[ng-controller="PublicationListController"]').data('group')) {
            $escope.savedGroups = [
                {
                    id  : angular.element('[ng-controller="PublicationListController"]').data('group'),
                    text: angular.element('[ng-controller="PublicationListController"]').data('name')
                }
            ];
            $escope.publication.groups = [$escope.savedGroups[0].id];
        }

        if ($escope.fileUploadScope.queue) {
            $escope.fileUploadScope.queue.pop();
            $escope.fileProgress = 0;
        }

        $('.form-tags-select2').select2('data', []);
        $escope.isPersisting = false;
    };

    $escope.buildPublication = function (data) {
        data.publication.savedGroups = $escope.savedGroups;
        data.publication.savedWithouts = $escope.savedWithouts;

        if ($escope.specification) {
            $escope.openObservation(data, false);
        } else {
            $escope.resetForm();
            $escope.$emit('npevent-publication/added', data.publication);
        }

    };
    $escope.$on('editMediaPublicationForm', function (event, data) {
        $escope.publication = data;
        $escope.backupPublication = angular.copy($escope.publication);
        $escope.show = true;

        if (($escope.publication.geolocation.latitude != "") && ((typeof $escope.publication.geolocation.latitude != 'undefined'))) {
            $escope.publication.geolocation.active = true;
            $escope.toggleGeolocation(true);
            setTimeout(function () {
                setEditMediaColorSelector($escope.publication.publicationcolor);
            });
        }
        $escope.initEditMedia();
    });
    $escope.cancelEditing = function () {
        $escope.show = false;
        $escope.publication = $escope.backupPublication;
        $escope.publication.editing = false;
        $escope.publication.show = true;
        $(".publications").before($("#edit-media-publication"));
        $escope.$emit('npevent-publication/update', $escope.publication);
    };
    $escope.updatePublication = function ($event) {

        $escope.isPersisting = true;

        var tags = $('.form-tags-select2').select2('data');
        $escope.publication.media.tags = [];
        $.each(tags, function (index, element) {
            $escope.publication.media.tags.push(element.id);
        });
        $escope.publication.sending = $escope.publication.content.replace(/<[^>]+>/g, '').replace(
            /(((http|https)\:\/\/)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
            '<a href="$1" target="_blank">$1</a>'
        );
        $escope.publication.sending = $escope.publication.sending.replace(/<[^>]+>/g, '').replace(
            /((www)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
            '<a href="http://$1" target="_blank">$1</a>'
        );

        if ($escope.fromMap) {
            if ($escope.publication.showOnFeed == true) {
                $escope.publication.landmark = false;
            } else {
                $escope.publication.landmark = true;
            }
            delete $escope.publication.showOnFeed;
        }
        if (!$escope.publication.geolocation.active) {
            delete $escope.publication.geolocation;
        }
        if ($escope.publication.editdate) {
            $escope.publication.date = $escope.publication.editdate
        }

        var publication_id = $escope.publication.id;
        $factory.updateMedia($escope.publication)
            .success(function (data, status, headers, config) {
                $escope.show = false;
                $escope.publication.show = true;
                $escope.resetForm();
                $(".publications").before($("#edit-media-publication"));
                $escope.$emit('finishPublication', $escope.publication);
                if (status === 201) {
                    $escope.$emit('npevent-publication/update', data.publication);
                    if ($escope.publication.social && $escope.publication.social.facebook) {
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
                                    $escope.buildPublication(data);
                                })
                                .error(function () {
                                    $escope.buildPublication(data);
                                });
                        }, function (response) {
                            $escope.buildPublication(data);
                        });
                    } else {
                        if ($escope.specification) {

                            $escope.openObservation(data, true);

                        }
                    }
                } else if (status === 202) {
                    $escope.resetForm();
                    $modal.open({
                        templateUrl: 'modal.processing-publication.html',
                        size       : 'lg',
                        controller : 'ModalProcessingPublicationController'
                    });
                }

            })
            .error(function (data, status, headers, config) {
                $escope.isPersisting = false;
            });

    };

    $escope.changeSharing = function (sharing) {
        $escope.currentSharing = sharing;
        $escope.publication.sharing.share = sharing.share;
    }

    $escope.$on('fileuploadadd', function (event, data) {
        $escope.fileAdded = true;

        $escope.fileProgress = 0;

        if (event.targetScope.queue.length) {
            event.targetScope.queue.pop();
        }

        $escope.fileUploadScope = event.targetScope;

        $escope.savedGeolocation = false;
        $escope.isFileLoading = true;
    });

    $escope.$on('fileuploaddone', function (event, data) {
        if (data.jqXHR.responseJSON.geolocation) {
            $escope.isLoading = true;
            $escope.map.loading = true;

            $escope.savedGeolocation = new google.maps.LatLng(data.jqXHR.responseJSON.geolocation.latitude, data.jqXHR.responseJSON.geolocation.longitude);

            if ($escope.publication.geolocation.active) {
                $escope.updateGeolocation(function () {
                    $escope.$apply(function () {
                        $escope.isLoading = false;
                        $escope.map.loading = false;
                    });
                });
            } else {
                $escope.isLoading = false;
                $escope.map.loading = false;
            }
        }

        if (data.jqXHR.responseJSON.date) {
            $escope.$apply(function () {
                $escope.publication.date = moment(data.jqXHR.responseJSON.date).format("DD/MM/YYYY HH:mm");
                $escope.publication.sendingDate = moment(data.jqXHR.responseJSON.date).format("DD/MM/YYYY HH:mm");
            });
        }

        $escope.fileUploadScope = event.targetScope;

        $escope.isFileLoading = false;

        $timeout(function () {
            $escope.fileAdded = false;
        }, 5000);
    });

    $escope.changePane = function (pane) {

        var $replace = angular.element('#tab' + (pane == 'Publication' ? 'Media' : 'Publication')).find('.map-media');

        if (angular.element('#tab' + pane).find('.map-media').length) {
            angular.element('#tab' + pane).find('.map-media').replaceWith($replace);
        } else {
            angular.element('#tab' + pane).find('.map-container').append($replace);
        }

        $escope.currentPane = pane;
    }

    $escope.toggleGeolocation = function () {

        $escope.map.loading = true;
        var map = $escope.map.control.getGMap();

        if ($escope.publication.geolocation.active) {
            if ($escope.marker) {
                $escope.marker.setMap(null);
            }

            $escope.marker = new google.maps.Marker({
                map      : map,
                draggable: true,
                icon     : window.location.protocol + '//' + window.location.hostname + '/img/map/map_icon_loc.png',
            });
            if ($escope.publication.geolocation.latitude && $escope.publication.geolocation.longitude) {
                var myLatLng = new google.maps.LatLng($escope.publication.geolocation.latitude, $escope.publication.geolocation.longitude);
                $escope.savedGeolocation = myLatLng;
            }
            google.maps.event.addListener($escope.marker, 'dragend', $escope.markerDragged);

            $escope.updateGeolocation(function () {
                $escope.$apply(function () {
                    $escope.map.loading = false;
                });
            });

        } else {
            $escope.publication.publicationcolor = "";
            $escope.publication.geolocation.address = "";
            $escope.publication.geolocation.latitude = "";
            $escope.publication.geolocation.longitude = "";
            $escope.publication.geolocation.altitude = "";

            $escope.map.loading = false;
        }
    }

    $escope.searchAddress = function ($event) {
        if ($event == undefined || ($event && $event.keyCode === 13)) {
            $event ? $event.preventDefault() : '';

            $escope.map.loading = true;

            $maputils.geocode($escope.publication.geolocation.address, function (position) {
                $escope.map.control.refresh({latitude: position.lat, longitude: position.lng});
                $escope.marker.setPosition(new google.maps.LatLng(position.lat, position.lng));

                $escope.map.loading = false;

                $escope.$apply(function () {

                    if (/([A-Za-zÀ-ÿ-]*, ?[A-Za-zÀ-ÿ-]*)$/.test(position.address)) {
                        $escope.publication.geolocation.address = RegExp.$1;
                    } else {
                        $escope.publication.geolocation.address = position.address;
                    }

                    $escope.publication.geolocation.latitude = position.lat;
                    $escope.publication.geolocation.longitude = position.lng;
                });
            });
        }
    };

    /**
     *
     */
    $escope.markerDragged = function () {
        var position = $escope.marker.getPosition();

        $escope.map.control.refresh({latitude: position.lat(), longitude: position.lng()});

        $maputils.geocode(position, function (response) {
            $escope.$apply(function () {
                if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test(response.address)) {
                    $escope.publication.geolocation.address = RegExp.$1;
                } else {
                    $escope.publication.geolocation.address = response.address;
                }
                $escope.publication.geolocation.latitude = position.lat();
                $escope.publication.geolocation.longitude = position.lng();
            });
        });
    }

    /**
     * Mets à jour les données de géolocalisation
     */
    $escope.updateGeolocation = function (callback) {
        if ($escope.savedGeolocation instanceof google.maps.LatLng) {
            $escope.marker.setPosition($escope.savedGeolocation);
            $escope.map.control.refresh({
                latitude : $escope.savedGeolocation.lat(),
                longitude: $escope.savedGeolocation.lng()
            });

            $maputils.geocode($escope.savedGeolocation, function (aPosition) {
                $escope.$apply(function () {
                    $escope.publication.geolocation.latitude = $escope.savedGeolocation.lat();
                    $escope.publication.geolocation.longitude = $escope.savedGeolocation.lng();

                    $maputils.elevation({locations: [new google.maps.LatLng(aPosition.lat, aPosition.lng)]}, function (elevation) {
                        $escope.publication.geolocation.altitude = elevation[0].elevation;
                    });

                    if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test(aPosition.address)) {
                        $escope.publication.geolocation.address = RegExp.$1;
                    } else {
                        $escope.publication.geolocation.address = aPosition.address;
                    }

                    $escope.marker.setTitle(aPosition.address);
                });

                $escope.map.control.refresh({
                    latitude : $escope.savedGeolocation.lat(),
                    longitude: $escope.savedGeolocation.lng()
                });

                if (callback) {
                    callback();
                }
            }, callback);
        } else if ($escope.connectedUser && $escope.connectedUser.address) {
            $escope.map.center.latitude = $escope.connectedUser.address.latitude;
            $escope.map.center.longitude = $escope.connectedUser.address.longitude;

            $escope.marker.setPosition(new google.maps.LatLng($escope.map.center.latitude, $escope.connectedUser.address.longitude));
            $escope.marker.setTitle($escope.connectedUser.address.address);

            $escope.publication.geolocation.latitude = $escope.map.center.latitude;
            $escope.publication.geolocation.longitude = $escope.map.center.longitude;
            if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test($escope.connectedUser.address.address)) {
                $escope.publication.geolocation.address = RegExp.$1;
            } else {
                $escope.publication.geolocation.address = $escope.connectedUser.address.address;
            }
            $escope.publication.geolocation.altitude = $escope.map.center.altitude;

            $timeout(function () {
                $escope.map.control.refresh({
                    latitude : $escope.map.center.latitude,
                    longitude: $escope.connectedUser.address.longitude
                });
                $escope.isLoading = false;
                $escope.map.loading = false;
            });

        } else {
            $maputils.position(function (aPosition) {

                if ($escope.publication.geolocation.active) {
                    if (($escope.publication.geolocation.latitude != "") && ((typeof $escope.publication.geolocation.latitude != 'undefined'))) {
                        aPosition.lat = $escope.publication.geolocation.latitude;
                        aPosition.lng = $escope.publication.geolocation.longitude;
                    }
                }
                $escope.marker.setPosition(new google.maps.LatLng(aPosition.lat, aPosition.lng));
                $escope.map.control.refresh({latitude: aPosition.lat, longitude: aPosition.lng});

                $escope.$apply(function () {
                    $escope.publication.geolocation.latitude = aPosition.lat;
                    $escope.publication.geolocation.longitude = aPosition.lng;

                    if (/([A-Za-zÀ-ÿ-]*, [A-Za-zÀ-ÿ-]*)$/.test(aPosition.address)) {
                        $escope.publication.geolocation.address = RegExp.$1;
                    } else {
                        $escope.publication.geolocation.address = aPosition.address;
                    }

                    $escope.marker.setTitle(aPosition.address);
                });

                $maputils.elevation({locations: [new google.maps.LatLng(aPosition.lat, aPosition.lng)]}, function (elevation) {
                    $escope.publication.geolocation.altitude = elevation[0].elevation;
                });

                $escope.map.control.refresh({latitude: aPosition.lat, longitude: aPosition.lng});

                if (callback) {
                    callback();
                }
            }, callback);
        }
    }

}])