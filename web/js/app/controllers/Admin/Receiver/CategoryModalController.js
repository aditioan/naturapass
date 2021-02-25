angular.module('app')

    .controller('ModalCategoryNewController', ['$scope', '$http', '$filter', '$modalInstance', 'add', 'cards', 'entity', function ($scope, $http, $filter, $instance, add, cards, entity) {
        $scope.param = {add: add};
        $scope.allcards = cards;
        $scope.data = {
            entity : (add == 1) ? {id: "new", title: "", nodes: [], visible: entity.visible, search: 0} : entity,
            loading: false
        };

        $scope.ok = function () {
            $scope.data.loading = true;
            if (add == 1) {
                entity.nodes.push($scope.data.entity);
            } else {
                entity.name = $scope.data.entity.name;
            }
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
    .controller('ModalCategoryNew2Controller', ['$scope', '$http', '$filter', '$modalInstance', 'entity', function ($scope, $http, $filter, $instance, entity) {

        $scope.data = {
            entity : {id: "new", title: "", nodes: [], visible: true, search: 0},
            loading: false
        };

        $scope.ok = function () {
            $scope.data.loading = true;
            entity.push($scope.data.entity);
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
    }])
    .controller('ModalGroupController', ['$scope', '$http', '$filter', '$modalInstance', 'selected', 'entity', function ($scope, $http, $filter, $instance, selected, entity) {
        $scope.groupsSelect = selected;
        $scope.data = {
            entity : entity,
            loading: false,
            json   : {
                groups: $filter('json')(selected)
            }
        };

        $scope.groupsOptions = {
            allowClear        : true,
            minimumInputLength: 3,
            multiple          : true,
            ajax              : {
                url     : $filter('route')('api_admin_get_groups'),
                dataType: 'json',
                data    : function (term, page) {
                    return {
                        filter: term,
                        limit : 10, // page size
                        page  : page, // page number
                        select: true
                    };
                },
                results : function (data, page) {
                    var more = (page * 10) < data.total;
                    return {results: data.groups};
                }
            },
            initSelection     : function (element, callback) {
                callback(selected);
            }
        }

        $scope.setGroup = function (node, merge) {
            if (merge) {
                var array = $.merge(node.groups, $('.modal-groups-select2').select2('data')),
                    fieldArray = [],
                    fieldArrayId = [];
                $.each(array, function (i, item) {
                    if ($.inArray(item.id, fieldArrayId) === -1) {
                        fieldArray.push(item);
                        fieldArrayId.push(item.id);
                    }
                });
                node.groups = fieldArray;
            } else {
                node.groups = $('.modal-groups-select2').select2('data');
            }
            $.each(node.nodes, function (index, childNode) {
                $scope.setGroup(childNode, true);
            });
        };

        $.extend({
            distinct: function (anArray) {
                var result = [];
                $.each(anArray, function (i, v) {
                    if ($.inArray(v, result) == -1)
                        result.push(v);
                });
                return result;
            }
        });

        $scope.ok = function () {
            $scope.setGroup(entity, false);
            $instance.close();
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };
    }]);