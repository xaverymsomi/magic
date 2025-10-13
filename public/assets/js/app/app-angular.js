var app = angular.module("app", ['toaster', 'ngAnimate', 'ngSanitize', 'ui.select', 'datatables', 'mxcreate.modal', 'mxprofile.modal', 'mxdashboard.modal', 'mxpermission.modal', 'mxreport.modal', 'daterangepicker']);
var app_location = "http://localhost:8089";
app.config(function ($locationProvider) {
    $locationProvider.html5Mode({enabled: true, requireBase: false, rewriteLinks: false});
});

app.directive('a', function () {
    return {
        restrict: 'E',
        link: function (scope, elem, attrs) {
            if (attrs.ngClick || attrs.href === '') {
                elem.on('click', function (e) {
                    e.preventDefault();
                });
            }
        }
    };
});
app.directive('dateFormat', function() {
    return {
        require: 'ngModel',
        link: function(scope, element, attr, ngModelCtrl) {
            //Angular 1.3 insert a formater that force to set model to date object, otherwise throw exception.
            //Reset default angular formatters/parsers
            ngModelCtrl.$formatters.length = 0;
            ngModelCtrl.$parsers.length = 0;
        }
    };
});
// allow you to format a text input field.
// <input type="text" ng-model="test" format="number" />
// <input type="text" ng-model="test" format="currency" />
app.directive('format', ['$filter', function ($filter) {
        return {
            require: '?ngModel',
            link: function (scope, elem, attrs, ctrl) {
                if (!ctrl)
                    return;

                ctrl.$formatters.unshift(function (a) {
                    return $filter(attrs.format)(ctrl.$modelValue)
                });

                elem.bind('blur', function (event) {
                    var plainNumber = elem.val().replace(/[^\d|\-+|\.+]/g, '');
                    elem.val($filter(attrs.format)(plainNumber));
                });
            }
        };
    }]);

app.controller("menuController", function ($scope, $compile, $window, $http, $location) {
    $scope.getUserMenu = function (user_id) {
        $('.overlay').removeClass('hidden');
        $http.get(app_folder + '/menu/get_user_menus?user_id=' + user_id)
                .success(function (response) {
                    $('.overlay').addClass('hidden');
                    $scope.menus = response.data;
                    if (localStorage.getItem('MiscellaneousExist') === 'yes') {
                        var link = localStorage.getItem('CurrentLink');
                        if (link.indexOf('Miscellaneous') > -1) {
                            $scope.loadPage(localStorage.getItem('CurrentLink'), localStorage.getItem('CurrentPageTitle'), localStorage.getItem('CurrentLinkId'));
                        }
                    }
                });
    };

    $scope.current = "";
    $scope.current_location;
    $scope.current_link;
    $scope.current_title;
    $scope.searchTerm = "";
    $scope.orderColumn = "id";
    $scope.orderDirection = "desc";
    $scope.currentPage = 0;
    $scope.pageSize = 25;
    $scope.pagesList = [];
    $scope.useSearchRange = false;

    $scope.searchRange = {
        startDate: moment(), //moment().subtract(1, 'days'),
        endDate: moment(),
        location: '',
        title: '',
        currentLink: '',
    };

    $scope.initiateSearchRange = function (mxRange, useR = true) {
        $scope.useSearchRange = useR;
        if (typeof (mxRange) !== 'undefined' && mxRange !== {}) {
            $scope.searchRange.startDate = mxRange.startDate ? moment(mxRange.startDate) : moment();//moment().subtract(29, 'days');
            $scope.searchRange.endDate = mxRange.endDate ? moment(mxRange.endDate) : moment();

            $scope.searchRange.location = mxRange.mxLocation;
            $scope.searchRange.title = mxRange.mxTitle;
            $scope.searchRange.currentLink = mxRange.mxCurrentLink;
    }

    };

    $scope.opts = {
        locale: {
            applyClass: 'btn-green',
            applyLabel: "Apply",
            fromLabel: "From",
            format: "YYYY-MM-DD",
            toLabel: "To",
            cancelLabel: 'Cancel',
            customRangeLabel: 'Custom range'
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This month': [moment().startOf('month'), moment().endOf('month')]
        },
        eventHandlers: {
            'apply.daterangepicker': function (ev, picker) {
                $scope.searchRange.startDate = ev.model.startDate;
                $scope.searchRange.endDate = ev.model.endDate;
                $scope.loadPage($scope.searchRange.location, $scope.searchRange.title, $scope.searchRange.currentLink);
            }
        }

    };

    $scope.loadPage = function (_link, _title, _id) {
//            $('.overlay').removeClass('hidden');
        $scope.current_task = 'display';
        localStorage.setItem('CurrentLink', _link);
        localStorage.setItem('CurrentPageLocation', app_folder + _link);
        localStorage.setItem('CurrentPageTitle', _title);
        localStorage.setItem('CurrentLinkId', _id);

        if (_link != '#') {
            console.log('sdfasd');
            var _par = '';
            if ($scope.useSearchRange) {
                _par = _par + '?startDate=' + $scope.searchRange.startDate.format('YYYY-MM-DD');
                _par = _par + '&endDate=' + $scope.searchRange.endDate.format('YYYY-MM-DD');
            }
            var htmlcontent = $('#mabrexPageContent');
            localStorage.setItem('PageSize', $scope.pageSize);

            $('.overlay').removeClass('hidden');
            $(document).find(".progress").each(function () {
                $(this).css("visibility", "visible");
            });

            if (_link.indexOf('/Dashboard/index') > -1) {
                htmlcontent.load(_link + ' #page-content', function (data) {
                    $compile(htmlcontent.contents())($scope);

                    $scope.setPage(_title, "", _link);
                    $('.overlay').addClass('hidden');
                    $(document).find(".progress").each(function () {
                        $(this).css("visibility", "hidden");
                    });
                    location.reload();
                    $scope.$apply();
                });
            } else {
                var filter = $scope.filter == undefined ? '' : $scope.filter;
                var filterable = $scope.filterable == undefined ? '' : $scope.filterable;
                _par = '?search=' + encodeURIComponent($scope.searchTerm )+ '&order=' + $scope.orderColumn + '&dir=' + $scope.orderDirection + '&start=' + $scope.currentPage + '&length=' + $scope.pageSize + '&loc=' + _link + '&current=' + _id + '&filter=' + filter + '&filterable=' + filterable;

                if ($scope.useSearchRange) {
                    _par = _par + '&startDate=' + $scope.searchRange.startDate.format('YYYY-MM-DD');
                    _par = _par + '&endDate=' + $scope.searchRange.endDate.format('YYYY-MM-DD');
                }

                _par = _par + ' #page-content';

                htmlcontent.load(_link + _par, function (data) {
                    $compile(htmlcontent.contents())($scope);
                    $scope.pageSize = localStorage.getItem('PageSize');

                    $scope.setPage(_title, "", _link);
                    $('.overlay').addClass('hidden');
                    $(document).find(".progress").each(function () {
                        $(this).css("visibility", "hidden");
                    });
                    if (_link.indexOf('/Miscellaneous/index') > -1) {
                        $scope.misc_data = JSON.parse($(data).find('#data_content').attr('data-form'));
                        $scope.og_data = $scope.misc_data;
                        $scope.dropdowns = JSON.parse($(data).find('#data_content').attr('data-dropdowns'));
                        for (var j = 0; j < $scope.misc_data.length; j++) {
                            $scope.misc_data[j]['dat_effective_start_date'] = new Date($scope.misc_data[j]['dat_effective_start_date']);
                            $scope.misc_data[j]['dat_effective_end_date'] = new Date($scope.misc_data[j]['dat_effective_end_date']);
                        }
                        $scope.$apply();
                        localStorage.setItem('MiscellaneousExist', 'yes');
                    }
                    if (_link.indexOf('/Card/printcard') > -1) {
                        $scope.form = JSON.parse($(data).find('#data_content').attr('data-form'));
                        for (var i = 0; i < $scope.form.cards_list.length; i++) {
                            $scope.form.cards_list[i]['Last Update'] = new Date($scope.form.cards_list[i]['Last Update']);
                        }
                        $scope.$apply();
                    }
                });
            }
        }
        if ($scope.current == _id && _link == '#') {
            $scope.current = "";
        } else {
            $scope.current = _id;
            $scope.current_location = _link;
            $scope.current_title = _title;
        }
        $scope.current_link = "";
    };

    $scope.setPage = function (title, content, link) {
        jQuery(window.document)[0].title = title;
        $window.history.pushState({
            html: content,
            pageTitle: title,
            pageLink: link
        }, '', link);
        if (link.indexOf('/GlobalRules/index') > -1) {
            $scope.configureSwitch();
        }
    };

    $scope.setSubMenu = function (_id) {
        $scope.current_task = 'hide';
        $scope.current = "";
        $scope.current_link = _id;
    };

    $scope.buttons = 0;

    $scope.getTable = function (_link, _title, _id, _start, _size, _sterm, _column, _dir, _filterable, _filter) {
        $scope.pageSize = _size;
        $scope.currentPage = _start;
        $scope.searchTerm = _sterm;
        $scope.orderColumn = _column;
        $scope.orderDirection = _dir;
        $scope.filterable = _filterable;
        $scope.filter = _filter;
        $scope.loadPage(_link, _title, _id);
    };

    $scope.setPagesList = function (maximum) {
        for (var n = 1; n <= maximum; n++) {
            $scope.pagesList.push(n);
        }
    };

    $scope.clearFilter = function () {
        $scope.searchTerm = "";
    };

    $scope.setFilterable = function (filterable) {
        $scope.mxFilterableLabel = filterable.label;
        $scope.mxFilterable = filterable.value;
    };

    $scope.configureSwitch = function () {
        $(document).find("input[type='checkbox']").bootstrapSwitch({
            onColor: 'success',
            offColor: 'danger'
        });
    };

    $scope.editMiscellaneous = function (event, rule) {
        $scope.temp_misc_value = rule.txt_value;
        $scope.temp_misc_effective_start_date = rule.dat_effective_start_date;
        $scope.temp_misc_effective_end_date = rule.dat_effective_end_date;
        $scope.temp_misc_unit_id = rule.opt_mx_unit_id;
        var rule_editor = 'span.rule_editor' + rule.config_id;
        var rule_value = 'span.rule_value' + rule.config_id;
        var button = $(event.target);
        var td = button.parent();
        $(rule_editor).removeClass('hidden');
        $(rule_value).addClass('hidden');
        td.find('button.save, button.cancel').removeClass('hidden');
        td.find('button.edit').addClass('hidden');
    };

    $scope.parseDate = function (date) {
        var newDate = new Date(date);
        var day = newDate.getDate();
        var month = newDate.getMonth() + 1;
        var year = newDate.getFullYear();

        return year + '-' + month + '-' + day;
    };

    $scope.updateMiscellaneous = function (event, rule) {
        var rule_editor = 'span.rule_editor' + rule.config_id;
        var rule_value = 'span.rule_value' + rule.config_id;

        var button = $(event.target);
        var td = button.parent();

        $(rule_editor).addClass('hidden');
        $(rule_value).removeClass('hidden');
        td.find('button.save, button.cancel').addClass('hidden');
        td.find('button.edit').removeClass('hidden');

        rule.txt_effective_start_date = $scope.parseDate(rule.dat_effective_start_date);
        rule.txt_effective_end_date = $scope.parseDate(rule.dat_effective_end_date);

        $http({
            method: 'POST',
            url: `${app_url}/Miscellaneous/changeMiscellaneous`,
            data: rule,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {
                var response = $(data).find('#mabrexPageContent').text().trim();
                if (response === '200' || response === '201') {
                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                } else if (response === '210') {
                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                } else {
                    $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
                }
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');
                }, 2000);
            }
        });
    };

    $scope.saveMiscellaneous = function () {
        if ($scope.form.type == 'checkbox') {
            $scope.form.txt_value = $('#txt_value').prop("checked");
        }

        $http({
            method: 'POST',
            url: `${app_url}/Miscellaneous/save`,
            data: $scope.form,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {
                var response = $(data).find('#mabrexPageContent').text().trim();
                if (response === '200' || response === '201') {
                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                } else if (response === '210') {
                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                } else {
                    $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
                }
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');
                }, 2000);
            }
        });
    };

    $scope.cancelMiscellaneous = function (event, rule) {
        rule.txt_value = $scope.temp_misc_value;
        rule.dat_effective_start_date = $scope.temp_misc_effective_start_date;
        rule.dat_effective_end_date = $scope.temp_misc_effective_end_date;
        rule.opt_mx_units_id = $scope.temp_misc_opt_mx_units_id;

        var rule_editor = 'span.rule_editor' + rule.config_id;
        var rule_value = 'span.rule_value' + rule.config_id;

        var button = $(event.target);
        var td = button.parent();

        $(rule_editor).addClass('hidden');
        $(rule_value).removeClass('hidden');
        td.find('button.save, button.cancel').addClass('hidden');
        td.find('button.edit').removeClass('hidden');
    };

    $scope.viewMiscellaneous = function (filter) {
        $scope.misc_data = [];
        if (filter === 'all') {
            $scope.misc_data = $scope.og_data;
        } else if (filter === 'active') {
            $scope.og_data.forEach(function (item) {
                if (item['opt_mx_status_id'] == 1) {
                    $scope.misc_data.push(item);
                }
            })
        } else if (filter === 'pending') {
            $scope.og_data.forEach(function (item) {
                if (item['opt_mx_status_id'] == 2) {
                    $scope.misc_data.push(item);
                }
            })
        }
    };

    $scope.checkType = function ($event) {
        $scope.form.txt_value = "";
        $scope.dropdowns['opt_mx_rule_ids'].forEach(function (rule) {
            if (rule.id === $scope.form.int_mx_rule_id) {
                $scope.form.type = rule.type;
            }
        })
    };

    $scope.dateDiff = function (startDate, endDate) {
        if (startDate == null || endDate == null) {
            return {value: 1}
        }
        if (startDate === "" || startDate === "today") {
            startDate = new Date();
        }
        var diffDate = new Date(new Date(endDate).getTime() - new Date(startDate).getTime());
        return {
            years: (diffDate.getFullYear() - 1970),
            months: (diffDate.getMonth()),
            weeks: Math.round(diffDate / (7 * 24 * 60 * 60 * 1000)),
            days: diffDate.getDate(),
            hours: diffDate.getHours(),
            minutes: diffDate.getMinutes(),
            seconds: diffDate.getSeconds(),
            milliseconds: diffDate.getMilliseconds(),
            value: diffDate.getTime()
        }
    }
});

