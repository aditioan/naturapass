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

        $scope.deleteNode = function (entity) {
            entity.visible = 0;
            if (entity.nodes.length > 0) {
                $.each(entity.nodes, function (index, value) {
                    $scope.deleteNode(value);
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

        $scope.init = function (receiver) {
            $scope.loadingWaiting = true;
            $http.get($filter('route')('api_admin_get_category_all_receiver', {receiver: receiver}))
                .success(function (response) {
                    $scope.data = response.tree;
                    $scope.loadingWaiting = false;
                })
                .error(function (response) {
                    $scope.loadingWaiting = false;
                });
        };

    }]);
