(function () {
    angular.module("google-maps.directives.api.utils").service("$maputils", [function () {
        var geocoder = new google.maps.Geocoder();
        var elevator = new google.maps.ElevationService();

        var geocode = function (location, fnCallback, fnErrorCallback) {
            var sending = {};

            if (location instanceof google.maps.LatLng) {
                sending = {latLng: location};
            } else if (typeof location == 'object') {
                sending = {location: location}
            } else {
                sending = {address: location};
            }

            geocoder.geocode(sending, function (results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    if (fnCallback) {
                        fnCallback({
                            lat: results[0].geometry.location.lat(),
                            lng: results[0].geometry.location.lng(),
                            address: results[0].formatted_address,
                            viewport: results[0].geometry.viewport
                        });
                    }
                } else {
                    if (fnErrorCallback) {
                        fnErrorCallback(status);
                    }
                }
            });
        }

        /**
         * Dessine l'icon suivant le type
         *
         * @param {string} url Url de l'image
         * @param {boolean} dragIcon
         */
        var drawMarkerIcon = function (info, url, isOwner, dragIcon) {
            var tw = info.width / 5,
                th = info.width / 7,
                canvas = document.createElement("canvas"),
                context = canvas.getContext("2d"),
                offset = dragIcon === true ? info.icons.dragRadius : 0;

            canvas.setAttribute("width", info.width + info.shadowOffset + offset);
            canvas.setAttribute("height", info.height + info.shadowOffset + offset);

            // shadow
            context.shadowOffsetX = 2;
            context.shadowOffsetY = 2;
            context.shadowBlur = 5;
            context.shadowColor = 'rgba(0, 0, 0, .6)';
            // rectangle
            context.fillStyle = "#fff";
            context.fillRect(offset, offset, info.width, info.width);
            // triangle
            context.moveTo((info.width - tw) * .5 + offset, info.width + offset);
            context.lineTo((info.width + tw) * .5 + offset, info.width + offset);
            context.lineTo(info.width * .5 + offset, info.width + th + offset);
            context.closePath();
            context.fill();

            // end shadow
            context.shadowColor = 'rgba(0, 0, 0, 0)';

            // background (owner|normal)
            context.fillStyle = isOwner ? info.icons.colors.owner : info.icons.colors.normal;
            context.fillRect(offset + 3, offset + 3, info.width - 6, info.width - 6);

            var thumb = new Image();
            thumb.src = url;
            context.drawImage(thumb, 6 + offset, 6 + offset, info.width - 12, info.width - 12);

            if (dragIcon === true) { // drag icon
                var di = new Image();
                di.src = info.icons.move;
                context.drawImage(di, 1, 0, info.icons.dragRadius * 2, info.icons.dragRadius * 2);
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
        var applyMarkerInfos = function(marker, icon, options) {
            if (marker instanceof google.maps.Marker) {
                marker.setOptions(options);
                marker.setIcon(icon);
            } else {
                marker.options = options;
                marker.icon = icon;
            }
        }

        return {
            elevation: function(positions, callback, error_callback) {
                elevator.getElevationForLocations(positions, function(results, status) {
                    if (status == google.maps.ElevationStatus.OK) {
                        if (callback) {
                            callback(results);
                        }
                    } else {
                        if(error_callback) {
                            error_callback(results);
                        }

                    }
                });
            },
            drawMarkerIcon: drawMarkerIcon,
            /**
             * Construit les élements d'icones d'un marker selon son type et le propriétaire de la publication liée
             *
             * @param marker
             * @param type
             * @param url
             * @param isOwner
             */
            setMarkerInfo: function (info, marker, type, url, draggable, isOwner) {
                var deferred = $q.defer();

                setTimeout(function() {
                    var icon = {};
                    var options = {};

                    if (draggable) {
                        switch (type) {
                            case "text":
                                url = info.icons.text;
                                break;
                            case "video":
                                url = info.icons.video;
                                break;
                        }

                        $("<img />").attr("src", url).load(function (event) {
                            icon = {
                                url: drawMarkerIcon(info, event.target.src, isOwner, true),
                                size: new google.maps.Size(info.width + info.shadowOffset + info.icons.dragRadius, info.height + info.shadowOffset + info.icons.dragRadius),
                                anchor: new google.maps.Point(info.width * .5 + info.icons.dragRadius, info.height + info.icons.dragRadius)
                            };

                            options = {
                                visible: true,
                                anchorPoint: new google.maps.Point(0, -info.height)
                            };

                            applyMarkerInfos(marker, icon, options);
                            deferred.resolve();
                        });

                    } else {

                        icon = {
                            size: new google.maps.Size(info.width - info.shrink + info.shadowOffset, info.height - info.shrink + info.shadowOffset),
                            anchor: new google.maps.Point((info.width - info.shrink) * .5, info.height - info.shrink),
                            scaledSize: new google.maps.Size(info.width - info.shrink + info.shadowOffset, info.height - info.shrink + info.shadowOffset)
                        }

                        options = {
                            visible: true,
                            anchorPoint: new google.maps.Point(0, -info.height + info.shrink)
                        };

                        switch (type) {
                            case "text":
                                icon.url = isOwner ? info.icons.data.ownerText : info.icons.data.text;

                                applyMarkerInfos(marker, icon, options);
                                deferred.resolve();
                                break;
                            case "video":
                                icon.url = isOwner ? info.icons.data.ownerVideo : info.icons.data.video;

                                applyMarkerInfos(marker, icon, options);
                                deferred.resolve();
                                break;
                            case "image":
                                $("<img />").attr("src", url).load(function (event) {
                                    icon = {
                                        url: drawMarkerIcon(info, event.target.src, isOwner),
                                        size: new google.maps.Size(info.width + info.shadowOffset, info.height + info.shadowOffset),
                                        anchor: new google.maps.Point(info.width * .5, info.height)
                                    };

                                    options.anchorPoint = new google.maps.Point(0, -info.height);

                                    applyMarkerInfos(marker, icon, options);
                                    deferred.resolve();
                                });
                                break;
                        }

                    }
                });

                return deferred.promise;
            },
            geocode: geocode,
            position: function (fnCallBack, fnErrorCallback) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        geocode(new google.maps.LatLng(position.coords.latitude, position.coords.longitude), fnCallBack, fnErrorCallback);
                    });
                } else {
                    if (fnErrorCallback) {
                        fnErrorCallback();
                    }
                }
            },
            geolocation: function(fnCallBack, fnErrorCallback) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        fnCallBack({
                            'lat': position.coords.latitude,
                            'lng': position.coords.longitude
                        })
                    });
                } else {
                    if (fnErrorCallback) {
                        fnErrorCallback();
                    }
                }
            }
        };
    }
    ]);

}).call(this);