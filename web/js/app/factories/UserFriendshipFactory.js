/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 26/05/14
 * Time: 10:55
 */

angular.module('app').factory('factory:UserFriendship', ['$http', '$filter', function ($http, $filter) {

        return {
            ask: function (receiver_id) {
                return $http.post($filter('route')('api_v2_post_user_friendship', {receiver: receiver_id}))
            },
            confirm: function (sender_id) {
                return $http.put($filter('route')('api_v2_put_user_friendship', {sender: sender_id}))
            },
            remove: function (receiver_id) {
                return $http._delete($filter('route')('api_v2_delete_user_friendship', {receiver: receiver_id}))
            }
        };
    }]);