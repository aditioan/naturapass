/**
 * Created by vincentvalot on 26/05/14.
 */

angular.module('app')

    .controller('PublicationListController', ['$scope', 'factory:Publication', 'socket', function ($scope, $factory, socket) {
        $scope.publications = [];
        $scope.sharing = 3;
        $scope.offset = 0;
        $scope.reset = true;

        $scope.busy = false;
        $scope.atEnd = false;

        $scope.group = [];
        $scope.hunt = false;
        $scope.profile = false;

        $scope.editingCommentRows = 1;

        $scope.addingCommentRows = 1;
        $scope.addingCommentLoading = false;

        $scope.lastKey = false;

        $scope.loading = false;

        $scope.loadMenu = false;

        /**
         * Evenenement: Changement du type de partage voulu
         */
//                $scope.$on('npevent-menu/sharing-changed', function (event, sharing) {
//                    $scope.publications = [];
//                    $scope.offset = 0;
//                    $scope.sharing = sharing;
////                    $scope.group = null;
//                    $scope.atEnd = false;
//                    $scope.loadMenu = true;
//
//                    $scope.loadPublications();
//                });
//
//
//                /**
//                 * Evenenement: Changement du groupe affiché voulu
//                 */
//                $scope.$on('npevent-menu/group-changed', function (event, group) {
//                    $scope.publications = [];
//                    $scope.offset = 0;
//                    $scope.group = group;
//                    $scope.atEnd = false;
//                    $scope.loadMenu = true;
//
//                    $scope.loadPublications();
//                });

        /**
         * Getting a socket event when a new heavy publication has been processed
         */
        if (socket) {
            socket.on('npevent-publication:processed', function (data) {
                if ($scope.group.length) {
                    if (data.groups && data.groups.indexOf($scope.group) !== -1) {
                        $scope.publications.unshift(data);
                    }
                } else {
                    $scope.publications.unshift(data);
                }
            });
        }

        /**
         * Evenenement: Changement du filtre affiché voulu
         */
        $scope.$on('npevent-menu/filter-changed', function (event, filter) {
            $scope.publications = [];
            $scope.offset = 0;
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
            $scope.atEnd = false;
            $scope.loadMenu = true;

            $scope.loadPublications();
        });

        /**
         * Evenement: A l'ajout d'une publication via le formulaire
         */
        $scope.$on('npevent-publication/added', function (event, data) {
            $scope.publications.unshift(data);
            $scope.$emit('npevent-publication/update', data);
        });

        /**
         * Evenement: A l'ajout d'une publication via le formulaire
         */
        $scope.$on('npevent-publication/update', function (event, data) {

            $.each($scope.publications, function (index, publication) {
                if (publication.id == data.id) {
                    data.show = true;
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
        });

        /**
         * Evenement: Connexion de l'utilisateur
         */
        $scope.$on('npevent-user/connected', function (event, user) {
            $scope.connectedUser = user;
        });

        /**
         * Evenement: Suppression d'une publication
         */
        $scope.$on('npevent-publication/remove', function ($event, id) {
            angular.forEach($scope.publications, function (element, index) {
                if (element.id == id) {
                    $scope.publications.splice(index, 1);
                    return;
                }
            });
        });
        $scope.$on('editPublication', function ($event, data) {
            angular.forEach($scope.publications, function (element, index) {
                $scope.publications[index].show = true;
                $scope.publications[index].editing = false;
            });
            var editdate = "";
            if (typeof data.date !== typeof undefined) {
                editdate = moment(data.date).format("DD/MM/YYYY HH:mm");
            }
            data.editdate = editdate;
            if (data.media == false) {
                $scope.$broadcast('editPublicationForm', data);
            }
            else {
                $scope.$broadcast('editMediaPublicationForm', data);
            }
        });
        $scope.$on('finishPublication', function ($event, data) {
            angular.forEach($scope.publications, function (element, index) {
                $scope.publications[index].editing = false;
            });
        });
        $scope.loadPublications = function (forceRestet) {
            forceRestet = forceRestet || false;
            if (forceRestet) {
                $scope.offset = 0;
            }
            if ($scope.loadMenu) {
                if ($scope.busy)
                    return;

                $scope.busy = true;

                var $container = angular.element('[ng-controller="PublicationListController"]');

                var onSuccess = function (data) {
                    if (forceRestet) {
                        $scope.publications = [];
                    }
                    if (data.publications.length) {
                        $.each(data.publications, function (index, publication) {
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

                            $scope.publications.push(publication);
                        });
                    } else {
                        $scope.atEnd = true;
                    }

                    $scope.busy = false;
                };

                var onError = function () {
                    $scope.offset -= 3;
                    $scope.busy = false;
                };

                if (!$scope.group) {
                    $scope.group = $container.data('group') != undefined ? $container.data('group') : false;
                }
                if (!$scope.hunt) {
                    $scope.hunt = $container.attr('data-hunt') != undefined ? $container.attr('data-hunt') : false;
                    if ($scope.hunt) {
                        $scope.sharing = -1;
                    }
                }
                $scope.reset = forceRestet ? true : false;

                $scope.profile = $container.data('profile') != undefined ? $container.data('profile') : false;
                if ($scope.profile) {
                    $factory.ofUser($scope.profile, {'limit': 3, 'offset': $scope.offset})
                        .success(onSuccess)
                        .error(onError);
                } else {
                    var _sharing = $scope.sharing;

                    if((!$scope.group || !$scope.group.length) && (!$scope.hunt || !$scope.hunt.length) && $scope.sharing == -1) {
                        _sharing = 3;
                    }
                    $factory.all({
                            'reset'   : ($scope.reset ? 1 : 0),
                            'sharing' : _sharing,
                            'limit'   : 3,
                            'groups[]': $scope.group,
                            'hunts[]' : $scope.hunt,
                            'offset'  : $scope.offset
                        })
                        .success(onSuccess)
                        .error(onError);
                }
                $scope.offset += 3;
            }
        }
    }])
;