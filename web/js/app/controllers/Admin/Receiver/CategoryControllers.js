angular.module('app')
    .controller('CategoryTreeController', ['$scope', '$http', '$modal', '$filter', function ($scope, $http, $modal, $filter) {
        $scope.data = [];
        $scope.toRemove = [];
        $scope.allanimals = [];
        $scope.field = {editing: ""};
        $scope.down1 = true;
        $scope.down2 = true;
        $scope.tmpAllAnimals = [];

        /**
         * Gère la recherche d'un animal
         *
         * @param $event
         */
        $scope.persistSearchList = function ($event) {
            if ($event.keyCode === 13) {
                $scope.loadEntities();
            }
        };

        $scope.loadEntities = function () {
            var filterList = '';
            if (typeof $scope.input.filterList !== "undefined") {
                filterList = $scope.input.filterList;
            }
            if (filterList == '') {
                $scope.allanimals = $scope.tmpAllAnimals;
            } else {
                $http.get($filter('route')('api_admin_get_animals', {limit: 10000, offset: 0, filter: filterList}))
                    .success(function (response) {
                        $scope.allanimals = response.animals;
                    })
                    .error(function () {
                    });
            }
        };

        $scope.remove = function (scope) {
            scope.remove();
        };

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

        var getRootNodesScope = function ($id) {
            return angular.element($id).scope();
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

        $scope.deleteNode = function (entity) {
            entity.visible = 0;
            if (entity.nodes.length > 0) {
                $.each(entity.nodes, function (index, value) {
                    $scope.deleteNode(value);
                });
            }
        };

        $scope.deleteReelNode = function (entity) {
            entity.remove = 1;
            if (entity.id) {
                $scope.toRemove.push({"id": angular.copy(entity.id), "remove": 1});
            }
            if (entity.nodes.length > 0) {
                $.each(entity.nodes, function (index, value) {
                    $scope.deleteReelNode(value);
                });
            }
        };

        $scope.restoreNode = function (entity) {
            entity.visible = 1;
            if (entity.nodes.length > 0) {
                $.each(entity.nodes, function (index, value) {
                    $scope.restoreNode(value);
                });
            }
        };

        $scope.openCardParentModal = function ($index, entity) {
            var aCategories = $scope.recursiveLeaf(entity);
            var $instance = $modal.open({
                controller : 'ModalCardParentController',
                templateUrl: 'modal.card-parent.html',
                size       : "lg",
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

        $scope.openNewCategory = function () {
            var $instance = $modal.open({
                controller : 'ModalCategoryNew2Controller',
                templateUrl: 'modal.add-category.html',
                size       : "lg",
                resolve    : {
                    entity: function () {
                        return $scope.data;
                    }
                }
            });
        };
        $scope.openNewModal = function ($index, entity, add) {
            var $instance = $modal.open({
                controller : 'ModalCategoryNewController',
                templateUrl: 'modal.add-entity.html',
                size       : "lg",
                resolve    : {
                    add   : function () {
                        return add;
                    },
                    cards : function () {
                        return $scope.allcards;
                    },
                    entity: function () {
                        return entity;
                    }
                }
            });
        };

        $scope.openDeleteModal = function ($index, entity, $this) {
            var $instance = $modal.open({
                controller : 'ModalCategoryRemoveController',
                templateUrl: 'modal.remove-entity.html',
                size       : "lg",
                resolve    : {
                    entity: function () {
                        return entity;
                    }
                }
            });

            $instance.result.then(function () {
                $scope.deleteReelNode(entity);
                $this.remove();
            });
        };

        $scope.openCardViewModal = function ($index, entity) {
            var card = $scope.findCardIndex(entity.card);
            var $instance = $modal.open({
                controller : 'ModalCardViewController',
                templateUrl: 'modal.card-view.html',
                size       : "lg",
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
                size       : "lg",
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

        $scope.openGroupViewModal = function ($index, entity, add) {
            var $instance = $modal.open({
                controller : 'ModalGroupController',
                templateUrl: 'modal.groups-edit.html',
                size       : "lg",
                resolve    : {
                    selected: function () {
                        return entity.groups;
                    },
                    entity  : function () {
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

        $scope.init = function () {
            $scope.loadingWaiting = true;
            $http.get($filter('route')('api_admin_get_category_all'))
                .success(function (response) {
                    $scope.data = response.tree;
                    $scope.loadingWaiting = false;
                })
                .error(function (response) {
                    $scope.loadingWaiting = false;
                });
//                    $http.get($filter('route')('api_admin_get_animal_all_tree'))
//                            .success(function (response) {
//                                $scope.allanimals = response.tree;
//                                $scope.tmpAllAnimals = response.tree;
//                            });
            $http.get($filter('route')('api_admin_get_cards'))
                .success(function (response) {
                    $scope.allcards = response.cards;
                });
        };

    }]);
