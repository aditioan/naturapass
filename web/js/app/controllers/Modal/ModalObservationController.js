/**
 * Created by vincentvalot on 28/05/14.
 */

angular.module('app').controller('ModalObservationController', ['$scope', '$http', '$filter', '$modalInstance', 'publication', 'edit', function ($scope, $http, $filter, $instance, publication, edit) {
    $scope.data = {
        loading: false
    };
    $scope.step = {
        firstQuestion : true,
        listCategories: true,
        searchAnimal  : false,
        hasCard       : false,
        shareQuestion : false,
        sharing       : false,
        noCard        : false,
        isSharing     : false
    };
    $scope.btn = {
        validate: false,
        previous: false,
        search  : false
    };
    $scope.activeSharing = !edit;
    $scope.publication = publication.publication;
    $scope.listCategories = [];
    $scope.contentArray = [];
    $scope.tree = [];
    $scope.allCards = [];
    $scope.specificCard = [];
    $scope.allAnimals = [];
    $scope.stack = [];
    $scope.stackSelect = [];
    $scope.card = "";
    $scope.model = null;
    $scope.sharing = null;
    $scope.checkSharing = [];
    $scope.input = {filterList: ""};
    $scope.restoreStep = function () {
        $scope.step.firstQuestion = false;
        $scope.step.listCategories = false;
        $scope.step.searchAnimal = false;
        $scope.step.hasCard = false;
        $scope.step.shareQuestion = false;
        $scope.step.sharing = false;
        $scope.step.noCard = false;
        $scope.step.isSharing = false;
    };
    $scope.restoreBtn = function () {
        $scope.btn.validate = false;
        $scope.btn.previous = false;
        $scope.btn.search = false;
    };
    $scope.questionSharing = function () {
        $scope.restoreStep();
        $scope.restoreBtn();
        $scope.btn.validate = false;
        $scope.step.shareQuestion = true;
    };
    $scope.sharingAction = function () {
        $scope.restoreStep();
        $scope.restoreBtn();
        $scope.step.sharing = true;
        $scope.btn.validate = true;
        $scope.btn.previous = true;
    };

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
            $scope.restoreStep();
            $scope.step.listCategories = true;
        } else {
            $http.get($filter('route')('api_v2_get_publication_observation_animals', {limit: 10000, offset: 0, filter: filterList}))
                .success(function (response) {
                    $scope.allAnimals = response.animals;
                    $scope.restoreStep();
                    $scope.step.searchAnimal = true;
                })
                .error(function () {
                });
        }
    };
    $scope.init = function () {
        $scope.editing = false;
        if ($scope.publication.observations[0]) {
            $scope.editing = true;
        }
        $scope.specifieObservation();
    }
    $scope.save = function (isValid) {

        var aShared = [];
        if (isValid) {
            $scope.valideCard();
            var aAttachement = [];
            $.each($scope.model.attachments, function (index, node) {
                if (node.value != null && node.value != "") {
                    delete node.name;
                    delete node.type;
                    delete node.required;
                    aAttachement.push(node);
                }
            });
            $scope.model.attachments = aAttachement;
            $scope.data.loading = true;
            $.each($scope.checkSharing, function (index, node) {
                aShared.push({receiver: node});
            });
            $scope.model.receivers = aShared;
            if ($scope.publication.observations[0]) {
                $http.put($filter('route')('api_v2_put_publication_observation_website', {observation: publication.publication.observations[0].id}), {observation: $scope.model})
                    .success(function (response) {
                        $scope.data.loading = false;
                        $instance.close(response);
                    })
                    .error(function (response) {
                        $scope.data.loading = false;
                    });
            }
            else {
                $http.post($filter('route')('api_v2_post_publication_observation', {publication: publication.publication.id}), {observation: $scope.model})
                    .success(function (response) {
                        $scope.data.loading = false;
                        $instance.close(response);
                    })
                    .error(function (response) {
                        $scope.data.loading = false;
                    });
            }
        }
    };

    $scope.valideCard = function (toShow) {
        $aNewLabels = [];
        $.each($scope.model.attachments, function (index, label) {
            if (label.contents) {
                delete label.contents;
            }
            if (label.type == 30) {
                label.value = label.value.id;
                $aNewLabels.push(label);
            } else if (label.type == 31) {
                var labelCopy = angular.copy(label);
                $.each(labelCopy.value, function (index, value) {
                    var newLabelCopy = angular.copy(labelCopy);
                    newLabelCopy.value = value.id;
                    $aNewLabels.push(newLabelCopy);
                });
            } else if (label.type == 32) {
                var labelCopy = angular.copy(label);
                $.each(labelCopy.value, function (index, value) {
                    var newLabelCopy = angular.copy(labelCopy);
                    newLabelCopy.value = value.id;
                    $aNewLabels.push(newLabelCopy);
                });
            } else if (label.type == 40) {
                var labelCopy = angular.copy(label);
                if ($scope.contentArray[label.id]) {
                    $.each($scope.contentArray[label.id], function (index, value) {
                        var newLabelCopy = angular.copy(labelCopy);
                        newLabelCopy.value = value;
                        $aNewLabels.push(newLabelCopy);
                    });
                }
            } else {
                $aNewLabels.push(label);
            }
        });
        $scope.model.attachments = $aNewLabels;
    }

    $scope.next = function (node) {
        var iIndice = $scope.stack.length - 1,
            stepSearchAnimal = $scope.step.searchAnimal;
        $scope.restoreStep();
        $scope.restoreBtn();
        $scope.btn.validate = true;
        if (iIndice >= 0) {
            $scope.btn.previous = true;
        }
        if (node.search) {
            $scope.btn.search = true;
        }
        $scope.listCategories = [];
        $scope.stackSelect.push(node.name);

        if (stepSearchAnimal) {
            var oldcategory = $scope.model.category;
        }
        $scope.model = {
            "specific"   : 0,
            "animal"     : null,
            "attachments": [],
            "receivers"  : [],
            "category"   : null
        };
        if (stepSearchAnimal) {
            $scope.model.specific = 1;
            $scope.model.animal = node.id;
            $scope.model.category = oldcategory;
        } else {
            $scope.model.category = node.id;
        }
        if (stepSearchAnimal) {
            var aCards = [];
            $.each($scope.specificCard.labels, function (index, label) {
                aCards.push({label: label.id, value: "", name: label.name, type: label.type, required: label.required});
            });
            $scope.model.attachments = aCards;
            $scope.btn.validate = true;
            $scope.step.hasCard = true;
        } else {
            if (node.children.length > 0) {
                $.each(node.children, function (index, node) {
                    $scope.listCategories.push(node);
                });
                $scope.step.listCategories = true;
            } else if (node.card) {
                var aCards = [];
                $.each($scope.allCards[node.card.id].labels, function (index, label) {
                    var oCard = {label: label.id, value: "", name: label.name, type: label.type, required: label.required};
                    if (label.contents) {
                        oCard.contents = label.contents;
                    }
                    //$scope.labelcontentsSelect[label.id] = [];
                    aCards.push(oCard);
                });
                $scope.model.attachments = aCards;
                //console.log(node.receivers.length);
                if (node.receivers.length && $scope.activeSharing) {
                    $scope.step.isSharing = true;
                    $scope.sharing = node.receivers;
                } else {
                    $scope.btn.validate = true;
                }
                $scope.step.hasCard = true;
            } else {
                $scope.step.noCard = true;
                if (node.receivers.length && $scope.activeSharing) {
                    $scope.step.isSharing = true;
                    $scope.sharing = node.receivers;
                } else {
                    $scope.btn.validate = true;
                }
            }
        }
        $scope.stack.push({'categories': $scope.listCategories, 'btn': angular.copy($scope.btn), 'step': angular.copy($scope.step)});
    };
    $scope.previous = function () {
        var iIndice = $scope.stack.length - 2,
            iIndiceRemove = $scope.stack.length - 1;
        $scope.card = "";
        $scope.model = null;
        $scope.stackSelect.splice(($scope.stackSelect.length - 1), 1);
        $scope.input.filterList = "";
        if (iIndice >= 0) {
            var stack = $scope.stack[iIndice];
            $scope.listCategories = [];
            $.each(stack.categories, function (index, node) {
                $scope.listCategories.push(node);
            });
            $scope.btn = angular.copy(stack.btn);
            $scope.step = angular.copy(stack.step);
            $scope.stack.splice(iIndiceRemove, 1);
        } else {
            $scope.btn.previous = false;
        }
    };
    $scope.specifieObservation = function () {
        $scope.restoreStep();
        $scope.restoreBtn();
        $scope.data.loading = true;
        $http.get($filter('route')('api_v2_get_categories_publication', {publication: publication.publication.id}))
            .success(function (response) {
                $scope.tree = response.tree;
                $scope.allCards = response.cards;
                $scope.specificCard = response.specific_card[0];
                $scope.data.loading = false;
                $.each($scope.tree, function (index, node) {
                    $scope.listCategories.push(node);
                });
                $scope.step.listCategories = true;
                $scope.step.firstQuestion = true;
                $scope.stack.push({'categories': $scope.listCategories, 'btn': angular.copy($scope.btn), 'step': angular.copy($scope.step)});
            })
            .error(function (response) {
                $scope.data.loading = false;
            });
//            $http.get($filter('route')('api_v2_get_categories'))
//                    .success(function (response) {
//                        $scope.tree = response.tree;
//                        $scope.allCards = response.cards;
//                        $scope.specificCard = response.specific_card[0];
//                        $scope.data.loading = false;
//                        $.each($scope.tree, function (index, node) {
//                            $scope.listCategories.push(node);
//                        });
//                        $scope.step.listCategories = true;
//                        $scope.stack.push({'categories': $scope.listCategories, 'btn': angular.copy($scope.btn), 'step': angular.copy($scope.step)});
//                    })
//                    .error(function (response) {
//                        $scope.data.loading = false;
//                    });
    };
    $scope.ok = function () {
        $instance.close(null);
    };
    $scope.cancel = function () {
        $instance.close(null);
    };
}
]);