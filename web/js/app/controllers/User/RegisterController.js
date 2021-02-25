/**
 * Created by vincentvalot on 02/07/14.
 */


angular.module('app')

    .controller('RegisterController', ['$scope', '$http', '$facebook', '$filter', '$sce', '$modal', function ($scope, $http, $facebook, $filter, $sce, $modal) {
        $scope.loading = false;
        $scope.blogs = [];

        $scope.fbRegister = function() {
            $scope.loading = true;

            $facebook.login().then(function(response) {
                if (response.status == 'connected') {
                    document.location = $filter('route')("hwi_oauth_service_redirect", {service: "facebook"});
                } else {
                    //$scope.$apply(function() {
                        $scope.loading = false;
                    //});
                }
            }, function() {
                //$scope.$apply(function() {
                    $scope.loading = false;
                //});
            });
        }

        $scope.openLogin = function () {
            var modalInstance = $modal.open({
                templateUrl: 'modal.login.html',
                controller : 'ModalLoginController'
            });

            modalInstance.result.then(function (params) {
                // console.log(params);
            });
        };

        $scope.initHome = function () {
            $http.get('https://blog-chasse.naturapass.com/wp-json/posts')
                .success(function (data) {
                    for (var i = 0; i < 3; i++) {
                        data[i].title = $sce.trustAsHtml(data[i].title);
                        data[i].excerpt = $sce.trustAsHtml(data[i].excerpt);
                        data[i].date = $filter('date')(data[i].date, "longDate");
                        data[i].date = data[i].date.split("ong");
                        data[i].date = data[i].date[0];
                        $scope.blogs.push(data[i]);
                    }
                    console.log($scope.blogs);
                })
                .error(function () {
                });
        }

        $scope.submit = function() {
            angular.element('form[name="fos_user_registration_form"]').submit();
        }
    }])
    .controller('ModalLoginController', ['$scope', '$http', '$filter', '$modalInstance', function ($scope, $http, $filter, $instance) {
                $scope.error = false;
                $scope.message = '';
                $scope.isLoading = false;

                $instance.opened.then(function () {
                    console.log('Hello world!');
                })

                $scope.ok = function () {
                    $scope.error = false;
                    $scope.isLoading = true;
                    var form;
                    if(document.URL.contains("contact")){
                        form = $('form')[3];
                    }else{
                        form = $('form')[2];
                    }
                    var fd = new FormData(form);
                    $http.post('/api/v2/checks/users/logins', fd, {
                       transformRequest: angular.identity,
                       headers: {'Content-Type': undefined}
                    })
                    .success(function(data){
                        $('form[name="login"]').submit();
                        $scope.isLoading = false;
                    })
                    .error(function(error){
                        $scope.error = true;
                        $scope.message = error.message;
                        $scope.isLoading = false;
                    });
                }

                $scope.cancel = function () {
                    $instance.dismiss('cancel');
                }
            }]);
