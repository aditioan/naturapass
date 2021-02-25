angular.module('infinite-scroll').value('THROTTLE_MILLISECONDS', 250);

var app = angular.module('app', [
    'naturapass.directives',
    'ngRoute',
    'ipCookie',
    'blueimp.fileupload',
    'infinite-scroll',
    'ui.bootstrap',
])
    .config(['$routeProvider', '$locationProvider', function ($routeProvider, $locationProvider) {
        $locationProvider.html5Mode(false);
    }])

    .config(['$httpProvider', function ($httpProvider) {
        $httpProvider.defaults.useXDomain = true;
        delete $httpProvider.defaults.headers.common['X-Requested-With'];

        $httpProvider.responseInterceptors.push(['$q', function ($q) {
            return function (promise) {
                return promise.then(function (response) {
                    return response;
                }, function (response) {
                    switch (response.status) {
                        case 503:
                            window.location.href = Routing.generate('naturapass_main_homepage');
                            break;
                        case 401:
                            window.location.href = Routing.generate('naturapass_main_homepage');
                            break;
                    }

                    return $q.reject(response);

                });

            }

        }]);

    }]);

app.run(['$rootScope', '$location', '$http', function ($rootScope, $location, $http) {
    moment.locale(Translator.locale);
    videojs.options.flash.swf = "/swf/video-js.swf";

    $http._delete = $http['delete'];

    (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id))
            return;
        js = d.createElement(s);
        js.id = id;
        js.src = "//connect.facebook.net/fr_FR/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
}]);

app.directive('placesAutocomplete', ['$rootScope', function ($rootScope) {
    return {
        restrict: 'AEC',
        require: ['placesAutocomplete', '?ngModel'],
        controller: ['$scope', '$element', '$attrs', '$transclude', function ($scope, $element, $attrs, $transclude) {

            this._element = $element[0];
            this._api = undefined;

            // Define properties
            Object.defineProperties(this, {
                element: {
                    get: function () {
                        return this._element;
                    },
                    configurable: false
                },
                api: {
                    get: function () {
                        return this._api;
                    },
                    configurable: false
                }
            });

            try {
                this._api = new google.maps.places.Autocomplete(this._element, {types: ['geocode'], componentRestrictions: {country: "fr"}});
            }
            catch (err) {
                console.log('Could not instantiate placesAutocomplete directive: ' + err.message);
            }

        }],
        link: function (scope, iElement, iAttrs, controllers) {
            var gmPlacesAutocompleteController = controllers[0];
            var ngModelController = controllers[1];

            if (ngModelController) {
                ngModelController.$setViewValue(gmPlacesAutocompleteController.api);
            }

            google.maps.event.addListener(gmPlacesAutocompleteController.api, 'place_changed',
                (function (scope, iElement, iAttrs, gmPlacesAutocompleteController, ngModelController, $rootScope) {
                    return function () {
                        if (ngModelController) {
                            ngModelController.$setViewValue(gmPlacesAutocompleteController.api);
                        }

                        // Broadcast event
                        $rootScope.$broadcast('PlacesAutocomplete::placeChanged', gmPlacesAutocompleteController);
                    };
                })(scope, iElement, iAttrs, gmPlacesAutocompleteController, ngModelController, $rootScope)
            );

        }
    };

}]);

app.controller('RevolierController', ['$scope', '$http', '$timeout', '$filter','$modal', function ($scope, $http, $timeout, $filter, $modal) {



        $scope.idSub = angular.element('[ng-controller="RevolierController"]').data('id');

        $scope.input = null;
        $scope.result = [];
        $scope.loading = false;
        $scope.resultEmpty= false;
        $scope.form = {
            firstName: '',
            lastName: '',
            email: '',
            phoneNumber: '',
            birthDate: '',
            event: $scope.idSub,
            zipCode: '',
        };
        $scope.acceptCgu = true;

        $scope.message = {
            success: false
        }

        $scope.$on('PlacesAutocomplete::placeChanged', function () {
            if ($scope.input) {
                // var place = $scope.input.getPlace();
                // console.log('formatted_address', place.formatted_address); // Paris, France
                // console.log('address_components', JSON.stringify(place.address_components));
                $scope.loadArmony();
                $scope.$apply();
            }
        });

        $scope.openCgu = function () {
            var modalInstance = $modal.open({
                templateUrl: 'modal.cgu.html',
                controller : 'ModalCguController'
            });

        };

        $scope.loadArmony = function () {
            $scope.result = [];
            localStorage.removeItem('getCity');
            $scope.loading = true;
            try {
                var place = $scope.input.getPlace();
                var addressComponents = place.address_components;
                var cityName = addressComponents[0].long_name;
                localStorage.setItem('getCity', cityName);

                $http.get('/api/v2/distributors/events/nearest?city=' + cityName)
                    .success(function (data) {
                        $scope.loading = false;
                        $scope.result = data.DistributorEvents.map(function (item) {
                            item.isSameDay = moment(item.startDate).isSame(item.endDate, 'day');
                            return item;
                        });

                        if(data.DistributorEvents.length == 0) {
                            $scope.resultEmpty = true;
                        } else {
                            $scope.resultEmpty = false;
                        }

                    })
                    .error(function (err) {
                        $scope.loading = false;

                    });
            } catch (e) {
                console.log(e);
                $scope.loading = false;
            }
        };

        $scope.loadSIngleArmony = function () {
            $scope.city = localStorage.getItem('getCity');
            $http.get('/api/v2/distributor/event?city=' +  $scope.city  + '&id=' + $scope.idSub)
                .success(function (data) {
                    $scope.loading = false;
                    $scope.data = data;
                    $scope.data.isSameDay = moment(data.startDate).isSame(data.endDate, 'day');
                })
                .error(function (err) {
                    $scope.loading = false;

                });
        };

        $scope.onSubmit = function () {
            $scope.loading = true;
            if ($scope.form.firstName == 'null' || $scope.form.firstName == null ||
                $scope.form.lastName == '' || $scope.form.lastName == null ||
                $scope.form.email == '' || $scope.form.email == null ||
                $scope.form.phoneNumber == '' || $scope.form.phoneNumber == null ||
                $scope.form.birthDate == '' || $scope.form.birthDate == null ||
                $scope.form.zipCode == '' || $scope.form.zipCode == null
            ) {
                alert('Vous devez remplir tous les champs obligatoires');
                $scope.loading = false;
                return;
            }

            if (!/^\d+$/.test($scope.form.zipCode)) {
                alert('Code postal invalide');
                $scope.loading = false;
                return;
            }

            if(!$scope.acceptCgu) {
                alert('Vous devez accepter la politique en sélectionnant la case à cocher');
                $scope.loading = false;
                return;
            }


            if($scope.acceptCgu) {
                var date =$scope.form.birthDate.split('/');
                $scope.form.birthDate = date[2]+'-'+date[1]+'-'+date[0];
                $http.post('/api/v2/distributors/events/subscribers', {DistributorEventSubscriber: $scope.form})
                    .success(function (data) {
                        $scope.loading = false;
                        $scope.message.success = true;
                        $scope.form = {
                            firstName: '',
                            lastName: '',
                            email: '',
                            phoneNumber: '',
                            birthDate: '',
                            event: $scope.idSub,
                            zipCode: '',
                        };
                        $timeout(function() {
                            $scope.message.success = false;
                        }, 5000);
                    })
                    .error(function (err) {
                        alert('Email déjà enregistré');
                        $scope.loading = false;

                    });
            }
        };
    $scope.onSubmitAnyPlace = function () {
        $scope.loading = true;

        if ($scope.form.firstName == 'null' || $scope.form.firstName == null ||
            $scope.form.lastName == '' || $scope.form.lastName == null ||
            $scope.form.email == '' || $scope.form.email == null ||
            $scope.form.phoneNumber == '' || $scope.form.phoneNumber == null ||
            $scope.form.birthDate == '' || $scope.form.birthDate == null ||
            $scope.form.zipCode == '' || $scope.form.zipCode == null
        ) {
            alert('Vous devez remplir tous les champs obligatoires');
            $scope.loading = false;
            return;
        }

        if (!/^\d+$/.test($scope.form.zipCode)) {
            alert('Code postal invalide');
            $scope.loading = false;
            return;
        }

        if(!$scope.acceptCgu) {
            alert('Vous devez accepter la politique en sélectionnant la case à cocher');
            $scope.loading = false;
            return;
        }

        if($scope.acceptCgu) {
            var save_date = $scope.form.birthDate;
            var date = $scope.form.birthDate.split('/');
            $scope.form.birthDate = date[2]+'-'+date[1]+'-'+date[0];
            $scope.form.event=10;
            $http.post('/api/v2/distributors/events/subscribers', {DistributorEventSubscriber: $scope.form})
                .success(function (data) {
                    $scope.loading = false;
                    $scope.message.success = true;
                    $scope.form = {
                        firstName: '',
                        lastName: '',
                        email: '',
                        phoneNumber: '',
                        birthDate: '',
                        event: '10',
                        zipCode: '',
                    };
                    $timeout(function() {
                        $scope.message.success = false;
                    }, 8000);
                })
                .error(function (error) {
                    $scope.form.birthDate = save_date;
                    alert('Email déjà enregistré');
                    $scope.loading = false;
                });
        }
    };
    }])
    .controller('ModalCguController', ['$scope', '$http', '$filter', '$modalInstance', function ($scope, $http, $filter, $instance) {

        $scope.ok = function () {
            $instance.close();
        };
    }]);