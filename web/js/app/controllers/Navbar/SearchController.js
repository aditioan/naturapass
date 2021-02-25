/**
 * Created by vincentvalot on 30/06/14.
 */
angular.module('app')
        .controller('SearchController', ['$scope', '$filter', function ($scope, $filter) {
                $scope.users = [];
                $scope.groups = [];
                $scope.lounges = [];

                $scope.select2 = {};

                $scope.init = function () {
                    $('.navbar-search-select2').on('change', function (event) {
                        $(location).attr('href', $filter('route')('fos_user_profile_show_name', {usertag: event.added.usertag}));
                    });
                }

                $scope.options = {
                    placeholder: $filter('trans')('search.form.placeholder', {}, 'main'),
                    minimumInputLength: 3,
                    ajax: {// instead of writing the function to execute the request we use Select2's convenient helper
                        url: $filter('route')('api_v2_get_users_search'),
                        dataType: 'json',
                        data: function (term) {
                            return {
                                q: term, // search term
                                page_limit: 6
                            };
                        },
                        results: function (data, page) { // parse the results into the format expected by Select2.
                            // since we are using custom formatting functions we do not need to alter remote JSON data
                            if (data.users.length > 0 && data.users.length !== data.total) {
                                $('.select2-invite-friends').remove();
                                if ($('.select2-total-result').length === 0) {
                                    $('.select2-results').after('<div class="select2-total-result"/>');
                                }
                                $('.select2-total-result').html('<a href="' + $filter('route')('naturapass_main_search') + '?q=' + data.term + '"><span class="icon-list">' + $filter('trans')('search.form.total', {'count': data.total}, 'main') + '</span></a>');
                                $('.select2-total-result').on('click', 'a', function (e) {
                                    window.location.href = $(this).attr('href');
                                });
                            } else {
                                $('.select2-total-result').remove();
                                if ($('.select2-invite-friends').length === 0) {
                                    $('.select2-results').after('<div class="select2-invite-friends"/>');
                                }
                                $('.select2-invite-friends').html('<a href="' + $filter('route')('naturapass_user_invitation') + '">' + $filter('trans')('search.form.inviteFriends', {}, 'main') + '</a>');
                                $('.select2-invite-friends').on('click', 'a', function (e) {
                                    window.location.href = $(this).attr('href');
                                });
                            }
                            return {results: data.users};
                        }
                    },
                    formatResult: function (element) {
                        var markup = "<div class='media'>";
                        markup += "<a class='pull-left'><img src='" + element.photo + "' width='40' height='40' class='media-object' ></a>";
                        markup += "<div class='media-body'><div class='select2-user-name'><b>" + element.firstname + "</b> " + element.lastname + "</span></div>";
                        if (element.state == 2) {
                            markup += "<div class='select2-nb-friends'>" + $filter('trans')('search.list.isfriend', {}, "main") + "</div>";
                        } else {
                            if (element.relation.mutalFriends > 0) {
                                markup += "<div class='select2-nb-friends'>" + $filter('trans')('search.list.have', {}, "main") + " " + $filter('trans')('search.list.friend', {'count': element.relation.mutualFriends}, "main") + "</div>";
                            } else {
                                markup += "<div class='select2-nb-friends'>" + $filter('trans')('search.list.have_not', {}, "main") + "</div>";
                            }
                        }

                        markup += "</div></div>";
                        return markup;
                    },
                    formatSelection: function (element) {
                        return element.usertag
                    },
                    dropdownCssClass: "select-drop select-drop-nav",
                    escapeMarkup: function (m) {
                        return m;
                    }
                }
            }]);