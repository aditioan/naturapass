
angular.module('app')
    .controller('SlideListController', ['$scope', '$http', '$filter', '$timeout', function ($scope, $http, $filter, $timeout) {

        $scope.slides = [];

        $scope.loadSlides = function() {
            $http.get($filter('route')('api_admin_get_slides', {limit: 15, offset: 0}))
                .success(function(response) {
                    angular.forEach(response.slides, function(element) {
                        element.initActive = element.active;
                        $scope.slides.push(element);
                    });
                })
                .error(function(response) {

                });
        }

        $scope.activeSlide = function(slide) {
            slide.loading = true;

            $http.put($filter('route')('api_admin_put_slide_active', {slide: slide.id, active: slide.active ? 1 : 0}))
                .success(function(response) {
                    slide.loading = false;
                })
                .error(function(response) {
                    slide.loading = false;
                })
        }

        $scope.deleteSlide = function(index, slide) {
            slide.loading = true;

            $http._delete($filter('route')('api_admin_delete_slide', {slide: slide.id}))
                .success(function() {
                    $scope.slides.splice(index, 1);
                })
                .error(function() {
                    slide.loading = false;
                })
        }

        $scope.updateSlideSort = function(slide) {
            slide.loading = true;

            $http.put($filter('route')('api_admin_put_slide_sort', {slide: slide.id, sort: slide.sort}))
                .success(function() {
                    slide.loading = false;
                })
                .error(function() {
                    slide.loading = false;
                })
        }
    }])