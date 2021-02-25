angular.module('app')

    .controller('ModalCategoryNewController', ['$scope', '$http', '$filter', '$modalInstance', 'add', 'animals', 'cards', 'entity', function ($scope, $http, $filter, $instance, add, animals, cards, entity) {
        $scope.param = {add: add};
        $scope.allanimals = animals;
        $scope.allcards = cards;
        $scope.selected = "normal";
        $scope.select = {animals: [], card: []};

        $scope.data = {
            entity : (add == 1) ? {id: "new", title: "", nodes: []} : entity,
            loading: false
        };

        $scope.changeType = function (type) {
            $scope.selected = type;
        };

        $scope.ok = function () {
            $scope.data.loading = true;
            if ($scope.selected == "normal") {
                if (add == 1) {
                    entity.nodes.push($scope.data.entity);
                } else {
                    entity.name = $scope.data.entity.name;
                }
            } else {
                $.each($scope.select.animals, function (index, value) {
                    var addEntity = {id: "new", title: value.name, nodes: []};
                    if ($scope.select.card) {
                        addEntity.card = jQuery.extend({}, $scope.select.card);
                        delete addEntity.card.labels;
                    }
                    entity.nodes.push(addEntity);
                });
            }
            $instance.close();

        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };
    }])
    .controller('ModalCategoryRemoveController', ['$scope', '$http', '$filter', '$modalInstance', 'entity', function ($scope, $http, $filter, $instance, entity) {
        $scope.data = {
            entity : entity,
            loading: false
        };

        $scope.ok = function () {
            $instance.close();
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };
    }])

    .controller('ModalCardParentController', ['$scope', '$http', '$filter', '$modalInstance', 'cards', 'categories', 'entity', function ($scope, $http, $filter, $instance, cards, categories, entity) {
        $scope.allcards = cards;
        $scope.allcategory = categories;
        $scope.select = {category: categories};
        $scope.data = {
            entity : entity,
            loading: false
        };

        $scope.ok = function () {

            $.each($scope.allcategory, function (index, value) {
                delete value.card;
            });
            $.each($scope.select.category, function (index, value) {
                if ($scope.select.card) {
                    value.card = jQuery.extend({}, $scope.select.card);
                    delete value.card.labels;
                }
            });

            $instance.close();
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };
    }])
    .controller('ModalCardViewController', ['$scope', '$http', '$filter', '$modalInstance', 'entity', function ($scope, $http, $filter, $instance, entity) {
        $scope.card = entity;
        $scope.convertType = function (type) {
            var sType = '';
            switch (type) {
                case 0:
                    sType = 'Champs libre';
                    break;
                case 1:
                    sType = 'Champs texte';
                    break;
                case 10:
                    sType = 'Entier';
                    break;
                case 11:
                    sType = 'Décimal';
                    break;
                case 20:
                    sType = 'Champs date';
                    break;
                case 21:
                    sType = 'Champ heure';
                    break;
                case 22:
                    sType = 'Champ date + heure';
                    break;
                case 30:
                    sType = 'Liste déroulante';
                    break;
                case 31:
                    sType = 'Liste déroulante multiple';
                    break;
                case 32:
                    sType = 'Liste multiple (tag)';
                    break;
                case 40:
                    sType = 'Checkbox';
                    break;
                case 50:
                    sType = 'Radio';
                    break;
            }

            return sType;
        };

        $scope.ok = function () {
            $instance.close();
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };
    }])
    .controller('ModalCardEditController', ['$scope', '$http', '$filter', '$modalInstance', 'add', 'cards', 'card', 'entity', function ($scope, $http, $filter, $instance, add, cards, card, entity) {
        $scope.param = {add: add};
        $scope.allcards = cards;
        $scope.select = {card: card};
        $scope.data = {
            entity : entity,
            loading: false
        };

        $scope.ok = function () {
            if (add != 1) {
                delete entity.card;
            }
            if ($scope.select.card) {
                entity.card = jQuery.extend({}, $scope.select.card);
                delete entity.card.labels;
            }
            $instance.close();
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };
    }]);