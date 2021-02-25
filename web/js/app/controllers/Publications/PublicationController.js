/**
 * Created by vincentvalot on 14/05/14.
 *
 * Gestion de l'ajout d'une publication
 */

angular.module('app').controller('PublicationController', ['$scope', '$http', '$filter', '$modal', '$timeout', '$location', '$facebook', 'factory:Publication', 'factory:PublicationComment', function ($scope, $http, $filter, $modal, $timeout, $location, $facebook, $factoryPublication, $factoryPublicationComment) {
        $scope.loaded = false;
        $scope.sharings = [];

        $scope.modal = false;

        var preProcessData = function () {
            angular.element('.sharing-data').first().children().each(function () {
                var sharing = {
                    share: $(this).data('sharing'),
                    icon : $(this).attr('class'),
                    text : $(this).html()
                };

                $scope.sharings.push(sharing);

                if ($scope.publication.sharing.share == sharing.share) {
                    $scope.currentSharing = sharing;
                }
            });
            $scope.publication.shareUsersName = $scope.publication.shareusers.map(function (u) {
                return u.fullname;
            }).join(',')
        };

        $scope.init = function (publication) {
            var ajax = false;

            if (typeof publication == 'object') {
                $scope.publication = publication;

            } else if (publication != undefined) {
                ajax = true;
                $factoryPublication.get(publication)
                    .success(function (data) {
                        $scope.publication = data.publication;

                        preProcessData();
                        $scope.loaded = true;
                        $scope.formatPublication();

                    })
                    .error(function () {

                    });
            }

            if (!ajax) {
                $scope.formatPublication();
                $scope.loaded = true;

                preProcessData();
            }
        }

        $scope.formatPublication = function () {
            $scope.publication.rows = 1;
            $scope.publication.comments.adding = {};
            $scope.publication.comments.adding.rows = 1;

            $scope.publication.comments.unloadedHTML = $filter('transchoice')('label.show_other', $scope.publication.comments.unloaded, {'count': $scope.publication.comments.unloaded}, 'publication');

            if ($scope.publication.media) {
                if ($scope.isModal)
                    $scope.loadOriginalMedia();

                if ($scope.publication.media.type == 101) {
                    var path = $scope.publication.media.path;

                    $scope.publication.media.poster = (path.substr(0, path.lastIndexOf(".")) + '.jpeg');
                    $scope.publication.media.mp4 = (path.substr(0, path.lastIndexOf(".")) + '.mp4').replace('resize', 'mp4');
                    $scope.publication.media.webm = (path.substr(0, path.lastIndexOf(".")) + '.webm').replace('resize', 'webm');
                    $scope.publication.media.ogv = (path.substr(0, path.lastIndexOf(".")) + '.ogv').replace('resize', 'ogv');
                }
            }
        }

        $scope.stopEditingPublication = function () {
            if ($scope.publication.editing) {
                $scope.publication.content = $scope.publication.oldContent;
                $scope.publication.editing = false;
            }
        }

        $scope.startEditingPublication = function ($event) {
            $scope.$emit('editPublication', $scope.publication);
            var dataForm = $("#edit-media-publication");
            if ($scope.publication.media == false) {
                var dataForm = $("#edit-publication");
            }
            $scope.publication.editing = true;
            angular.element($event.currentTarget).parent().parent().parent().parent().find(".media-link").before(dataForm);
            // dataForm.fadeIn(1000);
        }

        $scope.editingPublication = function ($event) {
            if ($event.keyCode === 8 || $event.keyCode === 46) {
                $scope.publication.rows = ($scope.publication.content.split(/\r\n|\n|\r/) || []).length;
            } else if ($event.keyCode === 13 && $scope.lastKey === 16) {
                $scope.publication.rows += 1;
            } else if ($event.keyCode === 27) {
                $scope.stopEditingPublication();
            } else if ($event.keyCode === 13) {
                $event.preventDefault();

                if ($scope.publication.content.length > 0 && /\S/.test($scope.publication.content)) {
                    $scope.publication.loading = true;

                    $scope.publication.hasError = false;
                    $scope.publication.error = "";

                    $scope.publication.sending = $scope.publication.content.replace(
                        /(((http|https)\:\/\/)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
                        '<a href="$1" target="_blank">$1</a>'
                    );
                    $scope.publication.sending = $scope.publication.sending.replace(
                        /((www)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
                        '<a href="http://$1" target="_blank">$1</a>'
                    );

                    $factoryPublication.update($scope.publication)
                        .success(function (response) {
                            $scope.publication.content = $scope.publication.sending;

                            $scope.publication.sending = false;
                            $scope.publication.oldContent = false;

                            $scope.publication.loading = false;
                            $scope.publication.editing = false;
                        })
                        .error(function (response) {
                            $scope.publication.loading = false;

                            $scope.publication.hasError = true;
                            $scope.publication.error = data[0].message;
                        });
                } else {
                    $scope.publication.hasError = true;
                }
            }

            $scope.lastKey = $event.keyCode;
        }

        $scope.doShareOnFacebook = function () {
            var picture = '';
            var prefix = $location.protocol() + '://' + $location.host();

            if ($scope.publication.media) {
                if ($scope.publication.media.type == 100) {
                    picture = $scope.publication.media.path.replace('resize', 'original');
                } else {
                    picture = $scope.publication.media.poster;
                }
            }

            $facebook.ui({
                method : 'feed',
                link   : prefix + $filter('route')('naturapass_publication_show', {publication: $scope.publication.id}),
                picture: prefix + picture,
                caption: $scope.publication.content,
                name   : 'NaturaPass'
            }).then(function (response) {
                $scope.publication.loading = false;
            }, function (response) {
                $scope.publication.loading = false;
            });
        }

        $scope.shareOnFacebook = function () {
            $scope.publication.loading = true;

            $facebook.getLoginStatus().then(function (response) {
                if (response.status == "not_authorized") {
                    $facebook.login().then(function (response) {
                        $http.put($filter('route')('api_v1_put_facebook_user'), {user: {facebook_id: fid}});
                        $scope.doShareOnFacebook(response);
                    }, function (error) {
                        $scope.publication.loading = false;
                    });
                } else if (response.status == "connected") {
                    $scope.doShareOnFacebook(response);
                }
            }, function (error) {
                $scope.publication.loading = false;
            });
        }

        /**
         * Mets le focus du navigateur sur le commentaire en particulier
         */
        $scope.focusOnComment = function () {
            $scope.focusComment = true;

            $timeout(function () {
                $scope.focusComment = false;
            }, 1000);
        }

        /**
         * Ouvre le modal de reporting d'une publication
         */
        $scope.report = function () {
            $modal.open({
                templateUrl: 'modal.report-publication.html',
                size       : 'lg',
                controller : 'ModalReportPublicationController',
                resolve    : {
                    publication: function () {
                        return $scope.publication;
                    }
                }
            });
        }

        /**
         * Ouvre le modal d'affichage d'une publication
         *
         * @param $event
         */
        $scope.openPublicationModal = function ($event) {
            if (!$scope.isModal) {
                $scope.modal = $modal.open({
                    templateUrl: 'modal.publication.html',
                    size       : 'lg-full',
                    controller : 'ModalPublicationController',
                    resolve    : {
                        publication  : function () {
                            return $scope.publication;
                        },
                        connectedUser: function () {
                            return $scope.connectedUser;
                        }
                    }
                });

                $scope.modal.result.then(function (data) {
                    if (data && data.remove) {
                        $scope.$emit('npevent-publication/remove', data.remove);
                    }

                    angular.forEach($scope.sharings, function (element) {

                        if (element.share == $scope.publication.sharing.share) {
                            $scope.currentSharing = element;
                            return;
                        }
                    });

                    $scope.modal = false;
                }, function () {
                    angular.forEach($scope.sharings, function (element) {

                        if (element.share == $scope.publication.sharing.share) {
                            $scope.currentSharing = element;
                            return;
                        }
                    });

                    $scope.modal = false;
                });
            }
        }

        /**
         * S'occupe du chargement du media original pour une vue plus grande
         */
        $scope.loadOriginalMedia = function () {
            var toLoad = '';

            if ($scope.publication.media.type == 100) {
                $scope.publication.media.original = $scope.publication.media.path.replace('resize', 'original');
                toLoad = $scope.publication.media.original;
            } else if ($scope.publication.media.type == 101) {
                toLoad = $scope.publication.media.poster;
            }

            $scope.publication.media.loading = true;

            angular.element("<img />").attr("src", toLoad).load(function (event) {
                $scope.$$phase || $scope.$apply(function () {
                    $scope.publication.media.height = event.target.naturalHeight;
                    $scope.publication.media.responsiveHeight = Math.floor($scope.publication.media.height <= 578 ? 511 : $scope.publication.media.height * 511 / 578);
                    $scope.publication.loading = false;
                    $scope.publication.media.loading = false;
                });
            });
        }

        /**
         * Envoi la rotation d'image au serveur
         *
         * @param degree
         */
        $scope.rotateImage = function (degree) {
            $scope.publication.media.loading = true;

            $factoryPublication.rotate($scope.publication.id, degree)
                .success(function (data) {
                    $scope.publication.media = data.media;

                    if ($scope.isModal) {
                        $scope.loadOriginalMedia();
                    } else {
                        $scope.publication.media.loading = false;
                    }
                })
                .error(function () {
                    $scope.publication.media.loading = false;
                });
        };

        /**
         * Arrêt de l'édition d'un média
         */
        $scope.disableMediaEditing = function () {
            $scope.publication.media.rotation = false;
            $scope.publication.media.cropping = false;
            $scope.publication.media.cropStep2 = false;
            $scope.publication.media.loading = false;
        }

        /**
         * Validation d'un crop de média
         */
        $scope.validateCropping = function () {
            $scope.publication.media.loading = true;

            if ($scope.publication.media.crop) {
                $factoryPublication.crop($scope.publication.id, $scope.publication.media.crop)
                    .success(function (data) {
                        $scope.publication.media = data.media;
                        $scope.publication.media.cropping = false;
                        $scope.publication.media.cropStep2 = false;

                        if ($scope.isModal) {
                            $scope.loadOriginalMedia();
                        } else {
                            $scope.publication.media.loading = false;
                        }
                    })
                    .error(function () {
                        $scope.publication.media.loading = false;
                    });
            } else {
                $scope.publication.media.cropping = false;
                $scope.publication.media.loading = false;
            }
        }

        /**
         * Débuter un crop sur une image
         *
         * @param coords
         */
        $scope.cropping = function (coords) {
            $scope.publication.media.crop = coords;
        }

        /**
         *
         */
        $scope.cropReleased = function () {
            $scope.publication.media.crop = false;
        }

        $scope.openSharingModal = function () {
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
                        return $scope.publication.savedGroups;
                    },
                    withouts: function () {
                        return $scope.publication.savedWithouts;
                    }
                }
            });

            modalInstance.result.then(function (params) {
                $scope.publication.loading = true;

                $scope.publication.sharing.share = params.current.share;
                $scope.currentSharing = params.current;

                $scope.publication.savedGroups = params.groups;
                $scope.publication.savedWithouts = params.withouts;

                $scope.publication.groups = [];
                $scope.publication.sharing.withouts = [];

                angular.forEach(params.groups, function (value) {
                    this.push(value.id);
                }, $scope.publication.groups);

                angular.forEach(params.withouts, function (value) {
                    this.push(value.id);
                }, $scope.publication.sharing.withouts);

                $factoryPublication.update($scope.publication)
                    .success(function () {
                        $scope.publication.loading = false;
                    })
                    .error(function () {
                        $scope.publication.loading = false;
                    });
            });
        };

        $scope.openMapModal = function () {
            $modal.open({
                templateUrl: 'modal.map.html',
                size       : 'lg',
                controller : 'ModalMapController',
                resolve    : {
                    position: function () {
                        return new google.maps.LatLng($scope.publication.geolocation.latitude, $scope.publication.geolocation.longitude);
                    },
                    icon    : function () {
                        return $scope.publication.markers.picto;
                    }
                }
            });
        };
        $scope.openObservationModal = function () {
            $modal.open({
                templateUrl: 'modal.show-observation.html',
                size       : 'lg',
                controller : 'ModalShowObservationController',
                resolve    : {
                    publication   : function () {
                        return $scope.publication;
                    },
                    currentSharing: function () {
                        return $scope.currentSharing;
                    }
                }
            });
        };

        $scope.openLikeModal = function () {
            $modal.open({
                controller : 'ModalActionController',
                templateUrl: 'modal.like.html',
                resolve    : {
                    publication: function () {
                        return $scope.publication;
                    }
                }
            });
        };

        $scope.loadComments = function () {
            $scope.publication.comments.loading = true;

            var toLoad = $scope.publication.comments.unloaded <= 20 ? $scope.publication.comments.unloaded : 20;

            $factoryPublicationComment.get($scope.publication.id, {
                    'loaded': $scope.publication.comments.data.length,
                    'limit' : toLoad
                })
                .success(function (data) {
                    if (data.loaded) {
                        $.each(data.comments, function (index, comment) {
                            $scope.publication.comments.data.unshift(comment);
                        });
                    }

                    $scope.publication.comments.unloaded -= data.loaded;
                    if ($scope.publication.comments.unloaded) {
                        $scope.publication.comments.unloadedHTML = $filter('transchoice')('label.show_other', $scope.publication.comments.unloaded, {'count': $scope.publication.comments.unloaded}, 'publication');
                    }

                    $scope.publication.comments.loading = false;
                })
                .error(function () {
                    $scope.publication.comments.loading = false;
                });
        }

        $scope.remove = function () {
            $scope.publication.loading = true;

            $factoryPublication.remove($scope.publication.id)
                .success(function () {
                    $scope.$emit('npevent-publication/remove', $scope.publication.id);

                    $scope.publication.loading = false;

                    if ($scope.modal)
                        $scope.modal.close();
                })
                .error(function () {
                    $scope.publication.loading = false;
                })
        };

        $scope.lockUser = function () {
            var $instance = $modal.open({
                controller : 'ModalLockUserController',
                templateUrl: 'modal.userlock.html',
                resolve    : {
                    user: function () {
                        return $scope.publication.owner;
                    }
                }
            });
            $instance.result.then(function () {
                $scope.loadPublications(true);
            });
        };

        $scope.createComment = function ($event) {
            if ($event.keyCode === 8 || $event.keyCode === 46) {
                $scope.publication.comments.adding.rows = ($scope.publication.comments.adding.content.split(/\r\n|\n|\r/) || []).length;
            } else if ($event.keyCode === 13 && $scope.lastKey === 16) {
                $scope.publication.comments.adding.rows += 1;
            } else if ($event.keyCode === 13) {
                $event.preventDefault();

                if ($scope.publication.comments.adding.content.length > 0 && /\S/.test($scope.publication.comments.adding.content)) {

                    $scope.publication.comments.adding.loading = true;

                    $scope.publication.comments.adding.hasError = false;
                    $scope.publication.comments.adding.error = "";

                    $scope.publication.comments.adding.sending = $scope.publication.comments.adding.content.replace(
                        /(((http|https)\:\/\/)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
                        '<a href="$1" target="_blank">$1</a>'
                    );
                    $scope.publication.comments.adding.sending = $scope.publication.comments.adding.sending.replace(
                        /((www)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
                        '<a href="http://$1" target="_blank">$1</a>'
                    );

                    $factoryPublicationComment.persist($scope.publication.id, $scope.publication.comments.adding.sending)
                        .success(function (data) {
                            data.comment.created = moment().format();

                            $scope.publication.comments.data.push(data.comment);
                            $scope.publication.comments.adding.loading = false;
                            $scope.publication.comments.adding.content = '';
                            $scope.publication.comments.adding.rows = 1;
                        })
                        .error(function (data) {
                            $scope.publication.comments.adding.loading = false;
                            $scope.publication.comments.adding.hasError = true;
                            $scope.publication.comments.adding.error = data[0].message;
                        });
                } else {
                    $scope.publication.comments.adding.hasError = true;
                }
            }

            $scope.lastKey = $event.keyCode;
        };

        $scope.deleteComment = function (index) {
            var comment = $scope.publication.comments.data[index];
            comment.loading = true;

            $factoryPublicationComment.remove(comment.id)
                .success(function () {
                    comment.loading = false;

                    $scope.publication.comments.data.splice(index, 1);
                })
                .error(function () {
                    comment.loading = false;
                });
        };

        $scope.editComment = function (comment) {
            comment.editing = true;
            comment.oldContent = comment.content;

            comment.rows = (comment.content.split(/\r\n|\n|\r/) || []).length;

            comment.content = comment.content.replace(
                /<a href="(((http|https)\:\/\/)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)" target="_blank">(((http|https)\:\/\/)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)<\/a>/g,
                '$1'
            );
            comment.content = comment.content.replace(
                /<a href="((http\:\/\/www|www)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)" target="_blank">((www)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)<\/a>/g,
                '$4'
            );
        };

        $scope.updateComment = function ($event, comment) {
            if ($event.keyCode === 8 || $event.keyCode === 46) {
                comment.rows = (comment.content.split(/\r\n|\n|\r/) || []).length;
            } else if ($event.keyCode === 13 && $scope.lastKey === 16) {
                comment.rows += 1;
            } else if ($event.keyCode === 13) {
                $event.preventDefault();

                if (comment.content.length > 0 && /\S/.test(comment.content)) {

                    comment.sending = comment.content.replace(
                        /(((http|https)\:\/\/)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
                        '<a href="$1" target="_blank">$1</a>'
                    );
                    comment.sending = comment.sending.replace(
                        /((www)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/g,
                        '<a href="http://$1" target="_blank">$1</a>'
                    );

                    comment.loading = true;

                    $factoryPublicationComment.update(comment.id, comment.sending)
                        .success(function (data) {
                            comment.editing = false;
                            comment.loading = false;
                            comment.content = comment.sending;
                        })
                        .error(function (data) {
                            comment.loading = false;
                        });
                }
            } else if ($event.keyCode === 27) {
                comment.content = comment.oldContent;
                comment.editing = false;
            }

            $scope.lastKey = $event.keyCode;
        };

        $scope.actionOnLike = function () {
            if ($scope.publication.isUserLike) {
                $factoryPublication.removeLike($scope.publication.id)
                    .success(function (data) {
                        $scope.publication.likes = data.likes;
                        $scope.publication.isUserLike = false;
                    });
            } else {
                $factoryPublication.persistLike($scope.publication.id)
                    .success(function (data) {
                        $scope.publication.likes = data.likes;
                        $scope.publication.isUserLike = true;
                    });
            }
        };

        $scope.actionOnCommentLike = function (comment) {
            if (comment.isUserLike) {
                $factoryPublicationComment.removeLike(comment.id)
                    .success(function (data) {
                        comment.likes = data.likes;
                        comment.isUserLike = false;
                    });
            } else {
                $factoryPublicationComment.persistLike(comment.id)
                    .success(function (data) {
                        comment.likes = data.likes;
                        comment.isUserLike = true;
                    });
            }
        }
    }])

    .controller('ModalPublicationController', ['$scope', '$modalInstance', 'publication', 'connectedUser', function ($scope, $instance, publication, connectedUser) {
        $scope.publication = publication;
        $scope.connectedUser = connectedUser;

        $scope.isModal = true;

        $scope.$on('npevent-publication/remove', function ($event, id) {
            $instance.close({
                'remove': id
            });
        });

        $scope.ok = function () {
            $instance.close();
        }
    }])
    .controller('ModalLockUserController', ['$scope', '$modalInstance', 'user', 'factory:User', function ($scope, $instance, user, $factoryUser) {
        $scope.user = user;

        $scope.isModal = true;

        $scope.validLock = function () {

            $factoryUser.lock($scope.user.id)
                .success(function () {
                    $instance.close();

                })
                .error(function () {
                    $instance.dismiss('cancel');
                })
        }

        $scope.ok = function () {
            $instance.dismiss('cancel');
        }
    }])

    .controller('ModalActionController', ['$scope', '$http', '$filter', '$modalInstance', 'factory:Publication', 'publication', function ($scope, $http, $filter, $instance, $factory, publication) {
        $scope.data = {
            publication: publication,
            users      : {},
            loading    : false
        };

        $instance.opened.then(function () {
            $scope.data.loading = true;

            $factory.getLikes($scope.data.publication.id)
                .success(function (response) {
                    $scope.data.likes = response.likes;
                    $scope.data.loading = false;
                });
        });

        $scope.isModal = true;

        $scope.ok = function () {
            $instance.close();
        }
    }])

    .controller('ModalReportPublicationController', ['$scope', '$http', '$filter', '$modalInstance', 'publication', function ($scope, $http, $filter, $instance, publication) {
        $scope.vars = {
            loading    : false,
            explanation: '',
            publication: publication,
            error      : false
        }

        $scope.ok = function () {
            $scope.vars.loading = true;

            $http.post($filter('route')('api_v1_post_publication_signal', {publication: $scope.vars.publication.id}), {
                    publication: {
                        signal     : 1,
                        explanation: $scope.vars.explanation
                    }
                })
                .success(function () {
                    $instance.close();
                })
                .error(function (data) {
                    $scope.vars.error = data[0].message;
                    $scope.vars.loading = false;
                });

        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };
    }])

    .controller('ModalMapController', ['$scope', '$controller', '$timeout', '$q', '$maputils', '$modalInstance', 'position', 'icon', function ($scope, $controller, $timeout, $q, $maputils, $instance, position, icon) {

        $controller('BaseMapController', {$scope: $scope, $q: $q, $maputils: $maputils});

        $scope.map.center = {
            latitude : position.lat(),
            longitude: position.lng()
        };

        $scope.onMapReady = function () {
            $timeout(function () {
                $scope.marker = new google.maps.Marker({
                    map     : $scope.getMapObject(),
                    position: position,
                    icon    : (icon != null) ? icon : (window.location.protocol + '//' + window.location.hostname + '/img/map/map_icon_loc.png')
                });

                $scope.map.control.refresh({
                    latitude : $scope.map.center.latitude,
                    longitude: $scope.map.center.longitude
                });

                $scope.$apply(function () {
                    $scope.map.loading = false;
                });
            });
        }

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
    }])
    .controller('ModalShowObservationController', ['$scope', '$controller', '$timeout', '$q', '$maputils', '$modalInstance', 'publication', 'currentSharing', function ($scope, $controller, $timeout, $q, $maputils, $instance, publication, currentSharing) {
        $scope.publication = publication;
        $scope.currentSharing = currentSharing;

        $scope.ok = function () {
            $instance.close();
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };
    }])
;

;