/**
 * Created by vincentvalot on 24/07/14.
 */
angular.module('app').controller('LoungeInvitationController', ['$scope', '$http', '$modal', '$filter', '$controller', function ($scope, $http, $modal, $filter, $controller) {

    $scope.id = angular.element('[ng-controller="LoungeInvitationController"]').data('lounge');

    $scope.params = {
        entity: {
            singular: 'lounge',
            plural: 'lounges'
        },
        routing: {
            list: {
                pending: 'api_v1_get_lounge_pending',
                owning: 'api_v1_get_lounge_owning',
                get: 'api_v1_get_lounges'
            },
            invitation: {
                mail: 'api_v1_post_lounge_invite_mail',
                user: 'api_v1_post_lounge_invite_user',
                group: 'api_v1_post_lounge_invite_group'
            },
            subscribers: {
                get: 'api_v1_get_lounge_subscribers',
                put: 'api_v1_put_lounge_user_join',
                post: 'api_v1_post_lounge_join',
                remove: 'api_v1_delete_lounge_join',
                participation: 'api_v1_put_lounge_subscriber_participation',
                admin: 'api_v1_put_lounge_subscriber_admin'
            },
            home:   'naturapass_lounge_homepage',
            show:   'naturapass_lounge_show',
            remove: 'api_v1_delete_lounge',
            get:    'api_v1_get_lounge'
        }
    };

    $controller('LoungeGroupInvitationController', {$scope: $scope, $http: $http, $modal: $modal, $filter: $filter});
}]);