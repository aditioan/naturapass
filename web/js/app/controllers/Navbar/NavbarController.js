angular.module('app')
    .controller('NavbarController', ['$scope', '$http', '$modal', '$filter', 'socket', 'ipCookie', 'factory:UserFriendship', function ($scope, $http, $modal, $filter, socket, ipCookie, $factoryFriendship) {

        $scope.notifications = [];
        $scope.unreadedNotifications = 0;
        $scope.notificationsLoading = false;
        $scope.notificationsTitle = 'Notifications';

        $scope.invitations = [];
        $scope.untreatedInvitations = 0;
        $scope.invitationsLoading = false;
        $scope.invitationsTitle = 'Invitations';

        $scope.messages = [];
        $scope.unreadedMessages = 0;
        $scope.messagesLoading = false;
        $scope.noMoreMessage = false;
        $scope.messagesTitle = 'Messages';

        /**
         * Open chat message
         *
         * @param message
         **/
        $scope.openChat = function (message, focused) {
            focused = typeof focused !== 'undefined' ? focused : true;

            angular.element($('#docking-panel')).scope().openChat(message, focused);

            if (message !== null) {
                message.opened = true;
                //if(angular.isDefined(message.unreadCount)) $scope.unreadedMessages = $scope.unreadedMessages - message.unreadCount;
                //if($scope.unreadedMessages < 0) $scope.unreadedMessages = 0;
            }
        };

        $scope.readAllNotifications = function () {
            $scope.notificationsLoading = true;
            $http.put($filter('route')('api_v2_put_notifications_readall'))
                .success(function (data) {
                    $scope.notificationsLoading = false;
                    angular.forEach($scope.notifications, function (notification, index) {
                        if (!notification.readed) {
                            notification.readed = true;
                            notification.loading = false;
                            $scope.unreadedNotifications--;
                        }
                    });

                })
                .error(function () {
                    $scope.notificationsLoading = false;
                });
        }

        /**
         * Evenement: Connexion de l'utilisateur
         *
         * Récupération des notifications & des messages
         */
        $scope.$on('npevent-user/connected', function (event, user) {
            $scope.connectedUser = user;

            if (user) {

                socket && socket.on('npevent-notification:incoming', function (response) {

                    angular.forEach($scope.notifications, function (notification, index) {
                        if (notification.type === response.type && notification.object_id === response.object_id) {
                            $scope.notifications.splice(index, 1);

                            if (!notification.readed) {
                                $scope.unreadedNotifications--;
                            }
                        }
                    });

                    $scope.notifications.unshift(response);
                    $scope.unreadedNotifications++;
                });

                $scope.notificationsLoading = true;

                $http.get($filter('route')('api_v2_get_notifications'))
                    .success(function (data) {
                        $scope.notifications = data.notifications;
                        $scope.unreadedNotifications = $scope.notifications.length;

                        $scope.notificationsTitle = $filter('transchoice')('nav.pop_notification.nb_notification', $scope.notifications.length, {'count': $scope.notifications.length}, 'nav');
                        $scope.notificationsLoading = false;
                    })
                    .error(function () {
                        $scope.notificationsLoading = false;
                    });

                $scope.messagesLoading = true;

                $http.get($filter('route')('api_v1_get_user_conversations'))
                    .success(function (data) {
                        if (data.messages === undefined || !data.messages.length) {
                            $scope.messagesLoading = false;
                            return;
                        }

                        // // cookie for opened conversations
                        var convIds = [];
                        if (ipCookie('oc') !== undefined && ipCookie('oc') != '')
                            convIds = String(ipCookie('oc')).split(',');

                        angular.forEach(data.messages, function (message, index) {
                            $scope.unreadedMessages += parseInt(message.unreadCount);

                            var participants = message.conversation.participants;
                            message.conversation.title = $filter('transchoice')('chat.title', participants.length, {
                                'count': participants.length - 1,
                                'name' : participants.length > 1 ? participants[0].firstname : participants[0].fullname
                            }, 'message');

                            if (convIds.indexOf(String(message.conversation.id)) > -1)
                                $scope.openChat(message, false);

                        });

                        $scope.messages = data.messages;

                        $scope.messagesTitle = $filter('transchoice')('nav.pop_message.nb_message', $scope.messages.length, {'count': $scope.messages.length}, 'nav');
                        $scope.messagesLoading = false;

                    })
                    .error(function () {
                        $scope.messagesLoading = false;
                    });

                socket && socket.on('npevent-invitation:incoming', function (response) {
                    $scope.invitations.unshift(response.sender);
                    $scope.untreatedInvitations++;
                });

                $scope.invitationsLoading = true;

                $http.get($filter('route')('api_v1_get_users_waiting'))
                    .success(function (data) {
                        $scope.invitations = data.users;
                        $scope.untreatedInvitations = $scope.invitations.length;

                        $scope.notificationsTitle = $filter('transchoice')('nav.pop_invite.nb_invitation', $scope.invitations.length, {'count': $scope.invitations.length}, 'nav');
                        $scope.invitationsLoading = false;
                    })
                    .error(function () {
                        $scope.invitationsLoading = false;
                    });
            }

        });

        /**
         * A la lecture d'une notification
         *
         * @param notification
         */
        $scope.readNotification = function (notification) {
            if (!notification.readed && !notification.loading) {
                notification.loading = true;

                if (notification.id) {
                    $http.put($filter('route')('api_v2_put_notification_read', {notification: notification.id}))
                        .success(function () {
                            notification.readed = true;
                            notification.loading = false;

                            $scope.unreadedNotifications--;
                        })
                        .error(function () {
                            notification.loading = false;
                        });
                } else {
                    notification.readed = true;
                    notification.loading = false;

                    $scope.unreadedNotifications--;
                }
            }
        };

        /**
         * Accepter une invitation d'ami
         *
         * @param invitation
         */
        $scope.acceptInvitation = function (invitation) {
            invitation.loading = true;

            $factoryFriendship.confirm(invitation.id)
                .success(function () {
                    invitation.validated = true;
                    invitation.loading = false;

                    $scope.untreatedInvitations--;
                })
                .error(function () {
                    invitation.loading = false;
                });
        };

        /**
         * Load more user's conversations
         *
         * @returns
         */
        $scope.loadMoreConversation = function () {
            if ($scope.messagesLoading || $scope.noMoreMessage)
                return;

            $scope.messagesLoading = true;

            $http.get($filter('route')('api_v1_get_user_conversations', {offset: $scope.messages.length}))
                .success(function (data) {
                    if (data.messages === undefined || !data.messages.length) {
                        $scope.messagesLoading = false;
                        $scope.noMoreMessage = true;
                        return;
                    }

                    angular.forEach(data.messages, function (message, index) {
                        var participants = message.conversation.participants;
                        message.conversation.title = $filter('transchoice')('chat.title', participants.length, {
                            'count': participants.length - 1,
                            'name' : participants.length > 1 ? participants[0].firstname : participants[0].fullname
                        }, 'message');

                        $scope.messages.push(message);
                    });

                    $scope.messagesTitle = $filter('transchoice')('nav.pop_message.nb_message', $scope.messages.length, {'count': $scope.messages.length}, 'nav');
                    $scope.messagesLoading = false;
                })
                .error(function () {
                    $scope.messagesLoading = false;
                });
        };

        /**
         * Refuser une invitation d'ami
         *
         * @param invitation
         */
        $scope.refuseInvitation = function (invitation) {
            invitation.loading = true;

            $factoryFriendship.remove(invitation.id)
                .success(function () {
                    invitation.deleted = true;
                    invitation.loading = false;

                    $scope.untreatedInvitations--;
                })
                .error(function () {
                    invitation.loading = false;
                });
        };

        /**
         * Signaler un problème
         */
        $scope.reportProblem = function () {
            $modal.open({
                templateUrl: 'modal.report-problem.html',
                size       : 'lg',
                controller : 'ModalReportProblemController'
            });
        };

    }])

    /**
     * Controller
     *
     * Signalement d'un problème
     */
    .controller('ModalReportProblemController', ['$scope', '$http', '$filter', '$modalInstance', function ($scope, $http, $filter, $modalInstance) {
        $scope.email = {error: false};
        $scope.loading = false;
        $scope.error = false;

        $scope.ok = function () {
            $scope.email.error = false;
            $scope.loading = true;

            $http.post($filter('route')('api_v1_post_user_problem'), {'email': $scope.email})
                .success(function () {
                    $modalInstance.close();
                    $scope.loading = false;
                })
                .error(function (data) {
                    $scope.email.error = data[0].message;
                    $scope.loading = false;
                });

        };

        $scope.cancel = function () {
            $modalInstance.dismiss('cancel');
        };
    }]);
