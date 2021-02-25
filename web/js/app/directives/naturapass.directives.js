/**
 * Created by vincentvalot on 07/08/14.
 */
angular.module('naturapass.directives', ['naturapass.filters'])

    .directive('npReadOnly', [function () {
        return {
            restrict: 'A',
            link    : function ($scope, element, attrs) {
                $scope.$watch(attrs.npReadOnly, function (value) {
                    if (value) {
                        element.attr('readonly', true);
                        element.attr('disabled', true);
                    } else {
                        element.removeAttr('readonly');
                        element.removeAttr('disabled');
                    }
                });
            }
        }
    }])

    .directive('npSlider', [function () {
        return {
            restrict: 'A',
            link    : function ($scope, element, attrs) {
                element.bxSlider({mode: 'fade', maxSlides: 4});
            }
        }
    }])

    .directive('checkList', function () {
        return {
            scope: {
                list : '=checkList',
                value: '@'
            },
            link : function (scope, elem, attrs) {
                var handler = function (setup) {
                    var checked = elem.prop('checked');
                    var index = scope.list.indexOf(scope.value);

                    if (checked && index == -1) {
                        if (setup)
                            elem.prop('checked', false);
                        else
                            scope.list.push(scope.value);
                    } else if (!checked && index != -1) {
                        if (setup)
                            elem.prop('checked', true);
                        else
                            scope.list.splice(index, 1);
                    }
                };

                var setupHandler = handler.bind(null, true);
                var changeHandler = handler.bind(null, false);

                elem.bind('change', function () {
                    scope.$apply(changeHandler);
                });
                scope.$watch('list', setupHandler, true);
            }
        };
    })

/**
 * Directive permettant de retarder l'initialisation d'une google maps avec le plugin AngularJS
 *
 * $watch sur un booléen true/false
 */
    .directive('npGoogleMaps', ['$compile', function ($compile) {
        return {
            restrict: 'A',
            replace : true,
            link    : function ($scope, element, attrs) {
                $scope.$watch(attrs.npGoogleMaps, function (value) {
                    if (!value)
                        return;

                    var $container = angular.element('<google-map></google-map>')
                    $container.html(element.html());

                    $.each(element[0].attributes, function (index, attr) {
                        if (attr.specified) {
                            $container.attr(attr.name, attr.value);
                        }
                    });

                    $container.removeAttr('np-google-maps');
                    $container.addClass('google-map');

                    $container = $compile($container)($scope);

                    element.replaceWith($container);
                })
            }
        }
    }])

    .directive('npImageCrop', function () {
        return {
            restrict: 'E',
            replace : true,
            scope   : {src: '@', selected: '&', released: '&'},
            link    : function (scope, element, attr) {
                var myImg;
                var clear = function () {
                    if (myImg) {
                        myImg.next().remove();
                        myImg.remove();
                        myImg = undefined;
                    }
                };

                scope.$watch('src', function (nv) {
                    clear();
                    if (nv) {
                        element.after('<img />');
                        myImg = element.next();
                        myImg.attr('src', nv);
                        $(myImg).Jcrop({
                            trackDocument: true,
                            onRelease    : function () {
                                scope.$apply(function () {
                                    scope.released();
                                });
                            },
                            onSelect     : function (x) {
                                scope.$apply(function () {
                                    scope.selected({coords: x});
                                });
                            },
                            boxWidth     : element.parent().width(), //Maximum width you want for your bigger images
                            boxHeight    : element.parent().height()  //Maximum Height for your bigger images
                        }, function () {
                            // Use the API to get the real image size
                            var bounds = this.getBounds();
                            var boundx = bounds[0];
                            var boundy = bounds[1];
                        });
                    }
                });
                scope.$on('$destroy', clear);
            }
        };
    })

    .directive('npScrollbar', ['$timeout', function ($timeout) {
        return {
            scope: {npScrollbarScrolledBack: '&', npScrollbarPosition: '='},
            link : function ($scope, $element, attrs) {
                var $container = angular.element('.' + $element.attr('class').replace(' ', '.'));

                $container.mCustomScrollbar({
                    scrollInertia     : 0,
                    scrollButtons     : {
                        enable: false
                    },
                    advanced          : {
                        updateOnBrowserResize: true,
                        updateOnContentResize: true
                    },
                    contentTouchScroll: true,
                    callbacks         : {
                        onTotalScrollBack: function () {
                            $scope.npScrollbarScrolledBack({mcs: this.mcs});
                        }
                    }
                });

                $scope.$watch(function () {
                    return $scope.npScrollbarPosition
                }, function (value) {
                    if (value) {
                        $container.mCustomScrollbar('update');

                        $timeout(function () {
                            $container.mCustomScrollbar('scrollTo', value);
                            $scope.npScrollbarPosition = false;
                        });
                    }
                });
            }
        };
    }])
    .directive('npVideo', ['$parse', function ($parse) {
        return {
            restrict: 'E',
            link    : function ($scope, $element, attrs) {
                attrs.type = attrs.type || "video/mp4";

                $scope.$watch(attrs.npVideoData, function (value) {
                    if (!value)
                        return;

                    var $video = angular.element('<video class="video-js vjs-default-skin ' + $element.attr("class") + '"></video>');
                    $video.attr('id', 'video-' + Math.floor(Math.random() * 1000000) + 1);

                    if (value.mp4) {
                        var $mp4 = angular.element('<source src="' + value.mp4 + '" type="video/mp4">');
                        $video.append($mp4);
                    }

                    if (value.ogv) {
                        var $ogv = angular.element('<source src="' + value.ogv + '" type="video/ogg">');
                        $video.append($ogv);
                    }

                    if (value.webm) {
                        var $webm = angular.element('<source src="' + value.webm + '" type="video/webm">');
                        $video.append($webm);
                    }

                    $element.replaceWith($video);

                    var setup = {
                        techOrder: ['html5', 'flash'],
                        controls : true,
                        preload  : 'metadata',
                        autoplay : false,
                        poster   : value.poster,
                        width    : attrs.npWidth,
                        height   : attrs.npHeight
                    };

                    var player = videojs($video[0], setup);

                    $scope.$watch(attrs.npHeight, function (value) {
                        if (value) {
                            player.height(value);
                        }
                    });

                    $scope.$watch(attrs.npWidth, function (value) {
                        if (value) {
                            player.width(value);
                        }
                    });

                    $scope.$on('$destroy', function () {
                        player.dispose();
                    });
                });
            }
        };
    }])
    .directive('npDatetimePicker', ['$parse', function ($parse) {
        return {
            restrict: 'A',
            link    : function ($scope, $element, attrs) {
                var oDate = {}, allowTime = true;
                if (!attrs.npDatetimePicker || attrs.npDatetimePicker == "false") {
                    allowTime = false;
                    oDate = {
                        startView: 2,
                        maxView  : 2,
                        minView  : 2
                    };
                }
                $element.datetimepicker($.extend(oDate, {
                    language  : Translator.locale,
                    format    : 'dd/mm/yyyy' + (allowTime ? ' hh:ii' : ''),
                    linkField : "mirror_field",
                    linkFormat: "yyyy-mm-dd" + (allowTime ? ' hh:ii' : ''),
                    autoclose : true,
                }));

                if (attrs.npFormatted) {
                    $element.datetimepicker().on('changeDate', function (event) {
                        $parse(attrs.npFormatted).assign($scope, moment(event.date).format());
                    });
                }

            }
        };
    }])
    .directive('npDatetimePickerHour', ['$parse', function ($parse) {
        return {
            restrict: 'A',
            link    : function ($scope, $element, attrs) {
                $element.datetimepicker({
                    language  : Translator.locale,
                    format    : 'HH:ii',
                    linkField : "mirror_field",
                    linkFormat: "HH:ii",
                    startView : 1,
                    maxView   : 1,
                    autoclose : true,
                }).on("show", function () {
                    $(".datetimepicker").addClass("timepicker");
                }).on("hide", function () {
                    $(".datetimepicker").removeClass("timepicker");
                });

                if (attrs.npFormatted) {
                    $element.datetimepicker().on('changeDate', function (event) {
                        $parse(attrs.npFormatted).assign($scope, moment(event.date).format());
                    });
                }

            }
        };
    }])
    .directive('npSlide', function () {
        return {
            //restrict it's use to attribute only.
            restrict: 'A',
            //set up the directive.
            link    : function (scope, elem, attr) {
                //set up the watch to toggle the element.
                scope.$watch(attr.npSlide, function (v) {
                    if (v && !elem.is(':visible')) {
                        elem.slideDown();
                    } else {
                        elem.slideUp();
                    }
                });
            }
        };
    })
    .directive('uiSelect2', ['$parse', function ($parse) {
        return {
            restrict: 'A',
            link    : function ($scope, $element, attrs) {
                var modelAccessor = $parse(attrs.ngModel);
                modelAccessor.assign($scope, $element.select2($scope.$eval(attrs.uiSelect2)));
            }
        };
    }])
    .directive('npDragOver', function () {
        return {
            restrict: 'A',
            link    : function ($scope, $element, attrs) {
                $(document).on('dragover', function () {
                    $scope.$apply(attrs.npDragOver);
                });
            }
        };
    })
    .directive('npEatClick', function () {
        return {
            restrict: 'A',
            link    : function (scope, element, attrs) {
                $(element).click(function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                });
            }
        };
    })
    .directive('npBindHtml', ['$filter', function ($filter) {
        return function ($scope, element, attr) {
            $scope.$watch(attr.npBindHtml, function (value) {
                element.html($filter('nl2br')($scope.$eval(attr.npBindHtml)));
            });
        };
    }])
    .directive('npSubmitLoader', ['$parse', '$filter', function ($parse, $filter) {
        return {
            restrict: 'A',
            link    : function ($scope, $element, attr) {
                $scope.$watch(attr.npSubmitLoader, function (value) {
                    if (value) {
                        var regex = new RegExp('\\b' + 'icon-' + '.+?\\b', 'g');
                        var sIcon = $element.attr('class').match(regex);
                        var sText = $element.html();
                        $element.attr('class', $element.attr('class').replace(regex, ''));
                        if ($element.hasClass('btn-icon')) {
                            $element.removeClass('btn-icon');
                        }
                        $element.attr({'data-icon': sIcon, 'data-text': sText, 'disabled': 'disabled'}).addClass('icon-spinner3 btn-loader').text($filter('trans')('button.load', {}, 'global'));
                    } else {
                        var regex = new RegExp('\\b' + 'icon-' + '.+?\\b', 'g');
                        var sIcon = $element.data('icon');
                        var sText = $element.data('text');
                        $element.attr('class', $element.attr('class').replace(regex, ''));
                        $element.removeAttr('disabled').removeClass('btn-loader icon-spinner3');
                        $element.addClass(sIcon + ' btn-icon').html(sText);
                    }
                });
            }
        };
    }])
    .directive('npPopover', function () {
        return {
            restrict: 'A',
            link    : function ($scope, trigger, attr) {
                var container = $(attr.npPopover);

                container.on('mouseenter', function (event) {
                    container.mouseIn = true;
                });

                container.on('mouseleave', function (event) {
                    container.mouseIn = false;
                });

                trigger.on('click', function () {
                    container.fadeToggle(150);

                    container.css('top', trigger.position().top + trigger.outerHeight(true) + 'px');
                    container.css('left', trigger.position().left - container.outerWidth(true) / 2 + trigger.outerWidth(true) / 2 + 'px');
                });

                trigger.on('blur', function (e) {
                    if (!container.mouseIn) {
                        container.fadeOut(150);
                    }
                });
            }
        };
    })
    .directive('npFocusOn', function () {
        return {
            restrict: 'A',
            link    : function ($scope, $element, attrs) {
                $scope.$watch(attrs.npFocusOn, function (value) {
                    if (value) {
                        $element[0].focus();
                    }
                });
            }
        };
    })
    .directive('focusMe', ['$timeout', '$parse', function ($timeout, $parse) {
        return {
            //scope: true,   // optionally create a child scope
            link: function (scope, element, attrs) {
                var model = $parse(attrs.focusMe);
                scope.$watch(model, function (value) {
                    if (value === true) {
                        $timeout(function () {
                            element[0].focus();
                        });
                    }
                });
            }
        };
    }])
    .directive('ngVisible', function () {
        return function (scope, element, attr) {
            scope.$watch(attr.ngVisible, function (visible) {
                element.css('visibility', visible ? 'visible' : 'hidden');
            });
        };
    })
    .directive('messagesRepeatFinished', ['$timeout', function ($timeout) {
        return {
            restrict: 'A',
            link    : function (scope, element, attrs) {
                $timeout(function () {
                    var $chatBody = $(".npChatLayoutBody", $("#chattab-" + attrs.messagesRepeatFinished));

                    if (scope.$last && ($chatBody.attr("message-cursor") == "bottom" || $chatBody.attr("message-cursor") == "")) {
                        $chatBody.scrollTop($chatBody[0].scrollHeight);
                    }
                    else if ($chatBody.attr("message-cursor") == "top") {
                        var $scrollHeight = 0;
                        $('.list-group-item', $chatBody).each(function (index) {
                            if (scope.$index >= index) {
                                $scrollHeight += $(this).height();
                            }
                        });

                        $chatBody.scrollTop($scrollHeight);
                    }
                });
            }
        };
    }])
    .directive('chattabRepeatFinished', ['$timeout', function ($timeout) {
        return {
            restrict: 'A',
            link    : function (scope, element, attrs) {
                $timeout(function () {
                    scope.$$phase || scope.$apply(attrs.chattabRepeatFinished);
                });
            }
        };
    }])
    .directive('scrollToTop', function () {
        return {
            restrict: 'A',
            link    : function (scope, element, attrs) {
                angular.element(element).bind("scroll", function () {
                    if (this.scrollTop <= 0) {
                        scope.$$phase || scope.$apply(attrs.scrollToTop);
                    }
                });
            }
        };
    })
    .directive('scrollToBottom', function () {
        return {
            restrict: 'A',
            link    : function (scope, element, attrs) {
                angular.element(element).bind("scroll", function () {
                    if ((this.scrollTop + this.clientHeight) >= this.scrollHeight) {
                        scope.$$phase || scope.$apply(attrs.scrollToBottom);
                    }
                });
            }
        };
    })
    .directive('resize', ['$window', function ($window) {
        return function (scope, element, attr) {

            var w = angular.element($window);
            scope.$watch(function () {
                scope.minimizeTabs();
            });

            w.bind('resize', function () {
                scope.$$phase || scope.$apply();
            });
        }
    }]);