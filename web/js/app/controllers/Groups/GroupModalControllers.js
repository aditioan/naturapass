angular.module('app')
    .controller('ModalGroupSubscribersController', ['$scope', '$http', '$filter', '$q', '$modalInstance', 'group', 'method', 'connected', function ($scope, $http, $filter, $q, $instance, group, method, connected) {
        $scope.data = {
            group: group,
            validation: method == 'validation',
            banish: method == 'banish',
            admin: method == 'admin',
            invitation: typeof method == 'object',
            groupAsk: method,
            loading: false
        };

        $instance.opened.then(function() {
            if (typeof $scope.data.group.subscribers != 'object') {
                $scope.data.loading = true;

                if ($scope.data.invitation) {
                    if (typeof $scope.data.groupAsk.subscribers != 'object') {
                        // Récupération des membres du groupe sur lequel on est
                        $scope.retrieveSubscribers($scope.data.groupAsk, true).then(function() {
                            // Récupération des membres du groupe que l'on vise
                            $scope.retrieveSubscribers($scope.data.group).then(function() {
                                // Traitement des informations
                                $scope.sortSubscriber();
                            });
                        });
                    } else {
                        $scope.retrieveSubscribers($scope.data.group).then(function() {
                            $scope.sortSubscriber();
                        });
                    }
                } else {
                    $scope.retrieveSubscribers($scope.data.group, true);
                }
            }
        });

        $scope.sortSubscriber = function() {
            $scope.data.group.invited = 0;

            angular.forEach($scope.data.group.subscribers, function(subscriber) {
                subscriber.invited = false;
                subscriber.subscriber = false;

                angular.forEach($scope.data.groupAsk.subscribers, function(element) {
                    if (subscriber.user.id == element.user.id) {
                        if (element.access >= 2) {
                            subscriber.invited = true;
                            subscriber.subscriber = true;
                            $scope.data.group.invited++;
                        } else if (element.access < 2) {
                            subscriber.invited = true;
                            subscriber.subscriber = false;
                            $scope.data.group.invited++;
                        }

                        return;
                    }
                });
            });
        }

        $scope.retrieveSubscribers = function(group, all) {
            return $http.get($filter('route')('api_v1_get_group_subscribers', {group: group.id}) + (all != undefined && all ? '?all=1' : ''))
                .success(function(response) {
                    $scope.data.loading = false;
                    group.subscribers = [];

                    angular.forEach(response.subscribers, function(subscriber) {
                        if (subscriber.access == 3) {
                            subscriber.isAdmin = true;
                        } else {
                            subscriber.isAdmin = false;
                        }

                        group.subscribers.push(subscriber);
                    });
                })
                .error(function() {
                    $scope.data.loading = true;
                });
        }

        $scope.filterSubscribers = function (subscriber) {
            if ($scope.data.validation) {
                return subscriber.access == 1;
            } else if ($scope.data.banish) {
                return subscriber.access == 2;
            } else if ($scope.data.admin) {
                return (subscriber.access == 2 || subscriber.access == 3);
            } else if ($scope.data.invitation) {
                return true;
            }

            return subscriber.access == 2 || subscriber.access == 3;
        };

        $scope.promoteSubscriber = function (subscriber) {
            subscriber.promoting = true;

            $http.put($filter('route')('api_v1_put_group_suscriber_admin', {group: $scope.data.group.id, subscriber: subscriber.user.id}))
                .success(function (response) {
                    subscriber.isAdmin = response.isAdmin;
                    subscriber.access = response.isAdmin ? 3 : 2;
                    subscriber.promoting = false;

                    $scope.data.group.nbAdmins++;
                })
                .error(function (response) {
                    subscriber.isAdmin = !subscriber.isAdmin;
                    subscriber.promoting = true;
                });
        }

        $scope.acceptAsk = function (subscriber) {
            subscriber.accepting = true;

            $http.put($filter('route')('api_v1_put_group_user_join', {group: $scope.data.group.id, user: subscriber.user.id}))
                .success(function () {
                    subscriber.access = 2;
                    subscriber.disabled = true;
                    subscriber.accepting = false;

                    $scope.data.group.nbPending--;
                    $scope.data.group.nbSubscribers++;
                })
                .error(function () {
                    subscriber.accepting = false;
                });
        }

        $scope.inviteSubscriber = function(subscriber) {
            subscriber.inviting = true;

            $http.post($filter('route')('api_v1_post_group_join', {group: $scope.data.groupAsk.id, user: subscriber.user.id}))
                .success(function() {
                    subscriber.inviting = false;
                    subscriber.invited = true;

                    if ($scope.data.groupAsk.invited != undefined) {
                        $scope.data.groupAsk.invited++;
                    } else {
                        $scope.data.groupAsk.invited = 1;
                    }

                })
                .error(function() {
                    subscriber.inviting = false;
                })
        }

        $scope.deleteSubscriber = function ($index, subscriber) {
            subscriber.refusing = true;

            $http._delete($filter('route')('api_v1_delete_group_join', {group: group.id, user: subscriber.user.id}))
                .success(function () {
                    subscriber.access = false;
                    subscriber.disabled = true;
                    subscriber.refusing = false;

                    if ($scope.data.validation) {
                        $scope.data.group.nbPending--;
                    } else {
                        $scope.data.group.nbSubscribers--;
                    }

                    $scope.data.group.subscribers.splice($index, 1);
                })
                .error(function () {
                    subscriber.refusing = false;
                });
        }

        $scope.ok = function () {
            $instance.close()
        };
    }])