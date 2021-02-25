angular.module('app')
    .controller('CategoryTreeController', ['$scope', '$http', '$modal', '$filter', function ($scope, $http, $modal, $filter) {
        $scope.data = [];
        $scope.field = {editing: ""};
        $scope.down = true;
        $scope.down1 = true;

        $scope.toggle = function (scope) {
            scope.toggle();
        };

        $scope.moveLastToTheBeginning = function () {
            var a = $scope.data.pop();
            $scope.data.splice(0, 0, a);
        };

        $scope.newSubItem = function (scope) {
            var nodeData = scope.$modelValue;
            nodeData.nodes.push({
                id   : nodeData.id * 10 + nodeData.nodes.length,
                title: nodeData.title + '.' + (nodeData.nodes.length + 1),
                nodes: []
            });
        };

        var getRootNodesScope = function () {
            return angular.element(document.getElementById("tree-root")).scope();
        };

        $scope.collapseAll = function ($id) {
            if ($id == "#tree-root") {
                $scope.down1 = false;
            } else {
                $scope.down2 = false;
            }
            var scope = getRootNodesScope($id);
            scope.collapseAll();
        };

        $scope.expandAll = function ($id) {
            if ($id == "#tree-root") {
                $scope.down1 = true;
            } else {
                $scope.down2 = true;
            }
            var scope = getRootNodesScope($id);
            scope.expandAll();
        };

        $scope.openDeleteModal = function ($index, entity, $this) {
            var $instance = $modal.open({
                controller : 'ModalCategoryRemoveController',
                templateUrl: 'modal.remove-entity-zone.html',
                resolve    : {
                    entity: function () {
                        return entity;
                    }
                }
            });

            $instance.result.then(function () {
                $this.remove();
            });
        };

        $scope.openCardViewModal = function ($index, entity) {
            var card = $scope.findCardIndex(entity.card);
            var $instance = $modal.open({
                controller : 'ModalCardViewController',
                templateUrl: 'modal.card-view.html',
                resolve    : {
                    entity: function () {
                        return card;
                    }
                }
            });
        };

        $scope.openCardEditModal = function ($index, entity, add) {
            var card;
            if (entity.card) {
                card = $scope.findCardIndex(entity.card);
            }
            var $instance = $modal.open({
                controller : 'ModalCardEditController',
                templateUrl: 'modal.card-edit.html',
                resolve    : {
                    add   : function () {
                        return add;
                    },
                    cards : function () {
                        return $scope.allcards;
                    },
                    card  : function () {
                        return card;
                    },
                    entity: function () {
                        return entity;
                    }
                }
            });
        };

        $scope.recursiveLeaf = function (entity) {
            var aCategory = new Array();
            if (entity.nodes.length == 0) {
                aCategory.push(entity);
            }
            $.each(entity.nodes, function (index, value) {
                aCategory = $.merge(aCategory, $scope.recursiveLeaf(value));
            });
            return aCategory;
        };

        $scope.openCardParentModal = function ($index, entity) {
            var aCategories = $scope.recursiveLeaf(entity);
            var $instance = $modal.open({
                controller : 'ModalCardParentController',
                templateUrl: 'modal.card-parent.html',
                resolve    : {
                    cards     : function () {
                        return $scope.allcards;
                    },
                    categories: function () {
                        return aCategories;
                    },
                    entity    : function () {
                        return entity;
                    }
                }
            });
        };

        $scope.findCardIndex = function (card) {
            var sendCard;
            $.each($scope.allcards, function (index, value) {
                if (parseInt(value.id) == parseInt(card.id)) {
                    sendCard = value;
                }
            });
            return sendCard;
        };

        $scope.init = function (zone) {
            $scope.loadingWaiting = true;
            $http.get($filter('route')('api_admin_get_category_all_zone', {zone: zone}))
                .success(function (response) {
                    $scope.data = response.tree;
                    $scope.loadingWaiting = false;
                })
                .error(function (response) {
                    $scope.loadingWaiting = false;
                });
            $http.get($filter('route')('api_admin_get_animals'))
                .success(function (response) {
                    $scope.allanimals = response.animals;
                });
            $http.get($filter('route')('api_admin_get_cards'))
                .success(function (response) {
                    $scope.allcards = response.cards;
                });
        };

    }]);
