angular.module('app')

    .controller('GroupListController', ['$scope', '$http', '$modal', '$filter', '$controller', function ($scope, $http, $modal, $filter, $controller) {
        $scope.params = {
            invited: angular.element('[ng-controller="GroupListController"]').data('invited') == '1' ? true : false,
            owning: angular.element('[ng-controller="GroupListController"]').data('owning') == '1' ? true : false,
            entity: {
                singular: 'group',
                plural: 'groups'
            },
            routing: {
                list: {
                    pending: 'api_v1_get_groups_pending',
                    owning: 'api_v1_get_groups_owning',
                    get: 'api_v1_get_groups'
                },
                invitation: {
                    mail: 'api_v1_post_group_invite_mail',
                    user: 'api_v1_post_group_invite_user',
                    group: 'api_v1_post_group_invite_group'
                },
                subscribers: {
                    get: 'api_v1_get_group_subscribers',
                    put: 'api_v1_put_group_user_join',
                    post: 'api_v1_post_group_join',
                    remove: 'api_v1_delete_group_join',
                    admin: 'api_v1_put_group_subscriber_admin'
                },
                home:   'naturapass_group_homepage',
                show:   'naturapass_group_show',
                remove: 'api_v1_delete_group',
                get:    'api_v1_get_group'
            }
        }

        /**
         * Mets à jour le paramètre permettant de savoir si l'utilisateur souhaite recevoir par email des notifications pour ce groupe
         *
         * @param group
         */
        $scope.updateMailable = function(group) {
            group.connected.loading = true;

            $http.put($filter('route')('api_v1_put_group_subscriber_mailable', {group: group.id, mailable: group.connected.mailable}))
                .success(function(response) {
                    group.connected.loading = false;
                })
                .error(function() {
                    group.connected.mailable = !group.connected.mailable;
                    group.connected.loading = false;
                });
        }

        $controller('LoungeGroupListController', {$scope: $scope, $http: $http, $modal: $modal, $filter: $filter});
    }])