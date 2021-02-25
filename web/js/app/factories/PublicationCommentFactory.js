/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 26/05/14
 * Time: 10:55
 */

angular.module('app').factory('factory:PublicationComment', ['$http', '$filter', function($http, $filter) {
    return {
        get: function (publication_id, data) {
            return $http.get($filter('route')('api_v2_get_publication_comments', {publication: publication_id}), {params: data});
        },
        getLikes: function (comment_id) {
            return $http.get($filter('route')('api_v2_get_publication_comments_likes', {comment: comment_id}));
        },
        persist: function (publication_id, data) {
            return $http.post($filter('route')('api_v2_post_publication_comment', {publication: publication_id}), {comment: {content: data}});
        },
        remove: function (comment_id) {
            return $http._delete($filter('route')('api_v1_delete_publication_comment', {comment: comment_id}));
        },
        update: function (comment_id, data) {
            return $http.put($filter('route')('api_v2_put_publication_comment', {comment: comment_id}), {comment: {content: data}});
        },
        removeLike: function(comment_id) {
            return $http._delete($filter('route')('api_v1_delete_publication_comment_action', {comment: comment_id}));
        },
        persistLike: function(comment_id) {
            return $http.post($filter('route')('api_v2_post_publication_comment_like', {comment: comment_id}));
        }
    };
}]);