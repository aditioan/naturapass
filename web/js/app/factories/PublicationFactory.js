/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 26/05/14
 * Time: 10:55
 */

angular.module('app').factory('factory:Publication', ['$http', '$filter', function ($http, $filter) {
        var model = {
            geolocation: {
                address: "",
                latitude: "",
                longitude: "",
                altitude: ""
            },
            sharing: {
                share: "",
                withouts: []
            },
            media: {
                legend: "",
                tags: []
            },
            legend: "",
            date: "",
            content: "",
            created:"",
            publicationcolor: "",
            groups: [],
            landmark: false
        }

        function sanitize(data, nomedia) {
            var sanitized = $filter('sanitizeArray')(data, model);

            if (data.sending)
                sanitized.content = data.sending;

            if (data.sendingDate)
                sanitized.date = data.sendingDate;

            if (nomedia)
                delete sanitized.media;

            if (typeof data.groups == 'array') {
                sanitized.groups = [];

                for (var i = 0; i < data.groups.length; i++) {
                    if (typeof data.groups[i] == 'object' && data.groups[i].id) {
                        sanitized.groups.push(data.groups[i].id)
                    } else {
                        sanitized.groups.push(data.groups[i])
                    }
                }
            }

            return sanitized;
        }

        return {
            getModel: function () {
                return jQuery.extend(true, {}, model);
            },
            all: function (data) {
                return $http.get($filter('route')('api_v2_get_publications'), {params: data});
            },
            get: function (publication_id) {
                return $http.get($filter('route')('api_v2_get_publication', {publication: publication_id}));
            },
            persist: function (data, nomedia) {
                return $http.post($filter('route')('api_v2_post_publication'), {publication: sanitize(data, nomedia)});
            },
            remove: function (publication_id) {
                return $http._delete($filter('route')('api_v1_delete_publication', {publication: publication_id}));
            },
            update: function (data) {
                return $http.put($filter('route')('api_v1_put_publication', {publication: data.id}), {publication: sanitize(data)});
            },
            updateMedia: function (data, nomedia) {
                return $http.post($filter('route')('api_v2_post_publication_media', {publication: data.id}), {publication: sanitize(data, nomedia)});
            },
            rotate: function (publication_id, degree) {
                return $http.put($filter('route')('api_v1_put_publication_rotate', {publication: publication_id, degree: degree}));
            },
            crop: function (publication_id, coords) {
                return $http.put($filter('route')('api_v1_put_publication_crop', {publication: publication_id}), {coords: coords});
            },
            ofUser: function (user_id, filters) {
                return $http.get($filter('route')('api_v2_get_publications_user', {user: user_id}), {params: filters});
            },
            getLikes: function (id) {
                return $http.get($filter('route')('api_v2_get_publication_likes', {publication: id}));
            },
            removeLike: function (publication_id) {
                return $http._delete($filter('route')('api_v1_delete_publication_action', {publication: publication_id}));
            },
            persistLike: function (publication_id) {
                return $http.post($filter('route')('api_v2_post_publication_like', {publication: publication_id}));
            }
        };
    }]);