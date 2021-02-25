angular.module('app')

    .controller('ModalCardLabelRemoveController', ['$scope', '$http', '$filter', '$modalInstance', 'entity', function ($scope, $http, $filter, $instance, entity) {

        $scope.data = {
            entity : entity,
            loading: false
        };

        $scope.ok = function () {
            $scope.data.loading = true;
            $instance.close();
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
        };
    }])
    .controller('ModalCardLabelContentController', ['$scope', '$http', '$filter', '$modalInstance', 'entity', function ($scope, $http, $filter, $instance, entity) {

        $scope.data = {
            entity : entity,
            loading: false
        };
        if (!entity.contents) {
            entity.contents = [];
        }
        entity.contents.push({"id": "", "name": ""});
        setTimeout(function () {
            $('.form-naturapass_content_add').find('input.form-control').last().focus();
        }, 200);

        $scope.ok = function () {
            $scope.data.loading = true;
            $instance.close();
        };

        $scope.cancel = function () {
            $instance.dismiss('cancel');
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
        };

        $scope.deleteRowContent = function ($index, content, $this) {
            var index = $scope.data.entity.contents.indexOf(content);
            if (index > -1) {
                $scope.data.entity.contents.splice(index, 1);
            }
        }
        $scope.addNewContent = function () {
            entity.contents.push({"id": "", "name": ""});
            setTimeout(function () {
                $('.form-naturapass_content_add').find('input.form-control').last().focus();
            }, 200);
        }
    }]);