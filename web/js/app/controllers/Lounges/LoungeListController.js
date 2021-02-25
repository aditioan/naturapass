angular.module('app')

    .controller('LoungeListController', ['$scope', '$http', '$modal', '$filter', '$controller', function ($scope, $http, $modal, $filter, $controller) {
        $scope.params = {
            invited: angular.element('[ng-controller="LoungeListController"]').data('invited') == '1' ? true : false,
            owning: angular.element('[ng-controller="LoungeListController"]').data('owning') == '1' ? true : false,
            entity: {
                singular: 'lounge',
                plural: 'lounges'
            },
            routing: {
                list: {
                    pending: 'api_v1_get_lounges_pending',
                    owning: 'api_v1_get_lounges_owning',
                    get: 'api_v1_get_lounges'
                },
                invitation: {
                    mail: '',
                    user: '',
                    group: ''
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
        }

        $controller('LoungeGroupListController', {$scope: $scope, $http: $http, $modal: $modal, $filter: $filter});
    }]);