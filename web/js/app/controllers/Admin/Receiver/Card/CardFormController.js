angular.module('app').controller('CardFormController', ['$scope', '$controller', '$http', '$timeout', '$modal', '$q', '$filter', '$maputils', function ($scope, $controller, $http, $timeout, $modal, $q, $filter, $maputils) {

    $scope.data = {entity: {'name': "", labels: []}};
    $scope.loading = true;
    $scope.loadingLabels = true;
    $scope.alllabels = [
        {
            'value'      : 0,
            'displayName': 'Champ libre'
        },
        {
            'value'      : 1,
            'displayName': 'Champ texte'
        },
        {
            'value'      : 10,
            'displayName': 'Entier'
        },
        {
            'value'      : 11,
            'displayName': 'Décimal'
        },
        {
            'value'      : 20,
            'displayName': 'Champ date'
        },
        {
            'value'      : 21,
            'displayName': 'Champ heure'
        },
        {
            'value'      : 22,
            'displayName': 'Champ date + heure'
        },
        {
            'value'      : 30,
            'displayName': 'Liste déroulante'
        },
        {
            'value'      : 31,
            'displayName': 'Liste déroulante multiple'
        },
        {
            'value'      : 32,
            'displayName': 'Liste multiple (tag)'
        },
        {
            'value'      : 40,
            'displayName': 'Checkbox'
        },
        {
            'value'      : 50,
            'displayName': 'Radio'
        }
    ];

    $scope.allowContentType = function () {
        return [50, 40, 30, 31, 32];
    };

    $scope.checkAllowableType = function (type, label) {
        if ($scope.allowContentType().indexOf(type) != -1) {
            label.allowContent = true;
        } else {
            label.allowContent = false;
        }
    };

    $scope.checkTypeSelect = function ($index, entity, $this) {
        $scope.checkAllowableType(parseInt(entity.type), entity);
    };

    $scope.init = function () {
        $scope.loadingOpen = true;
        if ($("#idCardCategory").attr('data-id') != "") {
            $http.get($filter('route')('api_admin_get_card', {card: $("#idCardCategory").attr('data-id')}))
                .success(function (response) {
                    $scope.data.entity = response.card;
                    $.each($scope.data.entity.labels, function (index, label) {
                        $scope.checkAllowableType(label.type, label);
                    });
                    $scope.loading = false;
                    $scope.loadingLabels = false;
                })
                .error(function (response) {
                    $scope.loading = false;
                    $scope.loadingLabels = false;
                });
        } else {
            $scope.loadingLabels = false;
        }
    };

    $scope.addNewLabel = function () {
        $scope.data.entity.labels.push({id: 'new', name: "", type: null, required: 0});
    };

    $scope.openContentLabel = function ($index, entity, $this) {
        var $instance = $modal.open({
            controller : 'ModalCardLabelContentController',
            templateUrl: 'modal.card-label-content.html',
            resolve    : {
                entity: function () {
                    return entity;
                }
            }
        });

        $instance.result.then(function () {
            var aContent = [];
            $.each(entity.contents, function (index, content) {
                if (content.id != "" || content.name != "") {
                    aContent.push(content);
                }
            });
            delete entity.contents;
            entity.contents = [];
            $.each(aContent, function (index, content) {
                entity.contents.push(content);
            });
        });
    };

    $scope.openDeleteModal = function ($index, entity, $this) {
        var $instance = $modal.open({
            controller : 'ModalCardLabelRemoveController',
            templateUrl: 'modal.remove-card-label.html',
            resolve    : {
                entity: function () {
                    return entity;
                }
            }
        });

        $instance.result.then(function () {
            var index = $scope.data.entity.labels.indexOf(entity);
            if (index > -1) {
                $scope.data.entity.labels.splice(index, 1);
            }
            window.console.log($scope.data.entity);
        });
    };

    $scope.submit = function () {
        $('form[name="card"]').submit();
    };

}
])
;