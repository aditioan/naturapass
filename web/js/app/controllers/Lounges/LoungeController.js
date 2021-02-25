/**
 * Created by vincentvalot on 15/07/14.
 */

angular.module('app')
        .controller('ModalLoungeRemoveNotMemberController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'lounge', 'notmember', function ($scope, $http, $filter, $instance, params, lounge, notmember) {
                $scope.params = params;
                $scope.lounge = lounge;
                $scope.data = {
                    notmember: notmember,
                    loading: false
                };

                /**
                 * Désactive l'édition d'un commentaire privé
                 *
                 * @param subscriber
                 */
                $scope.removeNotMember = function (subscriber) {
                    $scope.data.loading = true;
                    $http._delete($filter('route')($scope.params.routing.addnomember.remove, {lounge: $scope.lounge.id, id: subscriber.id}))
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
                };
            }])
        .controller('ModalLoungeRemoveMemberController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'lounge', 'member', function ($scope, $http, $filter, $instance, params, lounge, member) {
                $scope.params = params;
                $scope.lounge = lounge;

                $scope.data = {
                    member: member,
                    loading: false
                };

                /**
                 * Désactive l'édition d'un commentaire privé
                 *
                 * @param subscriber
                 */
                $scope.removeMember = function (subscriber) {
                    $scope.data.loading = true;
                    $http._delete($filter('route')($scope.params.routing.subscribers.remove, {lounge: $scope.lounge.id, user: subscriber.id}))
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
                };
            }])
        .controller('ModalLoungeRemoveInvitedMemberController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'lounge', 'member', function ($scope, $http, $filter, $instance, params, lounge, member) {
                $scope.params = params;
                $scope.lounge = lounge;

                $scope.data = {
                    member: member,
                    loading: false
                };

                /**
                 * Désactive l'édition d'un commentaire privé
                 *
                 * @param subscriber
                 */
                $scope.removeInvitedMember = function (subscriber) {
                    $scope.data.loading = true;
                    $http._delete($filter('route')($scope.params.routing.subscribers.remove, {lounge: $scope.lounge.id, user: subscriber.id}))
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
                };
            }])
        .controller('ModalLoungeValidInvitedMemberController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'lounge', 'member', 'subscriberMember', function ($scope, $http, $filter, $instance, params, lounge, member, subscriberMember) {
                $scope.params = params;
                $scope.lounge = lounge;

                $scope.data = {
                    member: member,
                    subscriberMember: subscriberMember,
                    loading: false
                };

                /**
                 * Désactive l'édition d'un commentaire privé
                 *
                 * @param subscriber
                 */
                $scope.validInvitedMember = function (subscriber) {
                    $scope.data.loading = true;
                    $http.put($filter('route')($scope.params.routing.subscribers.put, {lounge: $scope.lounge.id, user: subscriber.id}))
                            .success(function (response) {
                                $scope.data.loading = false;
                                subscriberMember.access = response.subscriber.access;
                                $instance.close();
                            })
                            .error(function () {
                                $scope.data.loading = false;
                            });
                };

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                };
            }])
        .controller('ModalLoungeReviveInvitedMemberController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'lounge', 'member', function ($scope, $http, $filter, $instance, params, lounge, member) {
                $scope.params = params;
                $scope.lounge = lounge;

                $scope.email = {
                    to: member.email,
                    subject: '',
                    body: ''
                };

                $scope.data = {
                    member: member,
                    loading: false
                };


                $scope.ok = function () {
                    $scope.data.loading = true;

                    var params = {};
                    params['lounge'] = $scope.lounge.id;

                    $http.post($filter('route')($scope.params.routing.subscribers.mail, params), $scope.email)
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
        .controller('LoungeController', ['$scope', '$http', '$modal', '$filter', 'socket', function ($scope, $http, $modal, $filter, socket) {
                $scope.lounge = {};
                $scope.loaded = false;
                $scope.subscribersLoading = true;
                $scope.subscribersNotMemberLoading = true;
                $scope.showAllMember = false;
                $scope.showMembers = false;
                $scope.showNotMembers = false;
                $scope.showListInvited = false;
                $scope.geolocationActive = 0;

                $scope.params = {
                    entity: {
                        singular: 'lounge',
                        plural: 'lounges'
                    },
                    routing: {
                        subscribers: {
                            get: 'api_v1_get_lounge_subscribers',
                            put: 'api_v1_put_lounge_user_join',
                            post: 'api_v1_post_lounge_join',
                            remove: 'api_v1_delete_lounge_join',
                            participation: 'api_v1_put_lounge_subscriber_participation',
                            admin: 'api_v1_put_lounge_subscriber_admin',
                            mail: 'api_v1_post_lounge_revive_mail'
                        },
                        addnomember: {
                            post: 'api_v1_post_lounge_notmember',
                            remove: 'api_v1_delete_lounge_notmember',
                            participation: 'api_v1_put_lounge_notmember_participation'

                        }
                    }
                }

                $scope.openDeleteMemberModal = function (lounge, member) {
                    var $instance = $modal.open({
                        controller: 'ModalLoungeRemoveMemberController',
                        templateUrl: 'modal.remove-member.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            lounge: function () {
                                return lounge;
                            },
                            member: function () {
                                return member.user;
                            }
                        }
                    });
                };

                $scope.openDeleteNotMemberModal = function (lounge, notmember) {
                    var $instance = $modal.open({
                        controller: 'ModalLoungeRemoveNotMemberController',
                        templateUrl: 'modal.remove-notmember.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            lounge: function () {
                                return lounge;
                            },
                            notmember: function () {
                                return notmember;
                            }
                        }
                    });
                };

                $scope.openDeleteInvitedMemberModal = function (lounge, member) {
                    var $instance = $modal.open({
                        controller: 'ModalLoungeRemoveInvitedMemberController',
                        templateUrl: 'modal.remove-invitedmember.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            lounge: function () {
                                return lounge;
                            },
                            member: function () {
                                return member.user;
                            }
                        }
                    });
                };

                $scope.openValidInvitedMemberModal = function (lounge, member) {
                    var $instance = $modal.open({
                        controller: 'ModalLoungeValidInvitedMemberController',
                        templateUrl: 'modal.valid-invitedmember.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            lounge: function () {
                                return lounge;
                            },
                            member: function () {
                                return member.user;
                            },
                            subscriberMember: function () {
                                return member;
                            }
                        }
                    });
                };

                $scope.openReviveInvitedMemberModal = function (lounge, member) {
                    var $instance = $modal.open({
                        controller: 'ModalLoungeReviveInvitedMemberController',
                        templateUrl: 'modal.revive-invitedmember.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            lounge: function () {
                                return lounge;
                            },
                            member: function () {
                                return member.user;
                            }
                        }
                    });
                };

                $scope.$on('npevent-user/connected', function (event, user) {
                    $scope.connectedUser = user;
                });


                /**
                 * Evénement: Au chargement du salon
                 */
                $scope.$on('npevent-lounge/loaded', function () {
                    if (socket) {
                        socket.on('npevent-lounge:quiet', function (data) {
                            if ($scope.lounge.connected.user.id == data.user.id) {
                                $scope.lounge.connected.quiet = data.quiet;
                            }
                        });
                        socket.on('npevent-lounge:geolocation', function (data) {
                            $scope.lounge.geolocation = data.geolocation;
                            $scope.lounge.initGeolocation = data.geolocation;
                        });
                        socket.on('npevent-lounge:user-admin', function (data) {
                            if (data.user.usertag == $scope.connectedUser.usertag) {
                                $scope.lounge.connected.access = data.access;
                            }
                        });
                        socket.on('npevent-lounge:participation', function (data) {
//                            console.log(data);
                            if (data.user.usertag != $scope.connectedUser.usertag) {
                                angular.forEach($scope.lounge.subscribers, function (member) {
                                    if (member.user.id === data.user.id) {
                                        member.participation = data.participation;
                                    }
                                });
                            }
                        });
                        socket.on('npevent-lounge:addnotmember', function (data) {
                            $scope.lounge.subscribersNotMember.push(data);

                        });
                        socket.on('npevent-lounge:removenotmember', function (data) {
                            angular.forEach($scope.lounge.subscribersNotMember, function (notmember) {
                                if (parseInt(notmember.id) === parseInt(data.id)) {
                                    var index = $scope.lounge.subscribersNotMember.indexOf(notmember);
                                    $scope.lounge.subscribersNotMember.splice(index, 1);
                                }
                            });
                        });
                        socket.on('npevent-lounge:removemember', function (data) {
                            angular.forEach($scope.lounge.subscribers, function (member) {
                                if (parseInt(member.user.id) === parseInt(data.id)) {
                                    var index = $scope.lounge.subscribers.indexOf(member);
                                    $scope.lounge.subscribers.splice(index, 1);
                                }
                            });
                        });
                        socket.on('npevent-lounge:participationnotmember', function (data) {
                            angular.forEach($scope.lounge.subscribersNotMember, function (notmember) {
                                if (notmember.id === data.id) {
                                    notmember.participation = data.participation;
                                }
                            });
                        });
                    }
                });

                $scope.clickBtnListInvite = function () {
                    $scope.showListInvited = !$scope.showListInvited;
                }

                $scope.initLounge = function () {
                    var id = angular.element('[ng-controller="LoungeController"]').data('lounge');

                    $http.get($filter('route')('api_v1_get_lounge', {lounge: id}))
                            .success(function (response) {
                                $scope.lounge = response.lounge;

                                socket && socket.emit('npevent-lounge:join', $scope.lounge.loungetag);

                                socket && socket.on('reconnect', function () {
                                    socket.emit('npevent-lounge:join', $scope.lounge.loungetag);
                                });

                                $scope.$broadcast('npevent-lounge/loaded');

                                $http.get($filter('route')('api_v1_get_lounge_subscribers', {lounge: id, all: 1}))
                                        .success(function (response) {
                                            $scope.lounge.subscribers = response.subscribers;

                                            $scope.lounge.admins = $filter('filter')($scope.lounge.subscribers, {access: 3});
                                            $scope.subscribersLoading = false;
                                        });

                                $http.get($filter('route')('api_v1_get_lounge_subscribersnotmember', {lounge: id}))
                                        .success(function (response) {
                                            $scope.lounge.subscribersNotMember = response.subscribersNotMember;
                                            $scope.subscribersNotMemberLoading = false;
                                        });

                                $scope.loaded = true;
                            })
                            .error(function () {
                                $scope.loaded = true;
                            });
                };

                $scope.clickInvite = function () {
                    $scope.showListInvited = !$scope.showListInvited;
//                    console.log($scope.showListInvited);
                }

                /**
                 * Active l'édition d'un commentaire public
                 *
                 * @param subscriber
                 */
                $scope.editPublicComment = function (subscriber) {
                    if ($scope.lounge.connected.access == 3) {
                        subscriber.savedPublicComment = subscriber.publicComment;
                        subscriber.editingPublicComment = true;
                    }
                }

                /**
                 * Active l'édition d'un commentaire privé
                 *
                 * @param subscriber
                 */
                $scope.editPrivateComment = function (subscriber) {
                    if ($scope.lounge.connected.access == 3) {
                        subscriber.savedPrivateComment = subscriber.privateComment;
                        subscriber.editingPrivateComment = true;
                    }
                }

                /**
                 * Désactive l'édition d'un commentaire privé
                 *
                 * @param subscriber
                 */
                $scope.stopEditingPrivateComment = function (subscriber) {
                    if (subscriber.editingPrivateComment) {
                        subscriber.privateComment = subscriber.savedPrivateComment;
                        subscriber.editingPrivateComment = false;
                    }
                }


                /**
                 * Mets à jour un commentaire public
                 *
                 * @param $event
                 * @param subscriber
                 */
                $scope.updatePublicComment = function ($event, subscriber) {
                    if ($event.keyCode === 13) {
                        subscriber.loading = true;

                        $http.put($filter('route')('api_v1_put_lounge_subscriber_publiccomment', {lounge: $scope.lounge.id, subscriber: subscriber.user.id}), {content: subscriber.publicComment})
                                .success(function () {
                                    subscriber.loading = false;
                                    subscriber.editingPublicComment = false;
                                })
                                .error(function () {
                                    subscriber.loading = false;
                                });

                    } else if ($event.keyCode === 27) {
                        $scope.stopEditingPublicComment(subscriber);
                    }
                }

                /**
                 * Mets à jour un commentaire public pour des utilistaeurs ne faisant pas partie de naturapass
                 *
                 * @param $event
                 * @param subscriber
                 */
                $scope.updatePublicCommentNotMember = function ($event, member) {
                    if ($event.keyCode === 13) {
                        member.loading = true;
                        $http.put($filter('route')('api_v1_put_lounge_notmember_publiccomment', {lounge: $scope.lounge.id, id: member.id}), {content: member.publicComment})
                                .success(function () {
                                    member.loading = false;
                                    member.editingPublicComment = false;
                                })
                                .error(function () {
                                    member.loading = false;
                                });

                    } else if ($event.keyCode === 27) {
                        $scope.stopEditingPublicComment(member);
                    }
                }

                /**
                 * Désactive l'édition d'un commentaire priv
                 *
                 * @param subscriber
                 */
                $scope.stopEditingPublicComment = function (subscriber) {
                    if (subscriber.editingPublicComment) {
                        subscriber.publicComment = subscriber.savedPublicComment;
                        subscriber.editingPublicComment = false;
                    }
                }

                /**
                 * Mets à jour un commentaire privé
                 *
                 * @param $event
                 * @param subscriber
                 */
                $scope.updatePrivateComment = function ($event, subscriber) {
                    if ($event.keyCode === 13) {
                        subscriber.loading = true;

                        $http.put($filter('route')('api_v1_put_lounge_subscriber_privatecomment', {lounge: $scope.lounge.id, subscriber: subscriber.user.id}), {content: subscriber.privateComment})
                                .success(function () {
                                    subscriber.loading = false;
                                    subscriber.editingPrivateComment = false;
                                })
                                .error(function () {
                                    subscriber.loading = false;
                                });

                    } else if ($event.keyCode === 27) {
                        $scope.stopEditingPrivateComment(subscriber);
                    }
                }

                /**
                 * Mets à jour un commentaire privé pour des utilistaeurs ne faisant pas partie de naturapass
                 *
                 * @param $event
                 * @param member
                 */
                $scope.updatePrivateCommentNotMember = function ($event, member) {
                    if ($event.keyCode === 13) {
                        member.loading = true;

                        $http.put($filter('route')('api_v1_put_lounge_notmember_privatecomment', {lounge: $scope.lounge.id, id: member.id}), {content: member.privateComment})
                                .success(function () {
                                    member.loading = false;
                                    member.editingPrivateComment = false;
                                })
                                .error(function () {
                                    member.loading = false;
                                });

                    } else if ($event.keyCode === 27) {
                        $scope.stopEditingPrivateComment(member);
                    }
                }

                /**
                 * Mets à jour la participation de l'utilisateur connecté
                 *
                 * @param participation
                 */
                $scope.updateParticipation = function (participation) {
                    var save = $scope.lounge.connected.participation;

                    $scope.lounge.connected.participation = participation;

                    $http.put($filter('route')('api_v1_put_lounge_subscriber_participation', {lounge: $scope.lounge.id, subscriber: $scope.lounge.connected.user.id}), {participation: participation})
                            .success(function () {
                                if ($scope.lounge.connected.participation === 1) {
                                    $scope.lounge.connected.geolocation = false;
                                }
                            })
                            .error(function () {
                                $scope.lounge.connected.participation = save;
                            });
                    ;
                }

                /**
                 * Mets à jour la participation d'un utilisateur membre du salon
                 *
                 * @param participation
                 * @param member
                 */
                $scope.updateMemberParticipation = function (participation, subscriber) {
                    var save = subscriber.participation;
                    subscriber.loading = true;

                    $http.put($filter('route')($scope.params.routing.subscribers.participation, {lounge: $scope.lounge.id, subscriber: subscriber.user.id}), {participation: participation})
                            .success(function () {
                                subscriber.participation = participation;
                                subscriber.loading = false;
                            })
                            .error(function () {
                                subscriber.participation = save;
                                subscriber.loading = false;
                            });
                    ;
                }

                /**
                 * Mets à jour la participation d'un utilisateur non membre du salon
                 *
                 * @param participation
                 * @param member
                 */
                $scope.updateNotMemberParticipation = function (participation, member) {
                    var save = member.participation;
                    member.loading = true;

                    $http.put($filter('route')($scope.params.routing.addnomember.participation, {lounge: $scope.lounge.id, id: member.id}), {participation: participation})
                            .success(function () {
                                member.participation = participation;
                                member.loading = false;
                            })
                            .error(function () {
                                member.participation = save;
                                member.loading = false;
                            });
                    ;
                }

                $scope.removeNotMember = function (member) {
                    member.loading = true;
                    $http._delete($filter('route')($scope.params.routing.addnomember.remove, {lounge: $scope.lounge.id, id: member.id}))
                            .success(function () {
                                member.loading = false;
                            })
                            .error(function () {
                                member.loading = false;
                            });
                };

                /**
                 * Toggle la permission de parler pour un utilisateur
                 *
                 * @param subscriber
                 */
                $scope.quietSubscriber = function (subscriber) {
                    subscriber.loading = true;
                    $http.put($filter('route')('api_v1_put_lounge_subscriber_quiet', {lounge: $scope.lounge.id, subscriber: subscriber.user.id}), {quiet: subscriber.quiet})
                            .success(function () {
                                subscriber.loading = false;

                            })
                            .error(function () {
                                subscriber.quiet = !subscriber.quiet;
                                subscriber.loading = false;
                            });
                    ;

                }

                /**
                 * Toggle l'accès administrateur pour un utilisateur
                 *
                 * @param subscriber
                 */
                $scope.promoteSubscriber = function (subscriber) {
                    subscriber.loading = true;
                    $http.put($filter('route')('api_v1_put_lounge_subscriber_admin', {lounge: $scope.lounge.id, subscriber: subscriber.user.id}))
                            .success(function (response) {
                                subscriber.access = response.isAdmin ? "3" : "2";
                                subscriber.loading = false;
                            })
                            .error(function (response) {
                                subscriber.access = subscriber.access == 3 ? "2" : "3";
                                subscriber.loading = false;
                            });
                }

                /**
                 * Toggle la permission de géolocalisation d'un salon pour un utilisateur
                 *
                 */
                $scope.updateConnectedGeolocation = function () {
                    $http.put($filter('route')('api_v1_put_lounge_subscriber_geolocation', {lounge: $scope.lounge.id, subscriber: $scope.lounge.connected.user.id}), {geolocation: $scope.lounge.connected.geolocation})
                            .success(function () {

                            })
                            .error(function () {
                                $scope.lounge.connected.geolocation = !$scope.lounge.connected.geolocation;
                            });
                    ;
                }

                $scope.openAdministrationModal = function () {
                    $modal.open({
                        controller: 'ModalLoungeGroupSubscribersController',
                        templateUrl: 'modal.subscribers.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return $scope.lounge;
                            },
                            method: function () {
                                return 'admin';
                            }
                        }
                    });
                }

                $scope.openAddNotMembersModal = function () {
                    $modal.open({
                        controller: 'ModalLoungeAddNotMemberController',
                        templateUrl: 'modal.add-not-member.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return $scope.lounge;
                            },
                            subscribersNotMember: function () {
                                return $scope.lounge.subscribersNotMember;
                            }
                        }
                    });
                }


                $scope.openBanishModal = function () {
                    $modal.open({
                        controller: 'ModalLoungeGroupSubscribersController',
                        templateUrl: 'modal.subscribers.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return $scope.lounge;
                            },
                            method: function () {
                                return 'banish';
                            }
                        }
                    });
                }

                $scope.openValidationModal = function () {
                    $modal.open({
                        controller: 'ModalLoungeGroupSubscribersController',
                        templateUrl: 'modal.subscribers.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return $scope.lounge;
                            },
                            method: function () {
                                return 'validation';
                            }
                        }
                    });
                };

                $scope.openSubscribersModal = function () {
                    $modal.open({
                        controller: 'ModalLoungeGroupSubscribersController',
                        templateUrl: 'modal.subscribers.html',
                        resolve: {
                            params: function () {
                                return $scope.params;
                            },
                            entity: function () {
                                return $scope.lounge;
                            },
                            method: function () {
                                return false;
                            }
                        }
                    });
                }

                $scope.filterSubscribers = function (subscriber) {
                    return subscriber.user.id != $scope.lounge.connected.user.id && (subscriber.access == 2 || subscriber.access == 3);
                };

                $scope.filterSubscribersInvited = function (subscriber) {
                    return subscriber.access == 0;
                };

                $scope.filterSubscribersParticipe = function (subscriber) {
                    return subscriber.user.id != $scope.lounge.connected.user.id && (subscriber.access == 2 || subscriber.access == 3) && subscriber.participation == 1;
                };

                $scope.filterSubscribersNotParticipe = function (subscriber) {
                    return subscriber.user.id != $scope.lounge.connected.user.id && (subscriber.access == 2 || subscriber.access == 3) && subscriber.participation == 0;
                };

                $scope.filterSubscribersNeutral = function (subscriber) {
                    return subscriber.user.id != $scope.lounge.connected.user.id && (subscriber.access == 2 || subscriber.access == 3) && subscriber.participation == 2;
                };

                $scope.filterSubscribersNotMemberParticipe = function (subscriber) {
                    return subscriber.participation == 1;
                };

                $scope.filterSubscribersNotMemberNotParticipe = function (subscriber) {
                    return subscriber.participation == 0;
                };

                $scope.filterSubscribersNotMemberNeutral = function (subscriber) {
                    return subscriber.participation == 2;
                };
            }])

        .controller('ModalLoungeAddNotMemberController', ['$scope', '$http', '$filter', '$modalInstance', 'params', 'entity', 'subscribersNotMember', function ($scope, $http, $filter, $instance, params, entity, subscribersNotMember) {
                $scope.params = params;

                $scope.newmember = {
                    firstname: '',
                    lastname: '',
                    publicComment: '',
                    privateComment: '',
                    participation: 2,
                    lounge: entity.id
                };

                $scope.data = {
                    entity: entity,
                    loading: false
                };

                $scope.addMember = function () {
                    $scope.data.loading = true;

                    $http.post($filter('route')($scope.params.routing.addnomember.post, {lounge: $scope.data.entity.id}), $scope.newmember)
                            .success(function (response) {
//                    subscribersNotMember.push(response.subscribersNotMember);
                                $scope.data.loading = false;
                                $instance.dismiss('cancel');
                            })
                            .error(function () {
                                $scope.data.loading = false;

                            });
                };

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                }

                $scope.participationNoMember = function (participation) {
                    $scope.newmember.participation = participation;
                }
            }]);