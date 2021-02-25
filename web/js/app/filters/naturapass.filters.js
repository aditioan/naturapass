/**
 * Created by vincentvalot on 07/08/14.
 */
angular.module('naturapass.filters', [])
    .filter('isEmpty', function () {
        var bar;
        return function (obj) {
            for (bar in obj) {
                if (obj.hasOwnProperty(bar)) {
                    return false;
                }
            }
            return true;
        };
    })

    .filter('isset', function () {
        return function (value) {
            return angular.isDefined(value);
        };
    })

    .filter('highlight', function ($sce) {
        return function (text, phrase) {
            if (phrase)
                text = text.replace(new RegExp('(' + phrase + ')', 'gi'),
                    '<span class="highlighted">$1</span>')

            return $sce.trustAsHtml(text)
        }
    })
/**
 * Filtre un tableau et ne laisse passer que les clés présentes dans le model
 */
    .filter('sanitizeArray', ['$filter', function ($filter) {
        return function (data, model) {
            var clone = {};

            for (var key in data) {
                if (data.hasOwnProperty(key) && model.hasOwnProperty(key)) {
                    if (typeof data[key] === 'object' && !Array.isArray(data[key]) && typeof model[key] === 'object' && !Array.isArray(model[key])) {
                        clone[key] = $filter('sanitizeArray')(data[key], model[key]);
                    } else {
                        clone[key] = data[key];
                    }
                }
            }

            return clone;
        }
    }])

    .filter('trans', function () {
        return function (word, params, domain) {
            return Translator.trans(word, params, $('html').data("translation")+domain);
        };
    })

    .filter('transchoice', function () {
        return function (word, number, params, domain) {
            return Translator.transChoice(word, number, params, $('html').data("translation")+domain);
        };
    })

    .filter('relativetime', function () {
        return function (date) {
            return moment(date).fromNow();
        };
    })

    .filter('date', function () {
        return function (date, format) {
            return format.length ? moment(date).format(format) : moment(date).format();
        };
    })

    .filter('route', function () {
        return function (route, params) {
            return Routing.generate(route, params);
        };
    })

    .filter('nl2br', function () {
        return function (text) {
            return text ? text.replace(/\n/g, '<br/>') : '';
        };
    })

    .filter('getGameType', function () {
        return function (type) {
            return (type == 0) ? 'Jeux concours' : 'Challenge';
        };
    })

    .filter('joinBy', function () {
        return function (array, attr, delimiter) {
            var joined = [];

            angular.forEach(array, function (element) {
                joined.push(element[attr]);
            });

            return joined.join(delimiter || ', ');
        }
    });