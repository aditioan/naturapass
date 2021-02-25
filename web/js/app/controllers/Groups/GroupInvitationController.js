angular.module('app').controller('GroupInvitationController', ['$scope', '$http', '$modal', '$filter', '$controller', function ($scope, $http, $modal, $filter, $controller) {

    $scope.id = angular.element('[ng-controller="GroupInvitationController"]').data('group');

    $scope.params = {
        entity: {
            singular: 'group',
            plural: 'groups'
        },
        routing: {
            list: {
                pending: 'api_v1_get_group_pending',
                owning: 'api_v1_get_group_owning',
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
    };

    $controller('LoungeGroupInvitationController', {$scope: $scope, $http: $http, $modal: $modal, $filter: $filter});
}]);