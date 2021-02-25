angular.module('app')
    .controller('DockingPanelController', ['$scope', '$http', '$modal', '$filter', 'socket', '$interval', '$window', 'ipCookie', function ($scope, $http, $modal, $filter, socket, $interval, $window, ipCookie) {
        $scope.chatData = [];
        $scope.pendingMessage = false;
        $scope.friends = [];
        $scope.minimizeCount = 0;
        $scope.showMinizeItem = false;

        $scope.searchUserOption = {
            loadingUsers : false,
            noMoreMessage: false,
            offset       : 0,
            limit        : 4
        }

        /**
         * Evenement: Connexion de l'utilisateur
         *
         * Listening incoming chat message
         */
        $scope.$on('npevent-user/connected', function (event, user) {
            $scope.connectedUser = user;

            if (user) {
                socket && socket.on('npevent-chat-message:incoming', function (response) {
                    // play sound notifiaction
                    //window.console.log(response);
                    var notification = $('<audio style="display:none;"></audio>').attr('src', window.location.protocol + '//' + window.location.hostname + '/sounds/chat_notifiaction.mp3');
                    $(notification)[0].play();

                    if (response.messageId === undefined || !response.messageId)
                        return;

                    var openedConversation = null;
                    response.opened = false;

                    angular.forEach($scope.chatData, function (chatItem, index) {
                        //If existed conversion in chatData then just open it
                        if (response.conversation.id == chatItem.id) {
                            openedConversation = chatItem;
                            return
                        }
                    });
                    $('[data-toggle="tooltip"]').tooltip();

                    var participants = response.conversation.participants;
                    angular.forEach(participants, function (participant, index) {
                        if (participant.id == $scope.connectedUser.id) {
                            participants.splice(index, 1);
                            return;
                        }
                    });

                    response.conversation.title = $filter('transchoice')('chat.title', participants.length, {'count': participants.length - 1, 'name': participants.length > 1 ? response.owner.firstname : response.owner.fullname}, 'message');

                    function popoverMessageHandle() {
                        var popoverMessages = angular.element($('#navbar-ctrl')).scope().messages;
                        var unreadCount = 1;
                        //Remove old conversation
                        angular.forEach(popoverMessages, function (message, index) {
                            if (message.conversation.id == response.conversation.id) {
                                response.opened = angular.isDefined(message.opened) ? message.opened : false;
                                unreadCount += angular.isDefined(message.unreadCount) ? parseInt(message.unreadCount) : 0;
                                popoverMessages.splice(index, 1);
                                return;
                            }
                        });

                        response.unreadCount = unreadCount;
                        response.conversation.unreadCount = unreadCount;

                        //Push new conversation
                        popoverMessages.unshift({
                            "userId"      : response.userId,
                            "messageId"   : response.messageId,
                            "content"     : response.content,
                            "updated"     : response.updated,
                            "created"     : response.created,
                            "owner"       : response.owner,
                            "conversation": response.conversation,
                            "unreadCount" : unreadCount,
                            "opened"      : response.opened
                        });
                    }

                    popoverMessageHandle();

                    if (openedConversation) {
                        response.opened = true;
                        params = {"id": null, "messageId": response.messageId}

                        openedConversation.unreadCount = response.unreadCount;

                        openedConversation.messages.push({
                            "messageId": response.messageId,
                            "content"  : response.content,
                            "owner"    : response.owner,
                            "updated"  : response.updated,
                            "created"  : response.created
                        });

                        if (openedConversation.opened) {
                            if (openedConversation.focus) {
                                $scope.updateMessageIsRead(openedConversation);
                            }
                            else {
                                angular.element($("#navbar-ctrl")).scope().unreadedMessages++;
                            }

                            $scope.hasIncomingMessages(openedConversation);

                            if (openedConversation.messageCursor == "top")
                                openedConversation.messageCursor = "middle";
                        }
                        else {
                            angular.element($("#navbar-ctrl")).scope().unreadedMessages++;
                        }

                        if (openedConversation.participants.length < response.conversation.participants.length) {
                            openedConversation.participants = response.conversation.participants;
                            openedConversation.title = $filter('transchoice')('chat.title', openedConversation.participants.length, {'count': openedConversation.participants.length - 1, 'name': openedConversation.participants.length > 1 ? response.owner.firstname : response.owner.fullname}, 'message');
                        }
                    } else {
                        angular.element($("#navbar-ctrl")).scope().unreadedMessages++;
                    }
                    $('[data-toggle="tooltip"]').tooltip();
                });
            }

        });

        $scope.deleteMessage = function (chatData, message) {
            $http._delete($filter('route')('api_v1_delete_chat_message', {message: message.messageId}))
                .success(function () {
                    //console.log($scope.chatData);
                    chatData.messages.splice(chatData.messages.indexOf(message), 1);
                })
                .error(function () {
                });
        };

        /**
         * Update owner message of conversation is read.
         *
         * @param conversation
         * @returns
         */
        $scope.updateMessageIsRead = function (conversation) {
            if (angular.isDefined(conversation.unreadCount) && conversation.unreadCount <= 0)
                return;

            var params = {"id": conversation.id};

            $http.put($filter('route')('api_v1_put_read_message'), {conversation: params})
                .success(function (data) {
                    if (!data || angular.isUndefined(data.readCount)) {
                        return;
                    }

                    var popoverMessages = angular.element($('#navbar-ctrl')).scope().messages;
                    data.readCount = parseInt(data.readCount);

                    angular.forEach(popoverMessages, function (message, index) {
                        if (message.conversation.id == conversation.id) {
                            if (angular.isDefined(message.unreadCount)) {
                                message.unreadCount -= data.readCount;
                                message.unreadCount = message.unreadCount < 0 ? 0 : message.unreadCount;
                            }

                            return;
                        }
                    });

                    if (angular.isUndefined(conversation.unreadCount))
                        conversation.unreadCount = 0;
                    else {
                        conversation.unreadCount -= data.readCount;
                        conversation.unreadCount = conversation.unreadCount < 0 ? 0 : conversation.unreadCount;
                    }

                    var unreadedMessage = angular.element($("#navbar-ctrl")).scope().unreadedMessages;
                    unreadedMessage = unreadedMessage - data.readCount;
                    if (unreadedMessage < 0)
                        unreadedMessage = 0;

                    angular.element($("#navbar-ctrl")).scope().unreadedMessages = unreadedMessage;
                })
                .error(function (data) {

                });
        }

        /**
         * Open new message box for an user
         * @params Object user
         * return
         */
        $scope.openNewMessage = function (user) {
            //Check if new chat message existed
            var newConversation = null;
            var chatConversation = null;

            angular.forEach($scope.chatData, function (data, index) {
                var countParticipant = angular.isDefined(data.participants) && data.participants !== null ? data.participants.length : 0;
                var countPendingParticipant = angular.isDefined(data.pendingParticipants) && data.pendingParticipants !== null ? data.pendingParticipants.length : 0;

                if (null === data.id && (countPendingParticipant == 0 || (countPendingParticipant == 1 && data.pendingParticipants[0].id == user.id))) {
                    newConversation = data;
                }
                else if (data.id !== null && (countParticipant == 0 || (countParticipant == 1 && data.participants[0].id == user.id))) {
                    chatConversation = data;
                    return;
                }
            });

            if (chatConversation === null) {
                chatConversation = newConversation;
            }

            if (chatConversation !== null) {
                if (!chatConversation.opened)
                    $scope.sortChatTab(chatConversation);

                chatConversation.openChat = true;
                chatConversation.opened = true;
                chatConversation.focus = true;

                if (chatConversation.minimize === true)
                    $scope.minimizeCount--;
                chatConversation.minimize = false;

                if (chatConversation.id === null) {
                    $scope.pendingMessage = true;
                    chatConversation.showHeader = true;

                    chatConversation.pendingParticipants = [{
                        id            : user.id,
                        profilepicture: user.profilepicture,
                        text          : user.fullname,
                        usertag       : user.usertag
                    }];
                }

                return;
            }

            var chatConversation = {"id": null};
            $scope.pendingMessage = true;

            //Set open chat = true
            chatConversation.opened = true;
            //Show adding participant input
            chatConversation.showHeader = true;
            //Focus inputtext
            chatConversation.headerFocus = true;

            //Set chat title
            chatConversation.title = $filter('transchoice')('chat.title', 0, {}, 'message');
            ;

            chatConversation.pendingParticipants = [{
                id            : user.id,
                profilepicture: user.profilepicture,
                text          : user.fullname,
                usertag       : user.usertag
            }];

            //Push chatConversation to chatData
            $scope.chatData.push(chatConversation);
        };

        /**
         * Open message box
         *
         * @param Object message
         * @returns
         */
        $scope.openChat = function (message, focused) {
            focused = typeof focused !== 'undefined' ? focused : true;

            //If is new message
            if (message === null) {
                //Check if new chat message existed
                angular.forEach($scope.chatData, function (data, index) {
                    if (null === data.id) {
                        if (!data.opened)
                            $scope.sortChatTab(data);

                        data.opened = true;
                        data.focus = focused;

                        if (data.minimize === true)
                            $scope.minimizeCount--;
                        data.minimize = false;

                        data.showHeader = true;
                        $scope.pendingMessage = true;
                        return;
                    }
                });

                //Return if new chat message opened
                if ($scope.pendingMessage) {
                    $(".popover.popover-messages").hide();
                    $("#navbar-ctrl .navbar-toggle").trigger("click");
                    return;
                }

                $scope.pendingMessage = true;

                var chatConversation = {"id": null};

                //Set open chat = true
                chatConversation.opened = true;
                //Show adding participant input
                chatConversation.showHeader = true;
                //Focus inputtext
                chatConversation.headerFocus = focused;

                //Set chat title
                chatConversation.title = $filter('transchoice')('chat.title', 0, {}, 'message');
                ;

                //Push chatConversation to chatData
                $scope.chatData.push(chatConversation);

                //Hide message popover
                $(".popover.popover-messages").hide();

                $("#navbar-ctrl .navbar-toggle").trigger("click");

                //$scope.getFriends(chatConversation);
            }
            //Open existed conversation chat
            else {
                $scope.checkMessage(message, focused);
                //Hide message popover
                $(".popover.popover-messages").hide();
                $("#navbar-ctrl .navbar-toggle").trigger("click");

                // cookie for opened conversations
                if (ipCookie('oc') !== undefined && ipCookie('oc') != '') {
                    // conversation not yet in cookie
                    if (String(ipCookie('oc')).split(',').indexOf(String(message.conversation.id)) === -1)
                        ipCookie('oc', String(ipCookie('oc') + (ipCookie('oc') ? ',' : '') + message.conversation.id), {path: '/'});

                } else {
                    ipCookie('oc', String(message.conversation.id), {path: '/'});
                }
            }
            $('[data-toggle="tooltip"]').tooltip();

        };

        /**
         * Check status of message box
         *
         * @param Object message
         * @returns
         */
        $scope.checkMessage = function (message, focused) {
            focused = typeof focused !== 'undefined' ? focused : true;

            var existed = false;

            //Check conversation existed
            angular.forEach($scope.chatData, function (data, index) {
                //If existed conversion in chatData then only open it
                if (message.conversation.id == data.id) {
                    existed = true;
                    if (!data.opened)
                        $scope.sortChatTab(data);

                    data.opened = true;
                    data.focus = focused;

                    data.unreadCount = message.unreadCount;
                    $scope.updateMessageIsRead(data);

                    if (data.minimize === true)
                        $scope.minimizeCount--;
                    data.minimize = false;
                    return
                }
            });

            //If not yet exist then create conversation chat
            if (!existed) {
                var chatConversation = message.conversation;
                chatConversation.unreadCount = message.unreadCount;
                chatConversation.userId = message.userId;
                chatConversation.messages = [{
                    "id"     : message.messageId,
                    "content": message.content,
                    "updated": message.updated,
                    "created": message.created,
                    "owner"  : message.owner,
                }];

                chatConversation.opened = true;
                chatConversation.focus = true;

                if (chatConversation.loadedMessage === null || chatConversation.loadedMessage === undefined) {
                    $http.get($filter('route')('api_v1_get_conversation_messages', {conversationId: chatConversation.id, reverse: 1}))
                        .success(function (data) {
                            if (data && data.messages !== undefined) {
                                chatConversation.messages = data.messages;
                            }

                            chatConversation.loadedMessage = true;

                            $scope.updateMessageIsRead(chatConversation);
                            $('[data-toggle="tooltip"]').tooltip();
                        })
                        .error(function (data) {
                            chatConversation.loadedMessage = true;
                        });
                }

                var participantName = chatConversation.participants.length > 1 ? chatConversation.participants[0].firstname : chatConversation.participants[0].fullname;
                chatConversation.title = $filter('transchoice')('chat.title', chatConversation.participants.length, {'count': chatConversation.participants.length - 1, 'name': participantName}, 'message');

                $scope.chatData.push(chatConversation);

                //$scope.getFriends(chatConversation);
            }
            $('[data-toggle="tooltip"]').tooltip();

        }

        /**
         * Get user friends
         *
         * @param conversation
         * @return
         */
        $scope.getFriends = function (query) {
            if ($scope.friends.length) {
                if (angular.isDefined(conversation)) {
                    //Set friends for chatConverstaion object
                    conversation.friends = angular.copy($scope.friends);

                    if (angular.isDefined(conversation.participants) && angular.isArray(conversation.participants)) {
                        angular.forEach(conversation.participants, function (participant, index1) {
                            angular.forEach(conversation.friends, function (friend, index2) {
                                if (friend.id == participant.id) {
                                    conversation.friends.splice(index2, 1);
                                    return;
                                }
                            });
                        });
                    }

                    if (angular.isDefined(conversation.pendingParticipants)) {
                        conversation.pendingParticipants = [];
                    }
                }

                return;
            }

            $http.get($filter('route')('api_v1_get_me_friends'))
                .success(function (data) {
                    if (data && data.friends !== undefined) {
                        $scope.friends = data.friends;

                        if (angular.isDefined(conversation)) {
                            //Set friends for chatConverstaion object
                            conversation.friends = angular.copy($scope.friends);

                            if (angular.isDefined(conversation.participants) && angular.isArray(conversation.participants)) {
                                angular.forEach(conversation.participants, function (participant, index1) {
                                    angular.forEach(conversation.friends, function (friend, index2) {
                                        if (friend.id == participant.id) {
                                            conversation.friends.splice(index2, 1);
                                            return;
                                        }
                                    });
                                });
                            }

                            if (angular.isDefined(conversation.pendingParticipants)) {
                                conversation.pendingParticipants = [];
                            }
                        }
                    }
                })
                .error(function (data) {
                });
        }

        /**
         * Get naturapass users
         *
         * @param event
         * @param string query
         * @param object conversation
         * @return
         */
        $scope.searchUsers = function ($event, query, conversation) {
            if ($event !== null && $event.which === 13) {
                $scope.enterToAddParticipant($event, conversation)
                return;
            }

            if ($event !== null) {
                $scope.searchUserOption.offset = 0;
                $scope.searchUserOption.noMoreMessage = false;
            }

            if ($scope.searchUserOption.loadingUsers || $scope.searchUserOption.noMoreMessage)
                return;

            $scope.searchUserOption.loadingUsers = true;

            $http.get($filter('route')('api_v2_get_users_search') + '?page_limit=' + $scope.searchUserOption.limit + '&page_offset=' + $scope.searchUserOption.offset + '&q=' + query)
                .success(function (response) {
                    if (response.users === undefined || !response.users.length) {
                        $scope.searchUserOption.loadingUsers = false;
                        $scope.searchUserOption.noMoreMessage = true;
                        return;
                    }

                    if (angular.isDefined(conversation.pendingParticipants) && conversation.pendingParticipants && conversation.pendingParticipants.length > 0) {
                        angular.forEach(conversation.pendingParticipants, function (participant, pIndex) {
                            angular.forEach(response.users, function (user, uIndex) {
                                if (participant.id == user.id) {
                                    response.users.splice(uIndex, 1);
                                    return;
                                }
                            });
                        });
                    }

                    if ($event !== null) {
                        $scope.friends = response.users;
                    }
                    else {
                        $.merge($scope.friends, response.users);
                    }

                    $scope.searchUserOption.loadingUsers = false;

                    $scope.searchUserOption.offset = $scope.friends.length;
                })
                .error(function () {
                    $scope.searchUserOption.loadingUsers = false;
                })
        };

        /**
         *
         */
        $scope.addParticipant = function (conversation, participant) {
            participant.text = participant.fullname;

            angular.forEach($scope.friends, function (data, index) {
                if (participant.id == data.id) {
                    $scope.friends.splice(index, 1);
                    return;
                }
            });

            if (conversation.pendingParticipants === undefined)
                conversation.pendingParticipants = [];
            conversation.pendingParticipants.push(participant);

            conversation.query = null;
            conversation.headerFocus = true;

            $scope.resizeChatBody(conversation)
        }

        /**
         * Remove participant

         * @param conversation
         * @param participantId
         *
         * @returns
         */
        $scope.removeParticipant = function (conversation, participantId) {
            angular.forEach(conversation.pendingParticipants, function (data, index) {
                if (participantId == data.id) {
                    conversation.pendingParticipants.splice(index, 1);
                    $scope.friends.push(data);
                    return;
                }
            });

            conversation.headerFocus = true;

            $scope.resizeChatBody(conversation);
        }

        /**
         * Add more participant to existed conversation
         *
         * @param JSON $conversation
         * @returns
         */
        $scope.addMoreParticipants = function (conversation) {
            conversation.query = null;

            if (angular.isDefined(conversation.pendingParticipants) && conversation.pendingParticipants.length) {
                angular.forEach(conversation.pendingParticipants, function (pendingParticipant, index1) {
                    var $found = false;
                    angular.forEach(conversation.participants, function (participant, index2) {
                        if (participant.id == pendingParticipant.id) {
                            $found = true;
                            return;
                        }
                    });

                    if (!$found) {
                        conversation.participants.push({
                            "id"       : pendingParticipant.id,
                            "firstname": pendingParticipant.text,
                            "lastname" : "",
                            "fullname" : pendingParticipant.text,
                            "usertag"  : pendingParticipant.usertag
                        });
                    }
                });

                conversation.title = $filter('transchoice')('chat.title', conversation.participants.length, {'count': conversation.participants.length - 1, 'name': conversation.participants.length > 1 ? conversation.participants[0].firstname : conversation.participants[0].fullname}, 'message');

                var params = {"id": conversation.id, "participants": conversation.pendingParticipants}

                $http.post($filter('route')('api_v1_post_conversation_participants'), {conversation: params})
                    .success(function (data) {
                        if (data && data.message !== undefined) {

                        }
                    })
                    .error(function (data) {
                        // console.log("Fail to add participants");
                    });

                conversation.pendingParticipants = [];
            }

            conversation.showHeader = false;
            $scope.resizeChatBody(conversation);
        }

        /*
         * Set auto scoll for chat box
         *
         * @param JSON conversation
         * @returns
         */
        $scope.enableAutoScroll = function ($conversation) {
            $conversation.messageCursor = "bottom";
        }

        /*
         * Load messages of converstaion
         *
         * @param JSON conversation
         * @returns
         */
        $scope.loadMoreConversationMessages = function ($conversation) {
            //Return if still loading message
            if ($conversation.loadingMessage !== undefined && $conversation.loadingMessage || $conversation.noMoreMessage === true) {
                return;
            }

            var $params = {conversationId: $conversation.id};

            if ($conversation.messages !== undefined && $conversation.messages.length) {
                var $message = $conversation.messages[0];
                $params.updated = $message.updated;
                $params.messageId = $message.messageId;
            }
            else
                $conversation.messages = [];

            $conversation.loadingMessage = true;
            $http.get($filter('route')('api_v1_get_conversation_messages', $params))
                .success(function (data) {
                    if (data && data.messages !== undefined) {
                        angular.forEach(data.messages, function (message, index) {
                            $conversation.messages.unshift({
                                messageId: message.messageId,
                                content  : message.content,
                                owner    : message.owner,
                                updated  : message.updated,
                                created  : message.created
                            });
                        });

                        $conversation.messageCursor = "top";
                    }

                    if (data.messages.length <= 0) {
                        $conversation.noMoreMessage = true;
                    }

                    $conversation.loadingMessage = false;
                    $('[data-toggle="tooltip"]').tooltip();
                })
                .error(function (data) {
                    $conversation.loadingMessage = false;
                });
        }

        /**
         * Resize height of chat body
         */
        $scope.resizeChatBody = function (conversation) {
            $chatBox = $("#chattab-" + (conversation.id === null ? "" : conversation.id));
            $c = angular.isDefined(conversation.pendingParticipants) ? conversation.pendingParticipants.length : 0;
            $(".npChatLayoutBody", $chatBox).height(173 - ($c * 25));
        }

        /**
         * Close chat box
         *
         * @param conversationId
         * @returns
         */
        $scope.closeChat = function (conversationId) {
            angular.forEach($scope.chatData, function (data, index) {
                if (conversationId == data.id) {
                    $scope.maximizeTab(data);

                    data.opened = false;
                    data.minimize = false;

                    $scope.minimizeTabs();

                    //$scope.getFriends(data);

                    // cookie for opened conversations
                    if (ipCookie('oc') !== undefined && ipCookie('oc') != '') {
                        var convIds = String(ipCookie('oc')).split(',');
                        if (convIds.indexOf(String(conversationId)) > -1)
                            convIds.splice(convIds.indexOf(String(conversationId)), 1);

                        if (convIds.length > 0)
                            ipCookie('oc', convIds.join(), {path: '/'});
                        else
                            ipCookie.remove('oc', {path: '/'});
                    }

                    if (conversationId === null) {
                        $scope.pendingMessage = false;
                        return;
                    }

                    var popoverMessages = angular.element($('#navbar-ctrl')).scope().messages;

                    //Remove old conversation
                    angular.forEach(popoverMessages, function (message, index) {
                        if (message.conversation.id == conversationId) {
                            message.opened = false;
                            return;
                        }
                    });

                    return;
                }
            });

        }

        /**
         * Re-open minimized conversation
         *
         * @param conversation
         * @returns
         */
        $scope.reOpenChat = function (conversation) {
            $scope.sortChatTab(conversation);

            $scope.maximizeTab(conversation);

            conversation.opened = true;
            conversation.focus = true;

            $scope.updateMessageIsRead(conversation);

            if (conversation.minimize === true)
                $scope.minimizeCount--;
            conversation.minimize = false;

            $scope.showMinizeItem = false;
        }

        /**
         * Reorder chat tab
         *
         * @param conversation
         *
         * @returns
         */
        $scope.sortChatTab = function (conversation) {
            var $openedIndex = 0;
            var $currentIndex = 0;

            angular.forEach($scope.chatData, function (data, index) {
                if (data.opened && data.minimize !== true && conversation.id != data.id) {
                    $openedIndex = index;
                }
                else if (conversation.id == data.id) {
                    $currentIndex = index;
                }
            });

            if ($openedIndex > $currentIndex) {
                $scope.chatData.splice($currentIndex, 1);
                $scope.chatData.push(conversation);
            }
        }

        /*
         *
         * @param {type} obj
         * @param {type} conversation
         * @returns {undefined}
         */
        $scope.focusChat = function (conversation) {
            if (conversation.minimizedTab) {
                $scope.maximizeTab(conversation);
            }

            $scope.readIncomingMessages(conversation);

            if (!conversation.showHeader) {
                conversation.focus = true;
                $scope.updateMessageIsRead(conversation);
            }

        }

        /**
         * Send message
         *
         * @param obj
         * @param conversation
         *
         * @returns
         */
        $scope.sendMessage = function (obj, conversation) {
            /*
             * Resize height
             */
            var textarea = angular.element(obj.target);
            var content = textarea.val().trim();
            var minHeight = 28,
                paddingLeft = textarea.css('paddingLeft'),
                paddingRight = textarea.css('paddingRight');

            var $shadow = angular.element('<div class="txt-shadow"></div>').css({
                position  : 'absolute',
                top       : -10000,
                left      : -10000,
                width     : textarea[0].offsetWidth - parseInt(paddingLeft || 0) - parseInt(paddingRight || 0),
                fontSize  : textarea.css('fontSize'),
                fontFamily: textarea.css('fontFamily'),
                lineHeight: textarea.css('lineHeight'),
                resize    : 'none'
            });
            angular.element(document.body).append($shadow);

            var update = function () {
                var times = function (string, number) {
                    for (var i = 0, r = ''; i < number; i++) {
                        r += string;
                    }
                    return r;
                }

                var val = content.replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/&/g, '&amp;')
                    .replace(/\n$/, '<br/>&nbsp;')
                    .replace(/\n/g, '<br/>')
                    .replace(/\s{2,}/g, function (space) {
                        return times('&nbsp;', space.length - 1) + ' '
                    });
                $shadow.html(val);

                var newHeight = Math.max($shadow[0].offsetHeight + 10 /* the "threshold" */, minHeight);

                if (newHeight < minHeight)
                    newHeight = minHeight;
                if (newHeight > $scope.maxTextareaHeight)
                    newHeight = $scope.maxTextareaHeight;

                textarea.css('height', newHeight + 'px');

                $chatBox = $("#chattab-" + (conversation.id === null ? "" : conversation.id));

                var npChatLayoutBodyHeight = 173;
                if (newHeight > 100)
                    newHeight = 100;

                $(".npChatLayoutBody", $chatBox).height(npChatLayoutBodyHeight - (newHeight - minHeight));

                conversation.messageCursor = "bottom";
            }

            function sendMessage() {
                if (content == "")
                    return;

                // remove shadow textarea
                $('body > .txt-shadow').remove();

                // textarea back to normal height
                textarea.css('height', minHeight);

                var message = {
                    "conversationId"     : conversation.id,
                    "content"            : content,
                    "participants"       : conversation.participants === undefined ? null : conversation.participants,
                    "pendingParticipants": conversation.pendingParticipants === undefined ? null : conversation.pendingParticipants
                };

                conversation.sendingMessage = true;

                $http.post($filter('route')('api_v1_post_messages'), {message: message})
                    .success(function (data) {
                        conversation.sendingMessage = false;

                        if (data && data.message !== undefined) {
                            data = data.message;

                            //If is new message
                            if (conversation.id === null) {
                                var openedConversation = null;

                                //Find the opened conversation
                                angular.forEach($scope.chatData, function (chatItem, index) {
                                    //If existed conversion in chatData then only open it
                                    if (data.conversation.id == chatItem.id) {
                                        openedConversation = chatItem;
                                        return
                                    }
                                });
                                $('[data-toggle="tooltip"]').tooltip();

                                conversation.pendingParticipants = [];
                                //$scope.getFriends(conversation);
                                //conversation.friends = angular.copy($scope.friends);

                                //If found the conversation
                                if (openedConversation !== null) {
                                    $scope.pendingMessage = false;
                                    conversation.opened = false;

                                    conversation.minimize = false;
                                    $scope.sortChatTab(openedConversation);
                                    if (openedConversation.minimize === true)
                                        $scope.minimizeCount--;

                                    openedConversation.minimize = false;
                                    openedConversation.opened = true;
                                    openedConversation.focus = true;
                                    openedConversation.messages.push({
                                        "messageId": data.messageId,
                                        "content"  : data.content,
                                        "owner"    : data.owner,
                                        "updated"  : data.updated,
                                        "created"  : data.created
                                    });
                                }
                                //If no found conversation
                                else {
                                    conversation.userId = data.userId;
                                    conversation.id = data.conversation.id;
                                    conversation.updated = data.conversation.updated;
                                    conversation.participants = data.conversation.participants;
                                    conversation.pendingParticipants = null;
                                    conversation.showHeader = false;
                                    conversation.unreadCount = 0;

                                    //conversation.friends = angular.copy($scope.friends);
                                    //$scope.getFriends(conversation);

                                    conversation.title = $filter('transchoice')('chat.title', conversation.participants.length, {'count': conversation.participants.length - 1, 'name': conversation.participants.length > 1 ? conversation.participants[0].firstname : conversation.participants[0].fullname}, 'message');

                                    if (conversation.messages === undefined || !conversation.messages)
                                        conversation.messages = [];

                                    $http.get($filter('route')('api_v1_get_conversation_messages', {conversationId: conversation.id, reverse: 1}))
                                        .success(function (data) {
                                            if (data && data.messages !== undefined) {
                                                conversation.messages = data.messages;
                                            }

                                            conversation.loadedMessage = true;

                                            $scope.updateMessageIsRead(conversation);
                                            $('[data-toggle="tooltip"]').tooltip();
                                        })
                                        .error(function (data) {
                                            conversation.loadedMessage = true;
                                        });

                                    /*
                                     conversation.messages.push({
                                     "messageId": data.id,
                                     "content": data.content,
                                     "owner": data.owner,
                                     "updated": data.updated,
                                     "created": data.created
                                     });
                                     */

                                    $scope.pendingMessage = false;
                                }
                            }
                            //If chat on the opened conversation
                            else {
                                if (conversation.messages === undefined || !conversation.messages)
                                    conversation.messages = [];

                                conversation.messages.push({
                                    "messageId": data.messageId,
                                    "content"  : data.content,
                                    "owner"    : data.owner,
                                    "updated"  : data.updated,
                                    "created"  : data.created
                                });
                            }

                            var popoverMessages = angular.element($('#navbar-ctrl')).scope().messages;

                            //Remove old conversation
                            angular.forEach(popoverMessages, function (message, index) {
                                if (message.conversation.id == data.conversation.id) {
                                    popoverMessages.splice(index, 1);
                                    return;
                                }
                            });

                            var participants = data.conversation.participants;
                            angular.forEach(participants, function (participant, index) {
                                if (participant.id == $scope.connectedUser.id) {
                                    participants.splice(index, 1);
                                    return;
                                }
                            });

                            data.conversation.title = $filter('transchoice')('chat.title', participants.length, {'count': participants.length - 1, 'name': participants.length > 1 ? participants[0].firstname : participants[0].fullname}, 'message');

                            //Push new conversation
                            popoverMessages.unshift({
                                "userId"      : $scope.connectedUser.id,
                                "messageId"   : data.messageId,
                                "content"     : data.content,
                                "updated"     : data.updated,
                                "created"     : data.created,
                                "owner"       : data.owner,
                                "conversation": data.conversation,
                                "opened"      : true
                            });
                        }
                        $('[data-toggle="tooltip"]').tooltip();
                    })
                    .error(function (data) {
                        // console.log("sent message error");
                    });
            }

            /*
             * Send message if press enter key without hold shift key
             */
            if (obj.which === 13 && !obj.shiftKey) {
                obj.target.value = "";
                sendMessage();
            }

            update();
        }

        /**
         * Minimize chat tab
         *
         * @param
         * @returns
         */
        $scope.minimizeTab = function (conversation) {
            $chatBox = $("#chattab-" + (conversation.id === null ? "" : conversation.id));

            var nh = $chatBox.find(".npChatLayoutTitleBar").innerHeight(),
                mt = $chatBox.innerHeight() - nh;

            conversation.minimizedTab = true;
            conversation.focus = false;
            conversation.headerFocus = false;
            conversation.chatboxHeight = $chatBox.innerHeight();

            $chatBox.css({"height": nh, "margin-top": mt})
                .find(".npChatLayoutInner")
                .children("div:not(.npChatLayoutTitleBar)")
                .hide();
        }

        /**
         * Maximize chat tab
         *
         * @param
         * @returns
         */
        $scope.maximizeTab = function (conversation) {
            $chatBox = $("#chattab-" + (conversation.id === null ? "" : conversation.id));

            $chatBox.css({"height": conversation.chatboxHeight, "margin-top": 0})
                .find(".npChatLayoutInner")
                .children("div:not(.npChatLayoutTitleBar)")
                .show();

            conversation.minimizedTab = false;
            conversation.focus = false;
            conversation.headerFocus = false;

            // $scope.focusChat(conversation);
        }

        /**
         * Minimize all chat tabs
         *
         * @param
         * @returns
         */
        $scope.minimizeTabs = function () {
            var chatwidth = 268;
            var windowWidth = $(window).width() - 35;
            var countOpenedChat = $(".npChat").length;
            var maxDisplay = Math.ceil(windowWidth / chatwidth) - 1;

            //Re-open the minimized chat
            if (countOpenedChat < maxDisplay && $scope.minimizeCount > 0) {
                var reopen = maxDisplay - countOpenedChat;

                if (reopen <= 0)
                    return

                for (var i = $scope.chatData.length - 1; i >= 0; i--) {
                    var chatItem = $scope.chatData[i];

                    if (chatItem.minimize === true && reopen > 0) {
                        chatItem.minimize = false;
                        chatItem.opened = true;

                        $scope.maximizeTab(chatItem);

                        $scope.minimizeCount--;
                        reopen--;
                    }
                }
                $('[data-toggle="tooltip"]').tooltip();

                return;
            }

            var hideCount = 0;
            //Minize opened chat
            angular.forEach($scope.chatData, function (chatItem, index) {
                if (chatItem.opened && hideCount < countOpenedChat - maxDisplay) {
                    chatItem.opened = false;

                    if (chatItem.minimize !== true)
                        $scope.minimizeCount++;
                    chatItem.minimize = true;

                    hideCount++;
                }
            });
            $('[data-toggle="tooltip"]').tooltip();
        }

        /**
         * Toggle participant popover
         *
         * @param JSON conversation
         * @returns
         */
        $scope.participantToggle = function (conversation) {
            conversation.participantToggle = (conversation.participantToggle === undefined ? true : !conversation.participantToggle);

            if (conversation.participantToggle) {
                $window.onclick = function (event) {
                    $scope.chatData.forEach(function (e, i) {
                        if (event.target.id != "participantToggle-" + e.id && e.participantToggle)
                            e.participantToggle = false;
                    });

                    $scope.$$phase || $scope.$apply();
                };
            } else {
                $window.onclick = null;
            }
        }

        /**
         * Start chat blink animation interval
         *
         * @param JSON conversation
         * @returns
         */
        $scope.hasIncomingMessages = function (conversation) {
            if (angular.isDefined(conversation.intervalStop))
                return;
            if (angular.isUndefined(conversation.hasIncomingMessage))
                conversation.hasIncomingMessage = false;

            conversation.intervalStop = $interval(function () {
                conversation.hasIncomingMessage = !conversation.hasIncomingMessage;
            }, 700);
        }

        /**
         * Stop chat blink animation interval
         *
         * @param JSON conversation
         * @returns
         */
        $scope.readIncomingMessages = function (conversation) {
            if (angular.isUndefined(conversation.intervalStop))
                return;

            $interval.cancel(conversation.intervalStop);
            conversation.intervalStop = undefined;
            conversation.hasIncomingMessage = false;
        }

        $scope.enterToAddParticipant = function (event, conversation) {
            var text = angular.element(event.target).val().trim();
            if (text == "")
                return;

            if (conversation.searchParticipantResults.length > 0)
                $scope.addParticipant(conversation, conversation.searchParticipantResults[0])
        }
    }]);