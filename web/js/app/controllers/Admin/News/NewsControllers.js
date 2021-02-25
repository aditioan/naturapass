
angular.module('app')
        .controller('NewsFormController', ['$scope', '$http', '$filter', '$timeout', function ($scope, $http, $filter) {

                $scope.news = {
                    active: false
                };

                $scope.persistNews = function () {
                    $scope.news.loading = true;

                    delete $scope.news.loading;

                    $http.post($filter('route')('api_admin_post_news'), {news: $scope.news})
                            .success(function (response) {
                                $scope.news.loading = false;
                                $scope.news = {
                                    active: false
                                };

                                $scope.$parent.$broadcast('npevent-news/add', response.news);
                            })
                            .error(function (response) {
                                $scope.news.loading = false;
                            });
                };
                $scope.submit = function () {
                    $('form[name="news"]').submit();
                };

            }])
        .controller('NewsListController', ['$scope', '$http', '$filter', '$timeout', function ($scope, $http, $filter, $timeout) {

                $scope.news = [];

                $scope.$on('npevent-news/add', function ($event, news) {
                    news.initActive = news.active;
                    $scope.news.unshift(news);
                });

                $scope.loadNews = function () {
                    $http.get($filter('route')('api_admin_get_news', {limit: 15, offset: 0}))
                            .success(function (response) {
                                angular.forEach(response.news, function (element) {
                                    element.initActive = element.active;
                                    $scope.news.push(element);
                                });
                            })
                            .error(function (response) {

                            });
                };

                $scope.activeNews = function (news) {
                    news.loading = true;

                    $http.put($filter('route')('api_admin_put_news_active', {news: news.id, active: news.active ? 1 : 0}))
                            .success(function (response) {
                                news.loading = false;
                            })
                            .error(function (response) {
                                news.loading = false;
                            });
                };

                $scope.deleteNews = function (index, news) {
                    news.loading = true;

                    $http._delete($filter('route')('api_admin_delete_news', {news: news.id}))
                            .success(function (response) {
                                $scope.news.splice(index, 1);
                            })
                            .error(function (response) {
                                news.loading = false;
                            });
                };
            }]);