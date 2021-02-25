angular.module('app')

    .controller('ModalLoungeGroupRemoveController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'entity', function ($scope, $http, $filter, $instance, params, entity) {
        $scope.params = params;

        $scope.data = {
            entity: entity,
            loading: false
        };

        $scope.ok = function () {
            $scope.data.loading = true;

            var params = [];
            params[$scope.params.entity.singular] = $scope.data.entity.id;

            $http._delete($filter('route')($scope.params.routing.remove, params))
                .success(function () {
                    $scope.data.loading = false;
                    $instance.close();
                })
                .error(function () {
                    $scope.data.loading = false;
                });
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        }
    }])

    .controller('ModalLoungeGroupSubscribersController', ['$scope', '$http', '$filter', '$q', '$modalInstance', 'params', 'entity', 'method', function ($scope, $http, $filter, $q, $instance, params, entity, method) {
        $scope.params = params;

        /**
         *
         * @type {{entity: *, validation: boolean, banish: boolean, admin: boolean, invitation: boolean, entityAsk: *, loading: boolean}}
         */
        $scope.data = {
            entity: entity,
            validation: method == 'validation',
            banish: method == 'banish',
            admin: method == 'admin',
            invitation: typeof method == 'object',
            entityAsk: method,
            loading: false
        };

        $instance.opened.then(function() {
            if (typeof $scope.data.entity.subscribers != 'object') {
                $scope.data.loading = true;

                if ($scope.data.invitation) {
                    if (typeof $scope.data.entityAsk.subscribers != 'object') {
                        // Récupération des membres de l'entité source
                        $scope.retrieveSubscribers($scope.data.entityAsk, true).then(function() {
                            // Récupération des membres de l'entité visé
                            $scope.retrieveSubscribers($scope.data.entity).then(function() {
                                // Traitement des informations
                                $scope.sortSubscriber();
                            });
                        });
                    } else {
                        $scope.retrieveSubscribers($scope.data.entity).then(function() {
                            $scope.sortSubscriber();
                        });
                    }
                } else {
                    $scope.retrieveSubscribers($scope.data.entity, true);
                }
            }
        });

        /**
         * Effectue un tri sur tous les membres, pour checker si ils n'ont pas déjà été invités
         */
        $scope.sortSubscriber = function() {
            $scope.data.entity.invited = 0;

            angular.forEach($scope.data.entity.subscribers, function(subscriber) {
                subscriber.invited = false;
                subscriber.subscriber = false;

                angular.forEach($scope.data.entityAsk.subscribers, function(element) {
                    if (subscriber.user.id == element.user.id) {
                        if (element.access >= 2) {
                            subscriber.invited = true;
                            subscriber.subscriber = true;
                            $scope.data.entity.invited++;
                        } else if (element.access < 2) {
                            subscriber.invited = true;
                            subscriber.subscriber = false;
                            $scope.data.entity.invited++;
                        }

                        return;
                    }
                });
            });
        }

        $scope.retrieveSubscribers = function(entity, all) {
            var params = {};

            if (entity.grouptag) {
                params.group = entity.id;
            } else if (entity.loungetag) {
                params.lounge = entity.id;
            }

            return $http.get($filter('route')(entity.grouptag ? 'api_v1_get_group_subscribers' : $scope.params.routing.subscribers.get, params) + (all != undefined && all ? '?all=1' : ''))
                .success(function(response) {
                    entity.subscribers = [];

                    angular.forEach(response.subscribers, function(subscriber) {
                        if (subscriber.access == 3) {
                            subscriber.isAdmin = true;
                        } else {
                            subscriber.isAdmin = false;
                        }

                        entity.subscribers.push(subscriber);
                    });

                    $scope.data.loading = false;
                })
                .error(function() {
                    $scope.data.loading = true;
                });
        }

        /**
         * Filtre les membres d'un entité selon leur niveau d'accès
         * @param subscriber
         * @returns {boolean}
         */
        $scope.filterSubscribers = function (subscriber) {
            if ($scope.data.validation) {
                return subscriber.access == 1;
            } else if ($scope.data.banish) {
                return subscriber.access == 2 || (subscriber.access == 3 && subscriber.user.id != $scope.data.entity.owner.id);
            } else if ($scope.data.admin) {
                return (subscriber.access == 2 || subscriber.access == 3);
            } else if ($scope.data.invitation) {
                return true;
            }

            return subscriber.access == 2 || subscriber.access == 3;
        };

        $scope.promoteSubscriber = function (subscriber) {
            subscriber.promoting = true;

            var params = [];
            params[$scope.params.entity.singular] = $scope.data.entity.id;
            params['subscriber'] = subscriber.user.id;

            $http.put($filter('route')($scope.params.routing.subscribers.admin, params))
                .success(function (response) {
                    subscriber.isAdmin = response.isAdmin;
                    subscriber.access = response.isAdmin ? 3 : 2;
                    subscriber.promoting = false;

                    $scope.data.entity.nbAdmins += subscriber.isAdmin ? 1 : -1;
                })
                .error(function () {
                    subscriber.isAdmin = !subscriber.isAdmin;
                    subscriber.promoting = true;
                });
        }

        $scope.acceptAsk = function (subscriber) {
            subscriber.accepting = true;

            var params = [];
            params[$scope.params.entity.singular] = $scope.data.entity.id;
            params['user'] = subscriber.user.id;

            $http.put($filter('route')($scope.params.routing.subscribers.put, params))
                .success(function () {
                    subscriber.access = 2;
                    subscriber.disabled = true;
                    subscriber.accepting = false;

                    $scope.data.entity.nbPending--;
                    $scope.data.entity.nbSubscribers++;
                })
                .error(function () {
                    subscriber.accepting = false;
                });
        }

        $scope.inviteSubscriber = function(subscriber) {
            subscriber.inviting = true;

            var params = [];
            params[$scope.params.entity.singular] = $scope.data.entityAsk.id;
            params['user'] = subscriber.user.id;

            $http.post($filter('route')($scope.params.routing.subscribers.post, params))
                .success(function() {
                    subscriber.inviting = false;
                    subscriber.invited = true;

                    if ($scope.data.entityAsk.invited != undefined) {
                        $scope.data.entityAsk.invited++;
                    } else {
                        $scope.data.entityAsk.invited = 1;
                    }

                })
                .error(function() {
                    subscriber.inviting = false;
                })
        }

        $scope.deleteSubscriber = function ($index, subscriber) {
            subscriber.refusing = true;

            var params = [];
            params[$scope.params.entity.singular] = $scope.data.entity.id;
            params['user'] = subscriber.user.id;

            $http._delete($filter('route')($scope.params.routing.subscribers.remove, params))
                .success(function () {
                    subscriber.access = false;
                    subscriber.disabled = true;
                    subscriber.refusing = false;

                    if ($scope.data.validation) {
                        $scope.data.entity.nbPending--;
                    } else {
                        $scope.data.entity.nbSubscribers--;
                    }

                    $scope.data.entity.subscribers.splice($index, 1);
                })
                .error(function () {
                    subscriber.refusing = false;
                });
        }

        $scope.ok = function () {
            $instance.close()
        };
    }])