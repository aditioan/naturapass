angular.module('app')

    .controller("MapController", ['$rootScope', '$scope', '$controller', '$location', '$http', '$modal', '$timeout', '$q', '$filter', '$maputils', function ($rootScope, $scope, $controller, $location, $http, $modal, $timeout, $q, $filter, $maputils) {
        $controller('BaseMapController', {$scope: $scope, $q: $q, $maputils: $maputils});
        $scope.isInitiated = false;
        $scope.publications = [];
        $scope.publicationClicked = null;
        $scope.makersLabel = [];
        $scope.distributors = [];
        $scope.loadedBounds = [];
        $scope.showlabels = true;
        $scope.photo = false;
        $scope.group = [];
        $scope.sharing = -1;
        $scope.urlPrintableType = 0;
        $scope.reset = false;
        $scope.waiting = false;
        $scope.map.ready = false;
        $scope.listOfImg = [];
        $scope.allImgLoaded = false;
        $scope.map.data = {
            target              : {},
            type                : 'hybrid',
            adding              : false,
            confirmedGeolocation: false,
            input               : ''
        };
        $scope.oldPosition = null;
        $scope.map.window = new google.maps.InfoWindow({
            content: "holding..."
        });
        $scope.deferred.httpRequest = $q.defer();
        $scope.treeOptions = {
            nodeChildren  : "children",
            multiSelection: true,
            dirSelectable : true
        };
        $scope.dataForTheTree = [];
        $scope.selectedCategories = [];
        $scope.modelCategories = [];

        $scope.activeDrawing = true;

        $scope.urlChecked = false;

        $scope.getNodeCategory = function (node, id_category) {
            if (node.id == id_category) {
                return node;
            } else {
                if (node.children.length) {
                    var $return = false;
                    angular.forEach(node.children, function (child) {
                        var check = $scope.getNodeCategory(child, id_category);
                        if (check) {
                            $return = check;
                        }
                    });
                    return $return;
                } else {
                    return false;
                }
            }
        };
        /**
         * Permets de modifier la manière dont est chargée la carte
         */
        $scope.initGoogleMaps = function () {
            $scope.reset = true;
            $scope.deferred.userConnected.promise.then(function () {
                $scope.setMapOnUserLocation();
            });
        };
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
        $scope.onMapReady = function () {
            $timeout(function () {
                    var vars = $location.url().split('/');
                    vars.shift();
                    // console.log(vars);
                    if (vars.length >= 3) {
                        $scope.mapInstance.setCenter(new google.maps.LatLng(vars[0], vars[1]));
                        if (/z[0-9]+/.test(vars[2])) {
                            $scope.mapInstance.setZoom(parseInt(vars[2].replace('z', '')));
                        }
                        // if (/h/.test(vars[3])) {
                        //     $scope.changeMapType('hybrid');
                        // } else if (/r/.test(vars[3])) {
                        //     $scope.changeMapType('roadmap');
                        // } else {
                        //     $scope.changeMapType('cadastre');
                        // }
                        // console.log($scope.mapInstance);
                        if (/s[0-9]+/.test(vars[6])) {
                            var share = vars[6];
                            if (/g[0-9]+(-[0-9]+)*/.test(vars[6])) {
                                share = share.substring(0, vars[6].indexOf('g'));
                            }
                            share = share.replace('s', '');
                            share = share.replace('g', '');
                            $scope.sharing = share;
                        }
                        if (/g[0-9]+(-[0-9]+)*/.test(vars[6])) {
                            var groups = vars[4];
                            if (/s[0-9]+/.test(vars[6])) {
                                groups = groups.substring(vars[6].indexOf('g'));
                            }
                            groups = groups.replace('g', '');
                            $scope.group = $.map(groups.split('-'), Number);
                        }
                        if (/t/.test(vars[5])) {
                            var typeView = vars[5].replace('t', '');
                            if (typeView == 1) {
                                $scope.iconWithoutLabels();
                            } else if (typeView == 2) {
                                $scope.photoWithLabels();
                            } else if (typeView == 3) {
                                $scope.photoWithoutLabels();
                            }
                        }
                    } else {
                        $scope.deferred.userConnected.promise.then(function () {
                            $scope.setMapOnUserLocation();
                        });
                    }
                    $scope.addMapControls();
                    $scope.drawingManagerControl.addDrawingControl();
                    /**
                     * Attends le chargement complet de la carte
                     */
//                        window.console.log(log);
//                        google.maps.event.addListenerOnce($scope.getMapObject(), 'tilesloaded', function () {
//                        $scope.loadPublications(true);
                    /**
                     * Effectue un reload de la carte à chaque mouvement
                     */
                    var dragging = false;
                    google.maps.event.addListener($scope.getMapObject(), 'dragstart', function () {
                        dragging = true;
                    });
                    google.maps.event.addListener($scope.getMapObject(), 'dragend', function () {
                        dragging = false;
                    });
                    google.maps.event.addListener($scope.getMapObject(), 'idle', function () {
                        if ($scope.drawingManagerControl.enableDrawing) {
                            $scope.drawingManagerControl.loadExistedShapes();
                        }
                        if (dragging) {
                            return;
                        }
                        if (!$scope.map.data.adding) {
                            $scope.loadPublications();
                        }
                        $scope.changeFilterMenu();
                    });
//                        });
                    $http.get($filter('route')('api_v2_get_categories_map'))
                        .success(function (data) {
                            //todo prechargement image
                            $scope.dataForTheTree = data.tree;
                            var vars = $location.url().split('/');
                            vars.shift();
                            if (vars.length >= 4) {
                                if (/c/.test(vars[4])) {
                                    var cat = vars[4].replace('c-', '');
                                    angular.forEach(cat.split("-"), function (id_category) {
                                        angular.forEach($scope.dataForTheTree, function (node) {
                                            var check = $scope.getNodeCategory(node, parseInt(id_category));
                                            if (check) {
                                                $scope.selectedCategories.push(check);
                                                $scope.modelCategories.push(check);
                                            }
                                        });
                                    });
                                }
                            }

                            $scope.urlChecked = true;
                            $scope.reset = true;
                            $scope.loadPublications(true);
                        })
                        .error(function (data) {
                            $scope.deferred.httpRequest = false;
                            $scope.urlChecked = true;
                        });
                    $http.get($filter('route')('api_admin_get_distributors', {limit: 10000000}))
                        .success(function (data) {
                            angular.forEach(data.distributors, function (distributor) {
                                //todo prechargement image
                                $scope.addMarkerDistributor(distributor);
                            });
                            $scope.deferred.httpRequest = false;
                        })
                        .error(function (data) {
                            $scope.deferred.httpRequest = false;
                        });
                    // google.maps.event.addListener($scope.map.window, 'closeclick', function () {
                    //     angular.element('.windows-container').append($scope.map.window.getContent());
                    // });
                }, 3000
            )
            ;
        };
        $scope.addLeftMenuListeners();
        /**
         * recharge les données pour l'impression
         */
        $scope.changeFilterMenu = function () {
            if ($scope.mapInstance == null) {
                var url = '#/' + $scope.map.center.latitude + '/' + $scope.map.center.longitude;
                url += $scope.map.zoom;
            } else {
                var url = '#/' + $scope.mapInstance.getCenter().lat() + '/' + $scope.mapInstance.getCenter().lng();
                url += (!jQuery.isEmptyObject($scope.map.control)) ? '/z' + $scope.getMapObject().getZoom() : '/z' + $scope.mapInstance.getZoom();
            }
            url += '/' + $scope.map.data.type.charAt(0);
            url += '/c';
            angular.forEach($scope.selectedCategories, function (category) {
                url += '-' + category.id;
            });
            url += '/t' + $scope.urlPrintableType;
            if ($scope.sharing != -1 || $scope.group) {
                url += '/';
                if ($scope.sharing != -1) {
                    url += 's' + $scope.sharing
                }
                if ($scope.group) {
                    url += 'g' + $scope.group.join('-');
                }
            }
            if ($scope.urlChecked) {
                //window.history.pushState("map", Translator.trans('title.map', {}, 'main'), url);
                window.location.replace(url);
            }
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
                size       : distributor.logo ? 'lg-full' : '',
                controller : 'ModalDistributorController',
                resolve    : {
                    distributor: function () {
                        return distributor;
                    }
                }
            });
            modal.result.then(function (data) {
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
                size       : publication.media ? 'lg-full' : '',
                controller : 'ModalPublicationController',
                resolve    : {
                    publication  : function () {
                        return publication;
                    },
                    connectedUser: function () {
                        return $scope.connectedUser;
                    }
                }
            });
            modal.result.then(function (data) {
                if (data && data.remove) {
                    angular.forEach($scope.publications, function (marker, index) {
                        if (marker.id == data.remove) {
                            $scope.publications.splice(index, 1);
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
                size       : 'lg',
                controller : 'MapAddPublicationController',
                resolve    : {
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
            var marker = {
                id         : distributor.id,
                coords     : {latitude: distributor.geolocation.latitude, longitude: distributor.geolocation.longitude},
                distributor: distributor,
                icon       : distributor.marker
            };
            $scope.distributors.push(marker);
        };
        /**
         * Remove all label marker
         *
         */
        $scope.iconWithoutLabels = function () {
            $scope.map.loading = true;
            $scope.showlabels = false;
            $scope.urlPrintableType = 1;
            if ($scope.photo) {
                angular.forEach($scope.publications, function (marker) {
                    marker.icon = marker.publication.markers.picto;
                });
                $scope.photo = false;
            }
            $('.active', $('.dropdown-view')).removeClass("active");
            $('.icon-view', $('.dropdown-view')).addClass("active");
            angular.forEach($scope.makersLabel, function (marker) {
                marker.options = {};
                if ($scope.connectedUser.id === marker.publication.owner.id) {
                    marker.options.draggable = true;
                }
            });
            $scope.map.loading = false;
            $scope.changeFilterMenu();
        };
        /**
         * Remove all label marker
         *
         */
        $scope.photoWithoutLabels = function () {
            $scope.map.loading = true;
            $scope.showlabels = false;
            $scope.urlPrintableType = 3;
            if (!$scope.photo) {
                angular.forEach($scope.publications, function (marker) {
                    marker.icon = marker.publication.markers.photo;
                });
                $scope.photo = true;
            }
            $('.active', $('.dropdown-view')).removeClass("active");
            $('.icon-photo', $('.dropdown-view')).addClass("active");
            angular.forEach($scope.makersLabel, function (marker) {
                marker.options = {};
                if ($scope.connectedUser.id === marker.publication.owner.id) {
                    marker.options.draggable = true;
                }
            });
            $scope.map.loading = false;
            $scope.changeFilterMenu();
        };
        /**
         * show all label marker
         *
         */
        $scope.iconWithLabels = function () {
            $scope.map.loading = true;
            $scope.showlabels = true;
            $scope.urlPrintableType = 0;
            if ($scope.photo) {
                angular.forEach($scope.publications, function (marker) {
                    marker.icon = marker.publication.markers.picto;
                });
                $scope.photo = false;
            }
            $('.active', $('.dropdown-view')).removeClass("active");
            $('.icon-view-text', $('.dropdown-view')).addClass("active");
            angular.forEach($scope.makersLabel, function (marker) {
                marker.options.labelContent = marker.publication.legend;
                marker.options.labelClass = "marker-labels";
            });
            $scope.map.loading = false;
            $scope.changeFilterMenu();
        };
        /**
         * show all label marker
         *
         */
        $scope.photoWithLabels = function () {
            $scope.map.loading = true;
            $scope.showlabels = true;
            $scope.urlPrintableType = 2;
            if (!$scope.photo) {
                angular.forEach($scope.publications, function (marker) {
                    marker.icon = marker.publication.markers.photo;
                });
                $scope.photo = true;
            }
            $('.active', $('.dropdown-view')).removeClass("active");
            $('.icon-photo-text', $('.dropdown-view')).addClass("active");
            angular.forEach($scope.makersLabel, function (marker) {
                marker.options.labelContent = marker.publication.legend;
                marker.options.labelClass = "marker-labels";
            });
            $scope.map.loading = false;
            $scope.changeFilterMenu();
        };
        /**
         * Ajoute un marker sur la map
         * @param publication
         */
        $scope.addMarker = function (publication) {
            var marker = {
                id         : publication.id,
                coords     : {latitude: publication.geolocation.latitude, longitude: publication.geolocation.longitude},
                publication: publication,
                icon       : ($scope.urlPrintableType == 2 || $scope.urlPrintableType == 3) ? publication.markers.photo : publication.markers.picto,
                options    : {}
            };
            if ($scope.connectedUser.id === publication.owner.id) {
                marker.options.draggable = true;
            }
            if (publication.legend && $scope.showlabels) {
                marker.options.labelContent = publication.legend;
                marker.options.labelClass = "marker-labels";
                $scope.makersLabel.push(marker);
            }
            $scope.publications.push(marker);
        };
        $scope.clickMarkerPublication = function (gMarker, eventName, model) {
            // angular.element('.windows-container').append($scope.map.window.getContent());
            var element = angular.element('.publication-infowindow.infowindow' + model.publication.id);
            element = element.clone(true, true);
            element.removeClass("hide");
            element = element[0];
            $scope.map.window.setContent(element);
            $scope.map.window.open($scope.getMapObject(), gMarker);
        };
        $scope.clickMarkerDistributor = function (gMarker, eventName, model) {
            // angular.element('.windows-container').append($scope.map.window.getContent());
            var element = angular.element('.distributor-infowindow.infowindow' + model.distributor.id);
            element = element.clone(true, true);
            element.removeClass("hide");
            element = element[0];
            $scope.map.window.setContent(element);
            $scope.map.window.open($scope.getMapObject(), gMarker);
        };
        /**
         * Enlève et supprime tous les markers de la map
         */
        $scope.resetMarkers = function () {
            $scope.publications = [];
        };
        /**
         * Charge les publications dans le champ de la carte, selon les élements du menu
         * @param reset
         */


        $scope.$on('editPublication', function ($event, data) {

            angular.forEach($scope.publications, function (element, index) {
                $scope.publications[index].show = true;
                $scope.publications[index].editing = false;
            });
            var editdate = "";
            if (typeof data.date !== typeof undefined) {
                var date = new Date(data.date).toISOString();
                editdate = date.substring(8, 10) + "/" + date.substring(5, 7) + "/" + date.substring(0, 4) + " " + date.substring(11, 13) + ":" + date.substring(14, 16);
            }
            data.editdate = editdate;
            if (data.media == false) {
                $rootScope.$broadcast('editPublicationForm', data);
            }
            else {
                $rootScope.$broadcast('editMediaPublicationForm', data);
            }
        });

        $scope.$on('finishPublication', function ($event, data) {
            $scope.resetMarkers();
            $scope.reset = true;
            $scope.loadPublications(true);

            angular.forEach($scope.publications, function (element, index) {
                $scope.publications[index].editing = false;
            });
            $(".modal-publication-text.pull-right").css("width", "360px");
        });

        $scope.$on('npevent-publication/update', function (event, data) {
            $.each($scope.publications, function (index, publication) {
                if (publication.id == data.id) {
                    data.show = true;
                    data.editing = false;
                    data.publicationcolor = "";

                    if (typeof data.color.id != "undefined") {
                        data.publicationcolor = data.color.id;
                    }

                    if (data.groups[0]) {
                        if (typeof data.groups[0].id != "undefined") {
                            data.savedGroups = [];
                            var groups = [];
                            angular.forEach(data.groups, function (element, index) {

                                data.savedGroups.push({
                                    id  : element.id,
                                    text: element.name
                                });
                                groups.push(element.id);
                            });
                            data.groups = groups;
                        }
                    }

                    if (data.sharing.withouts[0]) {
                        if (typeof data.sharing.withouts[0].id != "undefined") {
                            data.savedWithouts = [];
                            var withouts = [];
                            angular.forEach(data.sharing.withouts, function (element, index) {
                                data.savedWithouts.push({
                                    id  : element.id,
                                    text: element.firstname + ' ' + element.lastname
                                });
                                withouts.push(element.id);
                            });
                            data.sharing.withouts = withouts;
                        }
                    }

                    $scope.publications[index] = data;
                }
            });

            $scope.resetMarkers();
            $scope.reset = true;
            $scope.loadPublications(true);
        });

        $scope.loadPublications = function (reset) {
            reset = reset || false;
            var bounds = angular.copy($scope.getMapObject().getBounds());
            var params = {
                swLat: bounds.getSouthWest().lat(),
                swLng: bounds.getSouthWest().lng(),
                neLat: bounds.getNorthEast().lat(),
                neLng: bounds.getNorthEast().lng()
            };

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
            if ($scope.selectedCategories.length) {
                params.categories = [];
                angular.forEach($scope.selectedCategories, function (category) {
                    params.categories.push(category.id);
                });
            }
            if ($scope.reset) {
                params.reset = "1";
            }
            if (params.sharing == undefined) {
                params.sharing = 3
            }
            $http.get($filter('route')('api_v2_get_publication_map', params), {timeout: $scope.deferred.httpRequest.promise})
                .success(function (data) {
                    $scope.allImgLoaded = false;
                    $scope.listOfImg = [];
                    angular.forEach(data.publications, function (publication) {
                        publication.show = true;
                        publication.publicationcolor = "";
                        if (typeof publication.color.id != "undefined") {
                            publication.publicationcolor = publication.color.id;
                        }
                        publication.savedGroups = [];
                        var groups = [];
                        angular.forEach(publication.groups, function (element, index) {
                            publication.savedGroups.push({
                                id  : element.id,
                                text: element.name
                            });
                            groups.push(element.id);
                        });
                        publication.groups = groups;
                        publication.savedWithouts = [];
                        var withouts = [];
                        angular.forEach(publication.sharing.withouts, function (element, index) {
                            publication.savedWithouts.push({
                                id  : element.id,
                                text: element.firstname + ' ' + element.lastname
                            });
                            withouts.push(element.id);
                        });
                        publication.sharing.withouts = withouts;

                        $scope.listOfImg.push(publication.markers.photo);
                        $scope.listOfImg.push(publication.markers.picto);
                        $scope.addMarker(publication);
                    });
                    $scope.checkImgLoaded();
                    $scope.map.loading = false;
                    $scope.deferred.httpRequest = false;
                    if ($scope.reset) {
                        $scope.drawingManagerControl.hideAllShapes();
                        $scope.drawingManagerControl.shapes = [];
                    }
                    $scope.drawingManagerControl.loadExistedShapes($scope.reset);
                    $scope.reset = false;
                })
                .error(function (data) {
                    $scope.map.loading = false;
                    $scope.deferred.httpRequest = false;
                });

            $scope.allImgLoaded = false;
        };

        /**
         * Ajoute l'overlay cible sur la carte
         */
        $scope.checkImgLoaded = function () {
            var nb = 0;
            //angular.forEach($scope.listOfImg, function (link, index) {
            //    var img = new Image();
            //    img.onload = function () {
            //        nb++;
            //        if (nb == $scope.listOfImg.length) {
            //            $scope.allImgLoaded = true;
            //        }
            //    }
            //    img.src = link;
            //});

            $scope.allImgLoaded = true;
        };

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
         * Change le type de la map de satellite à routière et inversement
         * @param type
         */
        $scope.changeMapType = function (type) {
            $scope.map.data.type = type;
            $('.active', $('.dropdown-map')).removeClass("active");
            if (type === 'roadmap') {
                $scope.map.options.mapTypeId = google.maps.MapTypeId.ROADMAP;
                $scope.mapInstance.setMapTypeId(google.maps.MapTypeId.ROADMAP);
                $('.icon-plan', $('.dropdown-map')).addClass("active");
            } else if (type === 'hybrid') {
                $scope.map.options.mapTypeId = google.maps.MapTypeId.HYBRID;
                $scope.mapInstance.setMapTypeId(google.maps.MapTypeId.HYBRID);
                $('.icon-hybrid', $('.dropdown-map')).addClass("active");
            } else if (type === 'satellite') {
                $scope.map.options.mapTypeId = google.maps.MapTypeId.SATELLITE;
                $scope.mapInstance.setMapTypeId(google.maps.MapTypeId.SATELLITE);
                $('.icon-satellite', $('.dropdown-map')).addClass("active");
            } else if (type === 'terrain') {
                $scope.map.options.mapTypeId = google.maps.MapTypeId.TERRAIN;
                $scope.mapInstance.setMapTypeId(google.maps.MapTypeId.TERRAIN);
                $('.icon-relief', $('.dropdown-map')).addClass("active");
            } else if (type === 'cadastre') {
                $scope.map.options.mapTypeId = 'cadastre';
                $scope.mapInstance.setMapTypeId('cadastre');
                $('.icon-cadastre', $('.dropdown-map')).addClass("active");
            } else {
                $scope.map.options.mapTypeId = '1:25000';
                $scope.mapInstance.setMapTypeId('1:25000');
                $('.icon-ign', $('.dropdown-map')).addClass("active");
            }
            $scope.changeFilterMenu();
        };
        $scope.spiderEvents = {
            click   : function () {
            },
            spiderfy: function (gMarkers, models, crap) {
            }
        };
        $scope.setPosition = function (marker, coords) {
            marker.coords = coords;
            marker.publication.geolocation.latitude = coords.latitude;
            marker.publication.geolocation.longitude = coords.longitude;
        };
        $scope.markersEvents = {
            dragstart: function (marker, eventName, model, args) {
                $scope.oldPosition = new google.maps.LatLng(angular.copy(model.publication.geolocation.latitude), angular.copy(model.publication.geolocation.longitude));
            },
            dragend  : function (marker, eventName, model, args) {
                var position = new google.maps.LatLng($scope.oldPosition.lat(), $scope.oldPosition.lng());
                var newPosition = new google.maps.LatLng(angular.copy(model.publication.geolocation.latitude), angular.copy(model.publication.geolocation.longitude));
                $scope.indexToRemove = -1;
                angular.forEach($scope.publications, function (marker2, index) {
                    if (marker2.id == marker.model.id) {
                        $scope.indexToRemove = index;
                    }
                });
                var confirm = window.confirm("Confirmer ce déplacement?");
                if ($scope.indexToRemove >= 0 && confirm == true) {
                    $scope.publications.splice($scope.indexToRemove, 1);
                    marker.setMap(null);
                    delete marker;
                    $maputils.geocode(newPosition, function (result) {
                        if (/([A-Za-zÀ-ÿ-]*, ?[A-Za-zÀ-ÿ-]*)$/.test(result.address)) {
                            result.address = RegExp.$1;
                        }

                        $http.put($filter('route')('api_v1_put_publication_geolocation', {publication: model.id}), {
                                geolocation: {
                                    latitude : newPosition.lat(),
                                    longitude: newPosition.lng(),
                                    address  : result.address
                                }
                            })
                            .success(function () {
                                $scope.setPosition(model, {latitude: newPosition.lat(), longitude: newPosition.lng()});
                                model.publication.geolocation.address = result.address;
                                $scope.addMarker(model.publication);
                            })
                            .error(function () {
                                model.publication.geolocation.latitude = position.lat();
                                model.publication.geolocation.longitude = position.lng();
                                $scope.addMarker(model.publication);
                            });
                    });
                } else if ($scope.indexToRemove >= 0) {
                    $scope.publications.splice($scope.indexToRemove, 1);
                    marker.setMap(null);
                    delete marker;
                    model.publication.geolocation.latitude = position.lat();
                    model.publication.geolocation.longitude = position.lng();
                    $scope.addMarker(model.publication);
                }
            }
        };
        $(".dropdown-view").click(function (e) {
            $scope.drawingManagerControl.enableDrawing = false;
        });
        $(".dropdown-category").click(function (e) {
            $scope.drawingManagerControl.enableDrawing = false;
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
        $scope.validFilterCategories = function () {
            $scope.drawingManagerControl.enableDrawing = false;
            $scope.selectedCategories = angular.copy($scope.modelCategories);
            $scope.reset = true;
            $scope.loadPublications(true);
            $(".dropdown-parent-category").removeClass("open");
            $scope.changeFilterMenu();
        };
        $scope.closeDropdownCategory = function () {
            $scope.drawingManagerControl.enableDrawing = false;
            $scope.modelCategories = angular.copy($scope.selectedCategories);
        };
        $('.btn-category-filter').on('click', function () {
            $scope.drawingManagerControl.enableDrawing = false;
            if ($('.dropdown-parent-category').hasClass("open")) {
                $scope.closeDropdownCategory();
            }
        });
        $('.dropdown-parent-category').on('hide.bs.dropdown', function () {
            $scope.drawingManagerControl.enableDrawing = false;
            $scope.closeDropdownCategory();
        });

        //DRAWING
        /**
         * Drawing control
         */
        $scope.drawingManagerControl = {
            textStrings      : {
                actionMessage: {
                    noshape    : Translator.trans('map.shape.message.noshape', {}, 'map'),
                    saving     : Translator.trans('map.shape.message.saving', {}, 'map'),
                    saveSuccess: Translator.trans('map.shape.message.save_success', {}, 'map'),
                    saveFail   : Translator.trans('map.shape.message.save_fail', {}, 'map')
                }
            },
            shapes           : [],
            tmpMouveOver     : null,
            drawingManager   : null,
            enableDrawing    : false,
            selectedShape    : null,
            colorPalette     : [["red", "#ff0000"], ["green", "#008000"], ["blue", "#000080"], ["purple", "#800080"], ["orange", "#ff8800"], ["Black", "#000000"], ["DarkBlue", "#00008B"], ["DarkCyan", "#008B8B"], ["DarkGoldenRod", "#B8860B"], ["DarkGray", "#A9A9A9"], ["DarkGreen", "#006400"], ["DarkKhaki", "#BDB76B"], ["DarkMagenta", "#8B008B"], ["DarkOliveGreen", "#556B2F"], ["DarkOrange", "#FF8C00"], ["DarkOrchid", "#9932CC"], ["DarkRed", "#8B0000"], ["DarkSalmon", "#E9967A"], ["DarkSeaGreen", "#8FBC8F"], ["DarkSlateBlue", "#483D8B"], ["DarkSlateGray", "#2F4F4F"], ["DarkTurquoise", "#00CED1"], ["DarkViolet", "#9400D3"], ["AliceBlue", "#F0F8FF"], ["AntiqueWhite", "#FAEBD7"], ["Aqua", "#00FFFF"], ["Aquamarine", "#7FFFD4"], ["Azure", "#F0FFFF"], ["Beige", "#F5F5DC"], ["Bisque", "#FFE4C4"], ["BlanchedAlmond", "#FFEBCD"], ["Blue", "#0000FF"], ["BlueViolet", "#8A2BE2"], ["Brown", "#A52A2A"], ["BurlyWood", "#DEB887"], ["CadetBlue", "#5F9EA0"], ["Chartreuse", "#7FFF00"], ["Chocolate", "#D2691E"], ["Coral", "#FF7F50"], ["CornflowerBlue", "#6495ED"], ["Cornsilk", "#FFF8DC"], ["Crimson", "#DC143C"], ["DeepPink", "#FF1493"], ["DeepSkyBlue", "#00BFFF"], ["DimGray", "#696969"], ["DodgerBlue", "#1E90FF"], ["FireBrick", "#B22222"], ["FloralWhite", "#FFFAF0"], ["ForestGreen", "#228B22"], ["Fuchsia", "#FF00FF"], ["Gainsboro", "#DCDCDC"], ["GhostWhite", "#F8F8FF"], ["Gold", "#FFD700"], ["GoldenRod", "#DAA520"], ["Gray", "#808080"], ["GreenYellow", "#ADFF2F"], ["HoneyDew", "#F0FFF0"], ["HotPink", "#FF69B4"], ["IndianRed", "#CD5C5C"], ["Indigo", "#4B0082"], ["Ivory", "#FFFFF0"], ["Khaki", "#F0E68C"], ["Lavender", "#E6E6FA"], ["LavenderBlush", "#FFF0F5"], ["LawnGreen", "#7CFC00"], ["LemonChiffon", "#FFFACD"], ["LightBlue", "#ADD8E6"], ["LightCoral", "#F08080"], ["LightCyan", "#E0FFFF"], ["LightGoldenRodYellow", "#FAFAD2"], ["LightGray", "#D3D3D3"], ["LightGreen", "#90EE90"], ["LightPink", "#FFB6C1"], ["LightSalmon", "#FFA07A"], ["LightSeaGreen", "#20B2AA"], ["LightSkyBlue", "#87CEFA"], ["LightSlateGray", "#778899"], ["LightSteelBlue", "#B0C4DE"], ["LightYellow", "#FFFFE0"], ["Lime", "#00FF00"], ["LimeGreen", "#32CD32"], ["Linen", "#FAF0E6"], ["Maroon", "#800000"], ["MediumAquaMarine", "#66CDAA"], ["MediumBlue", "#0000CD"], ["MediumOrchid", "#BA55D3"], ["MediumPurple", "#9370DB"], ["MediumSeaGreen", "#3CB371"], ["MediumSlateBlue", "#7B68EE"], ["MediumSpringGreen", "#00FA9A"], ["MediumTurquoise", "#48D1CC"], ["MediumVioletRed", "#C71585"], ["MidnightBlue", "#191970"], ["MintCream", "#F5FFFA"], ["MistyRose", "#FFE4E1"], ["Moccasin", "#FFE4B5"], ["NavajoWhite", "#FFDEAD"], ["OldLace", "#FDF5E6"], ["Olive", "#808000"], ["OliveDrab", "#6B8E23"], ["OrangeRed", "#FF4500"], ["Orchid", "#DA70D6"], ["PaleGoldenRod", "#EEE8AA"], ["PaleGreen", "#98FB98"], ["PaleTurquoise", "#AFEEEE"], ["PaleVioletRed", "#DB7093"], ["PapayaWhip", "#FFEFD5"], ["PeachPuff", "#FFDAB9"], ["Peru", "#CD853F"], ["Pink", "#FFC0CB"], ["Plum", "#DDA0DD"], ["PowderBlue", "#B0E0E6"], ["RosyBrown", "#BC8F8F"], ["RoyalBlue", "#4169E1"], ["SaddleBrown", "#8B4513"], ["Salmon", "#FA8072"], ["SandyBrown", "#F4A460"], ["SeaGreen", "#2E8B57"], ["SeaShell", "#FFF5EE"], ["Sienna", "#A0522D"], ["Silver", "#C0C0C0"], ["SkyBlue", "#87CEEB"], ["SlateBlue", "#6A5ACD"], ["SlateGray", "#708090"], ["Snow", "#FFFAFA"], ["SpringGreen", "#00FF7F"], ["SteelBlue", "#4682B4"], ["Tan", "#D2B48C"], ["Teal", "#008080"], ["Thistle", "#D8BFD8"], ["Tomato", "#FF6347"], ["Turquoise", "#40E0D0"], ["Violet", "#EE82EE"], ["Wheat", "#F5DEB3"], ["White", "#FFFFFF"], ["WhiteSmoke", "#F5F5F5"], ["Yellow", "#FFFF00"], ["YellowGreen", "#9ACD32"]],
            selectedColor    : "#000000",
            drawingOptions   : {
                drawingType          : null,
                drawingMode          : null,//google.maps.drawing.OverlayType.MARKER,
                drawingControl       : false,
                drawingControlOptions: {
                    position    : google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [
                        google.maps.drawing.OverlayType.CIRCLE,
                        google.maps.drawing.OverlayType.POLYGON,
                        google.maps.drawing.OverlayType.POLYLINE,
                        google.maps.drawing.OverlayType.RECTANGLE
                    ]
                },
                polylineOptions      : {
                    editable: true
                },
                rectangleOptions     : {
                    strokeWeight: 1,
                    fillOpacity : 0.45,
                    editable    : true
                },
                circleOptions        : {
                    strokeWeight: 1,
                    fillOpacity : 0.45,
                    editable    : true
                },
                polygonOptions       : {
                    strokeWeight: 1,
                    fillOpacity : 0.45,
                    editable    : true
                }
            },
            controlToggle    : function () {
                this.enableDrawing = !this.enableDrawing;
            },
            activeControl    : function () {
                this.enableDrawing = true;
            },
            addDrawingControl: function () {
                this.drawingOptions.map = $scope.getMapObject();
                this.drawingManager = new google.maps.drawing.DrawingManager(this.drawingOptions);

                google.maps.event.addListener(this.drawingManager, 'overlaycomplete', function (e) {
                    var newShape = e.overlay;
                    newShape.type = e.type;
                    newShape.sharing = 0;
                    newShape.groups = [];
                    newShape.hunts = [];
                    newShape.isOwner = true;
                    newShape.title = $("#shape-title").val();
                    newShape.description = $("#shape-description").val();

                    var resultData = {};

                    switch (e.type) {
                        case google.maps.drawing.OverlayType.CIRCLE:
                            var radius = newShape.getRadius();
                            var center = newShape.getCenter();
                            var bounds = newShape.getBounds();
                            resultData = {center: [center.lat(), center.lng()], radius: radius, bounds: [[bounds.getSouthWest().lat(), bounds.getSouthWest().lng()], [bounds.getNorthEast().lat(), bounds.getNorthEast().lng()]]};

                            google.maps.event.addListener(newShape, 'center_changed', function () {
                                if (!newShape.dragging)
                                    $scope.drawingManagerControl.updateShape(newShape);
                            });

                            google.maps.event.addListener(newShape, 'radius_changed', function () {
                                if (!newShape.dragging)
                                    $scope.drawingManagerControl.updateShape(newShape);
                            });
                            break;

                        case google.maps.drawing.OverlayType.RECTANGLE:
                            var bounds = newShape.getBounds();
                            resultData.bounds = [[bounds.getSouthWest().lat(), bounds.getSouthWest().lng()], [bounds.getNorthEast().lat(), bounds.getNorthEast().lng()]];

                            google.maps.event.addListener(newShape, 'bounds_changed', function () {
                                if (!newShape.dragging)
                                    $scope.drawingManagerControl.updateShape(newShape);
                            });

                            break;
                        default:
                            var vertices = newShape.getPath();

                            var trackerPoints = [];
                            if (vertices) {
                                for (var a = 0; a < vertices.getLength(); a++) {
                                    trackerPoints.push([vertices.getAt(a).lat(), vertices.getAt(a).lng()]);
                                }

                                resultData.paths = trackerPoints;
                            }

                            google.maps.event.addListener(newShape.getPath(), 'set_at', function () {
                                if (!newShape.dragging)
                                    $scope.drawingManagerControl.updateShape(newShape);
                            });

                            google.maps.event.addListener(newShape.getPath(), 'insert_at', function () {
                                if (!newShape.dragging)
                                    $scope.drawingManagerControl.updateShape(newShape);
                            });

                            break;
                    }

                    var color = null;
                    if (newShape.type == google.maps.drawing.OverlayType.POLYLINE) {
                        color = newShape.get('strokeColor');
                    } else {
                        color = newShape.get('fillColor');
                    }

                    resultData.options = {color: color};

                    $scope.drawingManagerControl.shapes.push(newShape);

                    newShape.data = resultData;
                    $scope.drawingManagerControl.addShape(newShape);

                    if (e.type != google.maps.drawing.OverlayType.MARKER) {
                        $scope.drawingManagerControl.drawingManager.setDrawingMode(null);
                        $scope.drawingManagerControl.registerShapeEvents(newShape);
                        $scope.drawingManagerControl.setSelection(newShape);
                    }
                });

                google.maps.event.addListener($scope.getMapObject(), 'click', $scope.drawingManagerControl.clearSelection);
            },

            clearSelection: function () {
                $scope.drawingManagerControl.drawingOptions.drawingType = null;

                //$('.shape-icon.active').removeClass('active');

                $('.has-edit-permission').hide();

                if ($scope.drawingManagerControl.selectedShape) {
                    $scope.drawingManagerControl.selectedShape.setEditable(false);
                    $scope.drawingManagerControl.selectedShape.setDraggable(false);
                    $scope.drawingManagerControl.selectedShape = null;

                    $("#shape-title").val("");
                    $("#shape-description").val("");
                }

                $scope.drawingManagerControl.setActiveSharing($scope.drawingManagerControl.selectedShape);
            },

            setMouseOver: function (shape) {
                this.enableDrawing = true
                //this.clearSelection();
                if (!shape.isOwner) return;

                //this.selectedShape = shape;

                $('.has-edit-permission').show();
                $("#shape-title").val(shape.title);
                $("#shape-description").val(shape.description);

                this.setActiveSharing(shape);
                //
                //shape.setEditable(true);
                //shape.setDraggable(true);
            },

            setSelection: function (shape) {
                this.enableDrawing = true
                this.clearSelection();
                if (!shape.isOwner) return;

                this.selectedShape = shape;

                $('.has-edit-permission').show();
                $("#shape-title").val(this.selectedShape.title);
                $("#shape-description").val(this.selectedShape.description);

                this.setActiveSharing(this.selectedShape);

                shape.setEditable(true);
                shape.setDraggable(true);
            },

            setActiveSharing: function (shape) {
                $(".sharing-link").removeClass("active");
                $(".sharing-groups").removeClass("active");
                $(".sharing-hunts").removeClass("active");

                if (shape && shape.isOwner)
                    $('.has-edit-permission').show();
                else
                    $('.has-edit-permission').hide();

                if (shape) {
                    $(".sharing-" + shape.sharing).addClass("active");
                    $.each(shape.groups, function (index, group) {
                        $(".sharing-group-" + group).addClass("active");
                    });
                    $.each(shape.hunts, function (index, hunt) {
                        $(".sharing-hunt-" + hunt).addClass("active");
                    });
                }

            },

            deleteSelectedShape: function () {
                if (!this.selectedShape) {
                    alert("No shape is selected.");
                    return;
                }

                var shapeId = this.selectedShape.id;

                this.deleteShape(this.selectedShape);
                this.selectedShape.setMap(null);
                this.clearSelection();

                angular.forEach($scope.drawingManagerControl.shapes, function (shape, index) {
                    if (shape.id == shapeId) {
                        $scope.drawingManagerControl.shapes.splice(index, 1);
                        return;
                    }
                });
            },

            addShape: function (shape) {
                shape.title = $("#shape-title").val();
                shape.description = $("#shape-description").val();

                $http.post($filter('route')('api_v2_post_shape'), {
                        shape: {
                            data       : shape.data,
                            type       : shape.type,
                            title      : shape.title,
                            description: shape.description,
                            sharing    : shape.sharing,
                            groups     : shape.groups,
                            hunts      : shape.hunts
                        }
                    })
                    .success(function (data) {
                        if (data && data.shapeId !== undefined) {
                            shape.id = data.shapeId;
                        }
                    })
                    .error(function (data) {
                        console.log("Save error");
                    });
            },

            updateShape: function (shape) {
                if (!this.selectedShape) {
                    alert($scope.drawingManagerControl.textStrings.actionMessage.noshape);
                    return;
                }

                $("#save-result").css("color", "green").html($scope.drawingManagerControl.textStrings.actionMessage.saving);

                if (shape.id === undefined) {
                    $("#save-result").css("color", "red").html("Save fail");
                    setTimeout('$("#save-result").html("");', 1000);
                    return;
                }

                var resultData = {};

                shape.title = $("#shape-title").val();
                shape.description = $("#shape-description").val();

                switch (shape.type) {
                    case google.maps.drawing.OverlayType.CIRCLE:
                        var radius = shape.getRadius();
                        var center = shape.getCenter();
                        var bounds = shape.getBounds();
                        resultData = {center: [center.lat(), center.lng()], radius: radius, bounds: [[bounds.getSouthWest().lat(), bounds.getSouthWest().lng()], [bounds.getNorthEast().lat(), bounds.getNorthEast().lng()]]};
                        break;
                    case google.maps.drawing.OverlayType.RECTANGLE:
                        var bounds = shape.getBounds();
                        resultData.bounds = [[bounds.getSouthWest().lat(), bounds.getSouthWest().lng()], [bounds.getNorthEast().lat(), bounds.getNorthEast().lng()]];
                        break;
                    default:
                        var vertices = shape.getPath();

                        var trackerPoints = [];
                        if (vertices) {
                            for (var a = 0; a < vertices.getLength(); a++) {
                                trackerPoints.push([vertices.getAt(a).lat(), vertices.getAt(a).lng()]);
                            }
                            resultData.paths = trackerPoints;
                        }
                        break;
                }

                var color = null;
                if (shape.type == google.maps.drawing.OverlayType.POLYLINE) {
                    color = shape.get('strokeColor');
                } else {
                    color = shape.get('fillColor');
                }

                resultData.options = {color: color};

                $http.put($filter('route')('api_v2_put_shape', {shape: shape.id}), {
                        shape: {
                            data       : resultData,
                            type       : shape.type,
                            title      : shape.title,
                            description: shape.description,
                            sharing    : shape.sharing,
                            groups     : shape.groups,
                            hunts      : shape.hunts
                        }
                    })
                    .success(function (data) {
                        $("#save-result").css("color", "green").html($scope.drawingManagerControl.textStrings.actionMessage.saveSuccess);
                        setTimeout('$("#save-result").html("");', 1000);
                    })
                    .error(function (data) {
                        console.log("Update error");
                        $("#save-result").css("color", "red").html($scope.drawingManagerControl.textStrings.actionMessage.saveFail);
                        setTimeout('$("#save-result").html("");', 1000);
                    });
            },

            deleteShape: function (shape) {
                if (shape.id === undefined) {
                    return;
                }

                $http._delete($filter('route')('api_v2_delete_shape', {shape: shape.id}))
                    .success(function (data) {
                    })
                    .error(function (data) {
                        console.log("Delete error");
                    });
            },

            selectColor: function (color) {
                this.selectedColor = color;

                var polylineOptions = this.drawingManager.get('polylineOptions');
                polylineOptions.strokeColor = color;
                this.drawingManager.set('polylineOptions', polylineOptions);

                var rectangleOptions = this.drawingManager.get('rectangleOptions');
                rectangleOptions.fillColor = color;
                this.drawingManager.set('rectangleOptions', rectangleOptions);

                var circleOptions = this.drawingManager.get('circleOptions');
                circleOptions.fillColor = color;
                this.drawingManager.set('circleOptions', circleOptions);

                var polygonOptions = this.drawingManager.get('polygonOptions');
                polygonOptions.fillColor = color;
                this.drawingManager.set('polygonOptions', polygonOptions);

                this.setSelectedShapeColor(color);
            },

            setSelectedShapeColor: function (color) {
                if (this.selectedShape) {
                    if (this.selectedShape.type == google.maps.drawing.OverlayType.POLYLINE) {
                        this.selectedShape.set('strokeColor', color);
                    } else {
                        this.selectedShape.set('fillColor', color);
                        this.selectedShape.set('strokeColor', color);
                    }

                    this.updateShape(this.selectedShape);
                }
            },

            loadExistedShapes: function (reset) {
                $scope.map.loading = true;

                var bounds = $scope.getMapObject().getBounds();

                var params = {
                    swLat: bounds.getSouthWest().lat(),
                    swLng: bounds.getSouthWest().lng(),
                    neLat: bounds.getNorthEast().lat(),
                    neLng: bounds.getNorthEast().lng()
                };
                if ($scope.sharing >= 0) {
                    params.sharing = $scope.sharing;
                }
                if ($scope.group.length) {
                    params.groups = $scope.group;
                }

                if (reset) params.reset = 1;
                if (reset) {
                    params.reset = 1;

                    if ($scope.deferred.httpRequest) {
                        $scope.deferred.httpRequest.resolve();
                    }
                }

                $scope.deferred.httpRequest = $q.defer();

                $http.get($filter('route')('api_v2_get_shapes', params), {timeout: $scope.deferred.httpRequest.promise})
                    .success(function (data) {
                        if (!data || data.shapes === undefined) return;

                        $.each(data.shapes, function (index, shape) {
                            var newShape = null;

                            switch (shape.type) {
                                case "circle":
                                    var circleOptions = {
                                        strokeWeight: 1,
                                        strokeColor : shape.data.options.color,
                                        fillColor   : shape.data.options.color,
                                        fillOpacity : 0.45,
                                        editable    : false,
                                        map         : $scope.getMapObject(),
                                        center      : new google.maps.LatLng(shape.data.center[0], shape.data.center[1]),
                                        radius      : shape.data.radius
                                    };

                                    newShape = new google.maps.Circle(circleOptions);
                                    newShape.id = shape.id;
                                    newShape.type = shape.type;

                                    google.maps.event.addListener(newShape, 'center_changed', function () {
                                        if (!newShape.dragging)
                                            $scope.drawingManagerControl.updateShape(newShape);
                                    });

                                    google.maps.event.addListener(newShape, 'radius_changed', function () {
                                        if (!newShape.dragging)
                                            $scope.drawingManagerControl.updateShape(newShape);
                                    });

                                    break;
                                case "rectangle":
                                    var newShape = new google.maps.Rectangle({
                                        strokeWeight: 1,
                                        strokeColor : shape.data.options.color,
                                        fillColor   : shape.data.options.color,
                                        fillOpacity : 0.45,
                                        editable    : false,
                                        map         : $scope.getMapObject(),
                                        bounds      : new google.maps.LatLngBounds(
                                            new google.maps.LatLng(shape.data.bounds[0][0], shape.data.bounds[0][1]),
                                            new google.maps.LatLng(shape.data.bounds[1][0], shape.data.bounds[1][1])
                                        )
                                    });

                                    newShape.id = shape.id;
                                    newShape.type = shape.type;

                                    google.maps.event.addListener(newShape, 'bounds_changed', function () {
                                        if (!newShape.dragging)
                                            $scope.drawingManagerControl.updateShape(newShape);
                                    });

                                    break;
                                default :
                                    var polyCoordinates = [];

                                    $.each(shape.data.paths, function (i, path) {
                                        polyCoordinates.push(new google.maps.LatLng(path[0], path[1]));
                                    });

                                    if (shape.type == "polygon") {
                                        var newShape = new google.maps.Polygon({
                                            path        : polyCoordinates,
                                            strokeWeight: 1,
                                            strokeColor : shape.data.options.color,
                                            fillColor   : shape.data.options.color,
                                            fillOpacity : 0.45,
                                            editable    : false
                                        });
                                    } else {
                                        var newShape = new google.maps.Polyline({
                                            path       : polyCoordinates,
                                            strokeColor: shape.data.options.color,
                                            editable   : false
                                        });
                                    }

                                    newShape.setMap($scope.getMapObject());
                                    newShape.id = shape.id;
                                    newShape.type = shape.type;

                                    google.maps.event.addListener(newShape.getPath(), 'set_at', function () {
                                        if (!newShape.dragging)
                                            $scope.drawingManagerControl.updateShape(newShape);
                                    });

                                    google.maps.event.addListener(newShape.getPath(), 'insert_at', function () {
                                        if (!newShape.dragging)
                                            $scope.drawingManagerControl.updateShape(newShape);
                                    });

                                    break;

                            }

                            if (newShape) {
                                newShape.title = shape.title;
                                newShape.description = shape.description;
                                newShape.sharing = shape.sharing;
                                newShape.isOwner = shape.isOwner;
                                newShape.groups = shape.groups;
                                newShape.hunts = shape.hunts;

                                $scope.drawingManagerControl.shapes.push(newShape);
                                $scope.drawingManagerControl.registerShapeEvents(newShape);
                            }
                        });

                        $scope.map.loading = false;
                        $scope.deferred.httpRequest = false;
                        $scope.drawingManagerControl.loadData = true;
                    })
                    .error(function (data) {
                        console.log("Load error");
                        $scope.map.loading = false;
                        $scope.deferred.httpRequest = false;
                    });
            },

            updateSharing: function (sharing) {
                if (!this.selectedShape) return;

                this.selectedShape.sharing = sharing;
                this.setActiveSharing(this.selectedShape);
            },

            updateGroup: function (group) {
                if (!this.selectedShape) return;
                if (this.selectedShape.groups.indexOf(group) > -1) {
                    this.selectedShape.groups.splice(this.selectedShape.groups.indexOf(group), 1);
                } else {
                    this.selectedShape.groups.push(group);
                }
            },

            updateHunt: function (hunt) {
                if (!this.selectedShape) return;
                if (this.selectedShape.hunts.indexOf(hunt) > -1) {
                    this.selectedShape.hunts.splice(this.selectedShape.hunts.indexOf(hunt), 1);
                } else {
                    this.selectedShape.hunts.push(hunt);
                }
            },

            registerShapeEvents: function (shape) {
                shape.dragging = false;

                google.maps.event.addListener(shape, 'dragstart', function () {
                    shape.dragging = true;
                });

                google.maps.event.addListener(shape, 'dragend', function () {
                    shape.dragging = false;
                    $scope.drawingManagerControl.updateShape(shape);
                });

                google.maps.event.addListener(shape, 'click', function () {
                    $scope.drawingManagerControl.enableDrawing = true;
                    $scope.drawingManagerControl.setSelection(shape);
                });

                google.maps.event.addListener(shape, 'mouseover', function () {
                    $scope.drawingManagerControl.enableDrawing = true;
                    //$("#shape-title").val(shape.title);
                    //$("#shape-description").val(shape.description);
                    //$scope.drawingManagerControl.setActiveSharing(shape);
                    $scope.drawingManagerControl.setMouseOver(shape);
                });

                google.maps.event.addListener(shape, 'mouseout', function () {
                    $scope.drawingManagerControl.enableDrawing = true;
                    if ($scope.drawingManagerControl.selectedShape !== null) {
                        $scope.drawingManagerControl.setSelection($scope.drawingManagerControl.selectedShape);
                    } else {
                        $scope.drawingManagerControl.setActiveSharing($scope.drawingManagerControl.selectedShape);

                        if ($scope.drawingManagerControl.selectedShape) {
                            $("#shape-title").val($scope.drawingManagerControl.selectedShape.title);
                            $("#shape-description").val($scope.drawingManagerControl.selectedShape.description);

                            return;
                        }
                        $("#shape-title").val("");
                        $("#shape-description").val("");
                    }
                });
            },

            startNewShape: function (obj, type) {
                this.clearSelection();
                this.drawingOptions.drawingType = type;
                var drawingMode = google.maps.drawing.OverlayType.POLYGON;
                var overlayOption = "polygonOptions";

                switch (type) {
                    case "circle":
                        drawingMode = google.maps.drawing.OverlayType.CIRCLE;
                        overlayOption = "circleOptions";
                        break;
                    case "rectangle":
                        drawingMode = google.maps.drawing.OverlayType.RECTANGLE;
                        overlayOption = "rectangleOptions";
                        break;
                    case "polyline":
                        drawingMode = google.maps.drawing.OverlayType.POLYLINE;
                        overlayOption = "polylineOptions";
                        break;
                }

                var drawingOption = {
                    strokeWeight: 1,
                    strokeColor : this.selectedColor,
                    fillColor   : this.selectedColor,
                    fillOpacity : 0.45,
                    editable    : true
                };

                this.drawingManager.setDrawingMode(drawingMode);
                this.drawingManager.set(overlayOption, drawingOption);
                this.enableDrawing = true;
            },

            hideAllShapes: function () {
                angular.forEach($scope.drawingManagerControl.shapes, function (shape, index) {
                    shape.setMap(null);
                });
            },
            showAllShapes: function () {
                if (angular.isUndefined(this.loadData)) {
                    $scope.drawingManagerControl.loadExistedShapes(1);
                    return;
                }

                angular.forEach($scope.drawingManagerControl.shapes, function (shape, index) {
                    shape.setMap($scope.getMapObject());
                });
            },
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
                $scope.$broadcast('npevent-map/set-geolocation', {position: position});
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
