/**
 * Created by vincentvalot on 15/07/14.
 */

angular.module('app').controller('GroupFormController', ['$scope', '$http', '$modal', '$filter', function ($scope, $http, $modal, $filter) {
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
                home: 'naturapass_group_homepage',
                show: 'naturapass_group_show',
                remove: 'api_v1_delete_group',
                get: 'api_v1_get_group'
            }
        };


        $scope.openObservation = function (data) {
            var modalInstance = $modal.open({
                templateUrl: 'modal.observation.html',
                controller: 'ModalObservationController',
                resolve: {
                    publication: function () {
                        return data;
                    }
                }
            });

            modalInstance.result.then(function (params) {
                if (params != null) {
                    $scope.$emit('npevent-publication/update', params.publication);
                }
//                $scope.savedGroups = params.groups;
//                $scope.savedWithouts = params.withouts;
//                $scope.publication.groups = [];
//                $scope.publication.sharing.withouts = [];
//                $scope.publication.social = params.social;
//                $scope.changeSharing(params.current)
//
//                $.each(params.groups, function (index, element) {
//                    $scope.publication.groups.push(element.id);
//                });
//                $.each(params.withouts, function (index, element) {
//                    $scope.publication.sharing.withouts.push(element.id);
//                });
            });
        };


        $scope.openDeleteGroupModal = function (id, name) {
            var $instance = $modal.open({
                controller: 'ModalLoungeGroupRemoveController',
                templateUrl: 'modal.remove-entity.html',
                resolve: {
                    params: function () {
                        return $scope.params;
                    },
                    entity: function () {
                        return {id: id, name: name};
                    }
                }
            });

            $instance.result.then(function () {
                document.location.href = $filter('route')($scope.params.routing.home);
            });
        }

        $scope.submit = function () {
            $('form[name="group"]').submit();
        }

        $scope.filterSubscribers = function (subscriber) {
            return subscriber.access == 2 || subscriber.access == 3;
        };
    }]);