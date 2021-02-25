/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 26/05/14
 * Time: 10:55
 */

angular.module('app').factory('factory:User', ['$http', '$filter', function ($http, $filter) {

    return {
        lock: function (user_id) {
            return $http.post($filter('route')('api_v2_post_user_lock'), {id: user_id})
        },
    };
}]);