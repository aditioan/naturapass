angular.module('app')
        /**
         * Controller d'invitation aux salons/groupes
         *
         * A considérer comme un controlleur abstrait
         * Reçoit les différentes informations comme le routing par $scope.params
         */
        .controller('LoungeGroupInvitationController', ['$scope', '$http', '$modal', '$filter', function ($scope, $http, $modal, $filter) {

                $scope.loaded = false;

                /**
                 * Entité visé par les invitations (salon|groupe)
                 * @type {{}}
                 */
                $scope.entity = {};

                $scope.$on('npevent-user/connected', function (event, user) {
                    $scope.connectedUser = user;
                });

                /**
                 * Initialise les invitations aux salons et aux groupes
                 */
                $scope.initInvitation = function () {
                    var params = {};
                    params[$scope.params.entity.singular] = $scope.id;

                    $http.get($filter('route')($scope.params.routing.get, params))
                            .success(function (response) {
                                $scope.entity = response[$scope.params.entity.singular];
                                $scope.entity.invited = 0;

                                $scope.loaded = true;
                            })
                            .error(function () {
                                $scope.loaded = false;
                            });
                };

                $scope.openFriendsModal = function () {
                    $modal.open({
                        controller: 'ModalLoungeGroupFriendsController',
                        templateUrl: 'modal.invite-friends.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return $scope.entity;
                            },
                            connected: function () {
                                return $scope.connectedUser;
                            }
                        }
                    });
                }

                $scope.openGroupsModal = function () {
                    $modal.open({
                        controller: 'ModalLoungeGroupInviteGroupController',
                        templateUrl: 'modal.invite-groups.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return $scope.entity;
                            }
                        }
                    });
                }

                $scope.openMembersModal = function () {
                    $modal.open({
                        controller: 'ModalLoungeGroupInviteMembersController',
                        templateUrl: 'modal.invite-members.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return $scope.entity;
                            }
                        }
                    });
                }

                $scope.openEmailModal = function () {
                    $modal.open({
                        controller: 'ModalLoungeGroupInviteEmailController',
                        templateUrl: 'modal.invite-email.html',
                        size: 'lg',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            connected: function () {
                                return $scope.connectedUser;
                            },
                            entity: function () {
                                return $scope.entity;
                            }
                        }
                    });
                }
            }])

        /**
         * Controller d'invitation par email
         */
        .controller('ModalLoungeGroupInviteEmailController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'connected', 'entity', function ($scope, $http, $filter, $instance, params, connected, entity) {
                $scope.params = params;

                $scope.email = {
                    to: '',
                    subject: '',
                    body: ''
                }

                $scope.data = {
                    connected: connected,
                    entity: entity,
                    loading: false
                }

                $instance.opened.then(function () {
                    $scope.email.subject = $filter('trans')('invite.placeholder.subject', {fullname: $scope.data.connected.fullname, loungeOrGroup: $scope.data.entity.name}, 'main');
                })

                $scope.ok = function () {
                    $scope.data.loading = true;

                    var params = {};
                    params[$scope.params.entity.singular] = $scope.data.entity.id;

                    $http.post($filter('route')($scope.params.routing.invitation.mail, params), $scope.email)
                            .success(function () {
                                $scope.data.loading = false;
                                $instance.close();
                            })
                            .error(function () {
                                $scope.data.loading = false;
                            })
                }

                $scope.cancel = function () {
                    if (!$scope.data.loading) {
                        $instance.dismiss('cancel');
                    }
                }
            }])

        /**
         * Controller d'invitation d'un groupe pour rejoindre un autre groupe ou un salon
         */
        .controller('ModalLoungeGroupInviteGroupController', ['$scope', '$http', '$filter', '$modal', '$modalInstance', 'params', 'entity', function ($scope, $http, $filter, $modal, $instance, params, entity, connected) {
                $instance.opened.then(function () {
                    $scope.loadGroups();
                });
                $scope.loadingSendInvitation = false;

                $scope.params = params;

                $scope.data = {
                    groups: [],
                    entity: entity,
                    noMoreGroups: false,
                    loaded: false,
                    loading: true,
                    limit: 3,
                    offset: -3,
                    request: false
                }

                /**
                 * Invitation de l'ensemble des membres d'un groupe
                 * @param group
                 */
                $scope.inviteGroup = function (group) {
                    group.inviting = true;
                    $scope.loadingSendInvitation = true;

                    var params = {
                        groupFriends: group.id
                    };
                    params[$scope.params.entity.singular] = $scope.data.entity.id;

                    $http.post($filter('route')($scope.params.routing.invitation.group, params))
                            .success(function () {
                                group.inviting = false;
                                group.invited = true;
                                $scope.loadingSendInvitation = false;
                            })
                            .error(function () {
                                group.inviting = false;
                                $scope.loadingSendInvitation = false;
                            })
                }

                /**
                 * Ouverture du modal affichant les membres d'un groupe
                 * @param group
                 */
                $scope.openSubscribersModal = function (group) {
                    $modal.open({
                        controller: 'ModalLoungeGroupSubscribersController',
                        templateUrl: 'modal.subscribers.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return group;
                            },
                            method: function () {
                                return $scope.data.entity;
                            }
                        }
                    });
                }

                /**
                 * Récupération des groupes ou l'utilisateur est un membre
                 */
                $scope.loadGroups = function () {
                    if ($scope.data.loading && $scope.data.loaded) {
                        return;
                    }

                    $scope.data.offset += $scope.data.limit;
                    $scope.data.loading = true;

                    $http.get($filter('route')('api_v1_get_groups_owning', {limit: $scope.data.limit, offset: $scope.data.offset}))
                            .success(function (response) {
                                $scope.data.loading = false;
                                $scope.data.loaded = true;

                                angular.forEach(response.groups, function (entity) {
                                    if (entity.id != $scope.data.entity.id) {
                                        entity.invited = 0;

                                        $scope.data.groups.push(entity);
                                    }
                                });

                                if (!response.groups.length) {
                                    $scope.data.noMoreGroups = true;
                                }

                            })
                            .error(function () {
                                $scope.data.offset -= $scope.data.limit;
                                $scope.data.loading = false;
                            });
                }

                $scope.ok = function () {
                    if (!$scope.data.loading) {
                        $instance.close();
                    }
                }
            }])

        /**
         * Controller d'invitation des amis
         */
        .controller('ModalLoungeGroupFriendsController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'entity', 'connected', function ($scope, $http, $filter, $instance, params, entity, connected) {
                $scope.params = params;
                $scope.loadingSendInvitation = false;

                $scope.data = {
                    friends: [],
                    entity: entity,
                    connected: connected,
                    loading: true
                }

                /**
                 * Invitation d'un ami
                 * @param friend
                 */
                $scope.inviteFriend = function (friend) {
                    friend.inviting = true;
                    $scope.loadingSendInvitation = true;

                    var params = {};
                    params[$scope.params.entity.singular] = $scope.data.entity.id;
                    params['user'] = friend.id

                    $http.post($filter('route')($scope.params.routing.subscribers.post, params))
                            .success(function () {
                                friend.inviting = false;
                                friend.invited = true;
                                var user = {user: friend, access: 0, isAdmin: false};
                                $scope.data.entity.subscribers.push(user);
                                $scope.loadingSendInvitation = false;

                            })
                            .error(function () {
                                friend.inviting = false;
                                $scope.loadingSendInvitation = false;
                            })
                }


                $instance.opened.then(function () {
                    /**
                     * Si les membres du groupe/salon n'ont pas déjà été récupéré
                     */
                    if (typeof $scope.data.entity.subscribers != 'object') {
                        var params = [];
                        params[$scope.params.entity.singular] = $scope.data.entity.id;

                        $http.get($filter('route')($scope.params.routing.subscribers.get, params) + '?all=1')
                                .success(function (response) {
                                    $scope.data.entity.subscribers = [];

                                    angular.forEach(response.subscribers, function (subscriber) {
                                        if (subscriber.access == 3) {
                                            subscriber.isAdmin = true;
                                        } else {
                                            subscriber.isAdmin = false;
                                        }

                                        $scope.data.entity.subscribers.push(subscriber);
                                    });

                                    $scope.retrieveFriends();
                                })
                                .error(function () {
                                    $scope.data.loading = true;
                                });
                    } else {
                        $scope.retrieveFriends();
                    }
                    $scope.loadingSendInvitation = false;
                });

                /**
                 * Récupération de tous les amis
                 */
                $scope.retrieveFriends = function () {
                    $http.get($filter('route')('api_v1_get_user_friends', {user: $scope.data.connected.id}))
                            .success(function (response) {
                                $scope.data.loading = false;

                                angular.forEach(response.friends, function (friend) {
                                    friend.invited = false;
                                    friend.subscriber = false;

                                    angular.forEach($scope.data.entity.subscribers, function (subscriber) {
                                        if (subscriber.user.id == friend.id) {
                                            if (subscriber.access >= 2) {
                                                friend.invited = true;
                                                friend.subscriber = true;
                                            } else if (subscriber.access == 0) {
                                                friend.invited = true;
                                                friend.subscriber = false;
                                            }

                                            return;
                                        }
                                    });

                                    $scope.data.friends.push(friend);
                                });
                            })
                            .error(function (response) {
                                $scope.data.loading = false;
                            });
                }

                $scope.ok = function () {
                    if (!$scope.data.loading) {
                        $instance.close();
                    }
                }
            }])

        /**
         * Controller d'invitation des membres
         */
        .controller('ModalLoungeGroupInviteMembersController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'entity', function ($scope, $http, $filter, $instance, params, entity) {
                $scope.params = params;
                $scope.input = {};

                $scope.loadingSendInvitation = false;

                $scope.data = {
                    members: [],
                    entity: entity,
                    loading: false
                };

                $scope.showAlert = true;

                /**
                 * Invitation d'un member
                 * @param member
                 */
                $scope.inviteMember = function (member) {
                    member.inviting = true;
                    $scope.loadingSendInvitation = true;

                    var params = {};
                    params[$scope.params.entity.singular] = $scope.data.entity.id;
                    params['user'] = member.id

                    $http.post($filter('route')($scope.params.routing.subscribers.post, params))
                            .success(function () {
                                member.inviting = false;
                                member.invited = true;
                                var user = {user: member, access: 0, isAdmin: false};
                                $scope.data.entity.subscribers.push(user);
                                $scope.loadingSendInvitation = false;
                            })
                            .error(function () {
                                member.inviting = false;
                                $scope.loadingSendInvitation = false;
                            });
                };

                $instance.opened.then(function () {
                    /**
                     * Si les membres du groupe/salon n'ont pas déjà été récupéré
                     */
                    if (typeof $scope.data.entity.subscribers != 'object') {
                        var params = [];
                        params[$scope.params.entity.singular] = $scope.data.entity.id;

                        $http.get($filter('route')($scope.params.routing.subscribers.get, params) + '?all=1')
                                .success(function (response) {
                                    $scope.data.entity.subscribers = [];

                                    angular.forEach(response.subscribers, function (subscriber) {
                                        if (subscriber.access == 3) {
                                            subscriber.isAdmin = true;
                                        } else {
                                            subscriber.isAdmin = false;
                                        }

                                        $scope.data.entity.subscribers.push(subscriber);
                                    });

                                })
                                .error(function () {
                                    $scope.data.loading = true;
                                });
                    }
                    $scope.loadingSendInvitation = false;
                });

                /**
                 * Gère la recherche d'un membre
                 *
                 * @param $event
                 */
                $scope.persistSearchMember = function ($event) {
                    if ($event.keyCode === 13 && $scope.input.filter.length >= 3) {
                        $scope.loadingSendInvitation = true;
                        $scope.showAlert = false;
                        var params = {};
                        params['q'] = $scope.input.filter;
                        params[$scope.params.entity.singular] = $scope.data.entity.id;
                        $scope.data.members = [];
                        $http.get(($filter('route')('api_v1_get_users_search', params)))
                                .success(function (response) {
                                    $scope.data.loading = false;

                                    angular.forEach(response.users, function (user) {
                                        user.invited = false;
                                        user.subscriber = false;

                                        angular.forEach($scope.data.entity.subscribers, function (subscriber) {
                                            if (subscriber.user.id == user.id) {
                                                if (subscriber.access >= 2) {
                                                    user.invited = true;
                                                    user.subscriber = true;
                                                } else if (subscriber.access == 0) {
                                                    user.invited = true;
                                                    user.subscriber = false;
                                                }
                                                return;
                                            }
                                        });

                                        $scope.data.members.push(user);
                                    });
                                    $scope.loadingSendInvitation = false;
                                })
                                .error(function (response) {
                                    $scope.data.loading = false;
                                    $scope.loadingSendInvitation = false;
                                });
                    } else if ($event.keyCode === 13 && $scope.input.filter.length < 3) {
                        $scope.data.members = [];
                        $scope.showAlert = true;
                    }
                };

                $scope.ok = function () {
                    if (!$scope.data.loading) {
                        $instance.close();
                    }
                };
            }]);
