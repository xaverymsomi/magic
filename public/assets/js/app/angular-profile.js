
var app = angular.module('mxprofile.modal', ['ui.bootstrap']);

app.controller('profileController', ['$scope', '$modal', '$log', '$timeout', '$http', '$compile', '$interval', '$filter', 'toaster', '$sce', function ($scope, $modal, $log, $timeout, $http, $compile, $interval, $filter, toaster) {
        $scope.currentsms = 1;
        $scope.form = {};
        $scope.dropdowns = {};
        $scope.extraControl = {};
        $scope.autoCompleteSelectOptions = {};
        var time_data = [
            'From Time', 'To Time', 'Departure Time', 'Collection Time', 'Opening Hour', 'Closing Hour', 'last_updated', 'dat_dose_date'
        ];
        var time_urls = [
            'Application', 'Center', 'Vaccination'
        ];
        $scope.initiateAutocomplete = function () {
            // initiate autocomplete on start
            if ($scope.url === 'Incident')
            {
                if ($scope.extraControl.instituFtion === 0)
                {f
                    $scope.form.opt_mx_institution_id = $scope.dropdowns.opt_mx_institution_ids[0].id;
                }
                $scope.autoComplete('', 'opt_mx_subscriber_ids');
            }
        };

        $scope.autoComplete = function (searchKey, searchComponent) {
            // check if institution selected or add other inputs to before proceed for BCX
            if (typeof ($scope.form.opt_mx_institution_id) === 'undefined' && $scope.extraControl.institution === 0) {
                toaster.pop('error', "error", "Please Select Institution First!");
                return;
            }

            var location = app_url + '/views/' + $scope.url + '/get_' + $scope.url + '_autocomplete_dropdowns.php';
            var post_data = {};
            var controls = [];
            controls.push({
                'opt_mx_institution_id': $scope.form.opt_mx_institution_id
            });

            post_data = {controls: controls, 'key': searchKey, 'table': 'subscriber', 'searchColumn': ['txt_name']};

            $http({
                method: 'POST',
                url: location,
                data: post_data,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function successCallback(response) {
                $scope.autoCompleteSelectOptions[searchComponent] = response.data[searchComponent];
            }, function errorCallback(response) {
            });
        };

        //called when record row clicked to open the profile modal
        $scope.showProfile = function (url, id) {
            $('.overlay').removeClass('hidden');
            $scope.url = url;
            $scope.frmData = {};
            $scope.current_tab = url;
            $scope.is_profile_tab = true;
            $scope.parent_id = id;
            var formURL = `${app_url}/${url}/profile/${id}`;
            var modalInstance;
            var div = $('<div/>').load(formURL + ' #page-content', function () {
                $('.overlay').addClass('hidden');
                var template = div.find('#display_content').html();
                $('.overlay').addClass('hidden');
                $scope.tabs = JSON.parse(div.find('#data_content').attr('data-tabs'));
                $scope.initial_tab_data = JSON.parse(div.find('#data_content').attr('data-initial'));
                $scope.hidden_columns = JSON.parse(div.find('#data_content').attr('data-hidden-columns'));
                if (div.find('#data_content').attr('data-extras') !== undefined) {
                    $scope.extras = JSON.parse(div.find('#data_content').attr('data-extras'));
                }

                if (div.find('#data_content').attr('data-investigation-detail') !== undefined) {
                    $scope.investigation_detail = JSON.parse(div.find('#data_content').attr('data-investigation-detail'));
                }
                if (div.find('#data_content').attr('data-current-institution') !== undefined) {
                    $scope.cur_institution = JSON.parse(div.find('#data_content').attr('data-current-institution'));
                }
                if ($scope.current_tab === 'VehiclePass') {
                    var n = $scope.initial_tab_data['Vehicle Image'].slice(5);
                    $scope.src = n;
                }
                if ($scope.current_tab === 'Vehicle_Entry') {
                    var n = $scope.initial_tab_data['Vehicle Image'].slice(5);
                    $scope.src = n;
                }
                if (div.find('#data_content').attr('data-missing-accounts') !== undefined) {
                    $scope.missing_accounts = JSON.parse(div.find('#data_content').attr('data-missing-accounts'));
                }

                if (time_urls.includes(url)) {
                    time_data.forEach(function (item) {
                        if ($scope.initial_tab_data[item])
                            $scope.initial_tab_data[item] = new Date('' + $scope.initial_tab_data[item])
                    });

                    if ($scope.extras.sample_info !== undefined) {
                        time_data.forEach(function (item) {
                            if ($scope.extras.sample_info[item])
                                $scope.extras.sample_info[item] = new Date($scope.extras.sample_info[item])
                        });
                    }

                }

                modalInstance = $modal.open({
                    template: template,
                    controller: ModalProfileCtrl,
                    windowClass: 'mx-modal-form',
                    scope: $scope
                });
                $scope.$apply();
            });
        };

        $scope.getAssociatedRecords = function (caller, id) {
            console.log(caller, id);
            $scope.current_tab = caller;
            $scope.is_profile_tab = false;
            var formURL = `${app_url}/${$scope.url}/associated_records/${id}/${caller}`;
            var div = $('<div/>').load(formURL + ' #page-content', function () {
                var template = div.find('#display_content').html();
                var data = div.find('#data_content');
                $scope.table_headers = JSON.parse(data.attr('data-headings'));
                $scope.associated_records = JSON.parse(data.attr('data-associated'));
                $scope.labels = JSON.parse(data.attr('data-labels'));
                $scope.hiddens = JSON.parse(data.attr('data-hiddens'));
                $scope.formatters = JSON.parse(data.attr('data-formatters'));
                $scope.associated_actions = JSON.parse(data.attr('data-actions'));
                $(document).find('#' + caller).find('.associated_section').html($compile(template)($scope));
                $scope.$apply();
            });
        };

        //Invoice Path
        $scope.toPath = function (type,reference){
            var url = `/pdf/tmp/${type}-${reference}.pdf`;
            return url;
        }
        //Invoice Path
        $scope.toPathApp = function (type,reference){
            var url = `/pdf/tmp/${type}-${reference}.pdf`;
            return url;
        }
        $scope.toPathAppFee = function (type,reference){
            var url = `/pdf/tmp/${type}-${reference}.pdf`;
            return url;
        }
        $scope.toPermitExtensionPath = function (reference){
            var url = `/pdf/tmp/${reference}.pdf`;
            console.log(url);
            return url;
        }
        //Vehicle Invoice Path
        $scope.toPathVehicle = function (vehicle_number){
            var url = `/pdf/tmp/${vehicle_number}.pdf`;
            return url;
        }
        //Vehicle Invoice Path
        $scope.toPathVehicleInvoice = function (vehicle_number){
            var url = `/pdf/tmp/1-${vehicle_number}.pdf`;
            return url;
        }

        //Function to convert all dates to date string
        $scope.toDate = function (date) {
            if (date == null) {
                return;
            }
            return new Date(date).toDateString();
        }

        //Test Function
        $scope.testFunc = function () {
            console.log($scope.form.others);
        }

        $scope.monthDiff = function (date) {
            var current_date = new Date();
            var covid_date = date;
        }

        $scope.checkDateValue = function (date) {
            var current_date = new Date();
            var covid_date = new Date(date);
            var months = diff_months(current_date, covid_date);
            //var months =(years * 12) + (current_date.getMonth() - covid_date.getMonth());
            console.log(months);
        }

        $scope.reFormatDate = function (date) {
            var new_date = Date.parse(date);
            console.log(new_date);
            return new_date;
        }


        $scope.getProfileRecords = function (caller, id) {
            $('.overlay').removeClass('hidden');
            $scope.current_tab = caller;
            $scope.is_profile_tab = true;
            $scope.fetchProfile(id);
        };

        $scope.fetchProfile = function (id) {
            var formURL = `${app_url}/${$scope.url}/profile/${id}`;
            var div = $('<div/>').load(formURL + ' #page-content', function () {
                $('.overlay').addClass('hidden');
                var template = div.find('.profile_section').html();
                $scope.initial_tab_data = JSON.parse(div.find('#data_content').attr('data-initial'));
                $scope.hidden_columns = JSON.parse(div.find('#data_content').attr('data-hidden-columns'));
                if (div.find('#data_content').attr('data-account-detail') !== undefined) {
                    $scope.account_detail = JSON.parse(div.find('#data_content').attr('data-account-detail'));
                }
                if (div.find('#data_content').attr('data-extras') !== undefined) {
                    $scope.extras = JSON.parse(div.find('#data_content').attr('data-extras'));
                }
                $(document).find('#' + $scope.current_tab).find('.profile_section').html($compile(template)($scope));

                $('.overlay').addClass('hidden');
                $scope.$apply();
            });
        }

        $scope.escapeHtml = function (text) {
            var map = {
                //'&amp;':'&' ,
                '&lt;': '<',
                '&gt;': '>',
                '&quot;': '"',
                '&amp;#39;': "'",
                '&amp;apos;': "'"
            };

            Object.keys(map).forEach(function (m) {
                if (text.includes(m)) {
                    text = text.replace(m, map[m]);
                }
                //text.replace(m,map[m]);
            });

            return text;
        }

        // Open form for a clicked action
    $scope.showActionForm = function (id, url, action) {
            // console.log("Event fired")
            $('.overlay').removeClass('hidden');
            if (action.toLowerCase() === 'preview_transfer' || action.toLowerCase() === 'preview_card_transfer') {
                $scope.generateSampleTransferPDF(url, action, id);
            }else {
                var formURL = `${app_url}/${url}/${action.toLowerCase()}/${id}`;
                $scope.url = url;
                $scope.action_name = action;
                var modalInstance;
                var div = $('<div/>').load(formURL + ' #page-content', function () {
                    var template = div.find('#display_content').html();
                    $scope.dropdowns = JSON.parse(div.find('#data_content').attr('data-dropdowns'));
                    if (div.find('#data_content').attr('data-extra-data') !== undefined) {
                        $scope.extra_data = JSON.parse(div.find('#data_content').attr('data-extra-data'));
                    }

                    let data_1 = JSON.parse(div.find('#data_content').attr('data-form'));

                    if ($scope.extra_data && $scope.extra_data.transport_times) {
                        let transport_times = $scope.extra_data.transport_times;
                        transport_times.forEach(function (time) {
                            if (new Date() < new Date(time)) {
                                $scope.form.tim_transportation_time = new Date(time);
                            }
                        })
                    }
                    $('.overlay').addClass('hidden');

                    $scope.form = data_1;
                    $scope.form.txt_mobile = 0 + '' + $scope.form.txt_mobile;
                    if (div.find('#data_content').attr('data-institutions-groups') !== undefined) {
                        $scope.institutions_groups = JSON.parse(div.find('#data_content').attr('data-institutions-groups'));
                    }
                    if (div.find('#data_content').attr('data-dual-ativity-page') !== undefined) {
                        $scope.dual_activity_page = true;
                    }
                    //TODO : Making the changes below dynamic.
                    /** Changes below facilitate in changing the date string to Date Object. **/
                    (Object.keys(data_1)).forEach(function (key) {
                        var type = key.split("_")[0];
                        if (type === 'dat') {
                            if ($scope.form[key] !== undefined) {
                                $scope.form[key] = new Date($scope.form[key]);
                            }
                        }
                        if (url === 'Center' && action.toLowerCase() === 'edit') {
                            $scope.form.tim_break_end_hour = new Date($scope.form.tim_break_end_hour);
                            $scope.form.tim_break_start_hour = new Date($scope.form.tim_break_start_hour);
                            $scope.form.tim_closing_hour = new Date($scope.form.tim_closing_hour);
                            $scope.form.tim_opening_hour = new Date($scope.form.tim_opening_hour);
                            $scope.form.tim_departure_time = new Date($scope.form.tim_departure_time);
                        } else if (url === 'Applicants' && action.toLowerCase() === 'edit' || url === 'Application' && action.toLowerCase() === 'edit') {
                            $scope.form.tim_departure_time = new Date($scope.form.tim_departure_time);
                        }
                        if (url === 'WorkingHours' && action.toLowerCase() === 'edit') {
                            $scope.form.from_time = new Date($scope.form.from_time);
                            $scope.form.to_time = new Date($scope.form.to_time);
                        }

                    });
                    if (action == 'Preview_Invoice') {
                        $('.overlay').addClass('hidden');
                        var _width = 800;//$(document).width() / 2 - 200;
                        var _height = 800;//$(document).height();
                        var fileName = fileName = app_url + "/pdf/invoices/" + $scope.form.int_invoice_number + '.pdf';
                        $('div#collapsePanelReport').removeClass('in');
                        $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                        $('#preview_panel').removeClass('hide');
                        var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                        object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                        object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                        object += "</object>";
                        object = object.replace(/{FileName}/g, fileName);
                        //$('#report_preview').css('height', _height - 80 + 'px').html(object);
//                console.log(object);
                        $('#reportPDFPreview').html(object);

                        $scope.ExportTableToPDF();
                        $scope.ReportIsOpen = true;
                    } else {
                        modalInstance = $modal.open({
                            template: $compile(template)($scope),
                            controller: ModalProfileCtrl,
                            windowClass: 'mx-modal-form',
                            scope: $scope
                        });
                        $scope.$apply();
                        $('.overlay').addClass('hidden');
                        modalInstance.opened.then(function () {
                            if (div.find('input[type=file]').length > 0) {
                                $(document).find('input[type=file]').each(function () {
                                    $(this).mxImageUploader();
                                });
                            }
                            if (div.find('textarea[name=tar_sms_content]').length > 0) {
                                $(document).find('textarea[name=tar_sms_content]').height(100).smsArea({maxSmsNum: 3});
                            }
                        }, function () {});
                    }
                });
            }
        };
    $scope.exportExcelData = function (title, data) {
        try {

//           $('#loader').css('display', '');
            console.log(title.toString());
            $('.overlay').removeClass('hidden');
            console.log(data.length);
            var records = JSON.parse(data);
            console.log(records);
            alasql(`SELECT * INTO XLSX("${title}.xlsx",{headers:true}) FROM ?`, [records]);
        } catch (err) {
        } finally {
            $('.overlay').addClass('hidden');
        }
    }
        $scope.generateSampleTransferPDF = function (url, action, params) {
            $('.overlay').removeClass('hidden');
            var params_str = '';
//        $('#loader').css('display', '');
//        $(document).find(".progress").each(function () {
//            $(this).css("visibility", "visible");
//        });
            var msg = '';
            var title = '<b class="notification-title">Report Preview</b>';
            var icon = 'pe-7s-close fa-2x';
            var type = '';
            var data_to_post;
            data_to_post = {'reference': params};

            var _width = 800;//$(document).width() / 2 - 200;
            var _height = 800;//$(document).height();
            var fileName;

//           console.log($scope.ReportOptions.FilterFieldValue);
//url: app_url + "/views/report/reportPDF.php",
            $http({
                method: 'POST',
                url: `${app_url}/${url}/${action.toLowerCase()}`,
                data: data_to_post, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (response) {
                $('.overlay').addClass('hidden');
                var data = JSON.parse($(response).find('#page-content').text());
                if (data.status == 200) {
                    fileName = app_url + "/" + data.file;
                    $('div#collapsePanelReport').removeClass('in');
                    $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                    $('#preview_panel').removeClass('hide');
                    var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                    object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                    object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                    object += "</object>";
                    object = object.replace(/{FileName}/g, fileName);
                    //$('#report_preview').css('height', _height - 80 + 'px').html(object);
//                console.log(object);
                    $('#reportPDFPreview').html(object);

                    $scope.ExportTableToPDF();
                    $scope.ReportIsOpen = true;
                    // Write HTML table section and Display the table to user
//                console.log(data.records);
                } else if (data.status == 100) {
                    $('.notification-area').addClass('alert alert-success').text('No record found');
                } else if (data.status == 209) {
                    $('.notification-area').addClass('alert alert-success').text('Sorry! You can not generate certificate for a positive result test');
                } else {
                    $('.notification-area').addClass('alert alert-success').text('There was an error when generating your Certificate. Please try again later or contact your system administrator for assistance');
                }
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');

                }, 4000);
                $scope.ProcessingData = false;
                $('.overlay').addClass('hidden');
            });
        };

        $scope.generateLabSampleTransferPDF = function (url, action, params) {
            $('.overlay').removeClass('hidden');
            var params_str = '';
//        $('#loader').css('display', '');
//        $(document).find(".progress").each(function () {
//            $(this).css("visibility", "visible");
//        });
            var msg = '';
            var title = '<b class="notification-title">Report Preview</b>';
            var icon = 'pe-7s-close fa-2x';
            var type = '';
            var data_to_post;
            data_to_post = {'reference': params};

            var _width = 800;//$(document).width() / 2 - 200;
            var _height = 800;//$(document).height();
            var fileName;
            $http({
                method: 'POST',
                url: `${app_url}/${url}/${action.toLowerCase()}`,
                data: data_to_post, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (response) {
                $('.overlay').addClass('hidden');
                var data = JSON.parse($(response).find('#page-content').text());
                if (data.status == 200) {
                    $scope.ReportIsOpen = true;
                    $scope.ExportToExcel('labForm', JSON.stringify(data.records));
                } else if (data.status == 100) {
                    msg = '<p class="notification-msg">Sorry! No data available for the selected report options. Please try other options.</p>';
                    type = 'danger';
                    $scope.notify(msg, icon, title, type);
                    $('#noRecordFound').modal();
                } else {
                    $('#report_preview').empty();
                    $('#preview_panel').addClass('hide');
                    msg = '<p class="notification-msg">Sorry! There was an error when generating your report. Please try again later or contact your system administrator for assistance.</p>';
                    type = 'danger';
                    $scope.notify(msg, icon, title, type);
                }
                $(document).find(".progress").each(function () {
                    $(this).css("visibility", "hidden");
                });
            });
        };
        $scope.ExportToExcel = function (titles, data) {
            console.log('sdafsdfsd');
            var records = JSON.parse(data);
            alasql('SELECT * INTO XLSX("output.xlsx",{headers:true}) FROM ?', [records]);
        }
        $scope.ExportTableToPDF = function () {
            //$('#preview_panel #report_preview').removeClass('hide');
            $('#DemoModal').modal('show');
        };
        $scope.showProfileActionForm = function (url, action, params, backdrop = true) {
            $('.overlay').removeClass('hidden');
            var params_str = '';
            for (var i = 0; i < params.length; i++) {
                params_str += '/' + params[i];
            }
            var formURL = `${app_url}/${url}/${action.toLowerCase()}${params_str}`;
            $scope.action_name = action;

            var modalInstance;
            var div = $('<div/>').load(formURL + ' #page-content', function () {
                var template = div.find('#display_content').html();
                try {
                    $scope.dropdowns = JSON.parse(div.find('#data_content').attr('data-dropdowns'));
                    $scope.form = JSON.parse(div.find('#data_content').attr('data-form'));
                    if (div.find('#data_content').attr('data-extra-data') !== undefined) {
                        $scope.extra_data = JSON.parse(div.find('#data_content').attr('data-extra-data'));
                    }

                    modalInstance = $modal.open({
                        template: template,
                        controller: ModalProfileCtrl,
                        windowClass: 'mx-modal-profile-form',
                        scope: $scope,
                        backdrop: 'static',
                    });

                    $('.overlay').addClass('hidden');

                    if (div.find('#data_content').attr('data-disabled') !== undefined) {
                        $scope.disabled = JSON.parse(div.find('#data_content').attr('data-disabled'));
                    }

                    $scope.$apply();

                    if (div.find('input[type=file]').length > 0) {
                        $scope.imageCount = 0;
                        $(document).find('input[type=file]').each(function (key, value) {
                            $scope.imageCount = key;
                            $(this).mxImageUploader();
                        });
                    }

                    angular.forEach($scope.disabled, function (value) {
                        $(document).find('#' + value).parent().parent().css('display', 'none');
                    });

                    if (div.find('#data_content').attr('data-client-functions') !== undefined) {
                        var functions = JSON.parse(div.find('#data_content').attr('data-client-functions'));
                        angular.forEach(functions, function (value) {
                            $scope[value]($scope.form.slabs);
                        });
                    }
                }
                catch (e) {
                    $('.overlay').addClass('hidden');
                }
            });
        };

        //writes slab_commission_data table
        $scope.writeSlabData = function (data) {
            if (data.length > 0) {
                //$scope.form.opt_mx_commission_slab_type_id = data[0].opt_mx_commission_slab_type_id;
                var tbody = '';
                var slab_tbody = $(document).find('table > tbody#slab_commission_data');
                slab_tbody.empty();
                for (var i = 0; i < data.length; i++) {
                    tbody += '<tr><td><input type="number" placeholder="Minimum amount" value="' + data[i].dbl_minimum + '" name="dbl_minimum[]"  class="form-control dbl_minimum" ';
                    tbody += 'ng-class="slab_commission.dbl_minimum.$invalid && !slab_commission.dbl_minimum.$pristine"/></td>';
                    tbody += '<td><input type="number" placeholder="Maximum amount" value="' + data[i].dbl_maximum + '" name="dbl_maximum[]"  class="form-control dbl_maximum"';
                    tbody += 'ng-class="slab_commission.dbl_maximum.$invalid && !slab_commission.dbl_maximum.$pristine" /></td>';
                    tbody += '<td><input type="number" placeholder="Commission amount" value="' + data[i].dbl_commission + '" name="dbl_commission[]"  class="form-control dbl_commission"';
                    tbody += 'ng-class="slab_commission.dbl_commission.$invalid && !slab_commission.dbl_commission.$pristine" /></td>';
                    if (data[i].dbl_base != undefined) {
                        tbody += '<td><input type="number" placeholder="Commission amount" value="' + data[i].dbl_base + '" name="dbl_base[]"  class="form-control dbl_base"';
                        tbody += 'ng-class="slab_commission.dbl_base.$invalid && !slab_commission.dbl_base.$pristine" /></td>';
                    } else {
                        tbody += '<td><input type="number" placeholder="Commission amount" value="" name="dbl_base[]"  class="form-control dbl_base"';
                        tbody += 'ng-class="slab_commission.dbl_base.$invalid && !slab_commission.dbl_base.$pristine" /></td>';
                    }
                    if (data[i].dbl_bank_base != undefined) {
                        tbody += '<td><input type="number" placeholder="Commission amount" value="' + data[i].dbl_bank_base + '" name="dbl_bank_base[]"  class="form-control dbl_bank_base"';
                        tbody += 'ng-class="slab_commission.dbl_bank_base.$invalid && !slab_commission.dbl_bank_base.$pristine" /></td>';
                    } else {
                        tbody += '<td><input type="number" placeholder="Commission amount" value="" name="dbl_base[]"  class="form-control dbl_base"';
                        tbody += 'ng-class="slab_commission.dbl_base.$invalid && !slab_commission.dbl_base.$pristine" /></td>';
                    }
                    tbody += '<td><button type="button" class="btn btn-default btn-sm commission-slab-adder"><i class="fa fa-plus fa-fw"></i> Next Slab</button>';
                    if (i > 0) {
                        tbody += ' <button type="button" class="btn btn-danger btn-sm commission-slab-remover"><i class="fa fa-minus fa-fw"></i> Remove</button>';
                    } else {
                        tbody += ' <button type="button" class="btn btn-danger btn-sm commission-slab-remover hidden"><i class="fa fa-minus fa-fw"></i> Remove</button>';
                    }
                    tbody += '</td></tr>';
                }
                slab_tbody.append(tbody);
            }
        };

        $scope.writeAccountData = function (response) {
            var account_data = response.data;
            $scope.form.txt_name = account_data.txt_name;
            $scope.form.txt_account_number = account_data.txt_account_number;
        };

        $scope.capitalize = function (_url) {
            if (_url.charAt(0) == '/' || _url.charAt(0) == '\\')
                return (_url.charAt(0) + _url.charAt(1).toUpperCase() + _url.slice(2));
            else
                return (_url.charAt(0).toUpperCase() + _url.slice(1));
        };

        $scope.confirmPasswordReset = function (target) {
            $modal.open({
                templateUrl: app_url + "/views/" + target + "/reset_password.html",
                controller: ModalProfileCtrl,
                windowClass: 'mx-modal-profile-form',
                scope: $scope
            });
        };

        $scope.lowercase = function (url) {
            return url.toLowerCase();
        };

        $scope.getActionName = function (action_name) {
            var _act = '';
            if (action_name === "Cancel") {
                _act = 'cancelled';
            } else {
                _act = action_name + (action_name[action_name.length - 1] == 'e' ? 'd' : 'ed');
            }
            return _act;
        };

        $scope.approveTransaction = function (id, state) {
            $('.overlay').removeClass('hidden');
            $scope.ProcessingData = true;
            $http({
                method: 'POST',
                url: `${app_url}/Transaction/approve_transaction`,
                data: {'id': id, 'state': state}
            }).then(function (response) {
                $('.overlay').addClass('hidden');
                $scope.ProcessingData = false;
                let values = JSON.parse($(response.data).find('#mabrexPageContent').text().trim());

                if (values.status === 200) {
                    $('.float-approval-message').addClass('alert-success').text('Operation was successfully performed');
                } else if (values.status === 100) {
                    $('.float-approval-message').addClass('alert-danger').text('There was an error performing the requested operation');
                } else {
                    $('.float-approval-message').addClass('alert-info').text('Unknown issue has occurred');
                }
                setTimeout(() =>
                {
                    location.reload();
                }, 3200);
            }, function (response) {
                $('.float-approval-message').addClass('alert-danger').text(response);
            });
        };

        //called when record row clicked to open the profile modal
        $scope.redirectToApproval = function (token) {
            window.open(`${app_url}/Notifications/index?token=${btoa(token)}`, "_self");
        };

        $scope.approveDual = function (token, state, model, p_data, added_by, added_date, account = null, reason = null) {
            $('.overlay').removeClass('hidden');
            $scope.ProcessingData = true;
            let posted_data = (JSON.parse(p_data));
            posted_data['added_by'] = added_by;
            posted_data['date'] = added_date;
            let data_o = {"token": token, "model": model};
            angular.extend(posted_data, data_o);
            if (account) {
                posted_data['account'] = account;
            }
            if (reason) {
                posted_data['tar_reason'] = reason;
            }
            $http({
                method: 'POST',
                url: `${app_url}/${model}/${state}`,
                data: posted_data
            }).then(function (response) {
                $('.overlay').addClass('hidden');
                $scope.ProcessingData = false;

                let values = JSON.parse($(response.data).find('#mabrexPageContent').text().trim());
                if (values === 200) {
                    $('.float-approval-message').addClass('alert-success').text('Operation was successfully performed');
                } else if (values === 100) {
                    $('.float-approval-message').addClass('alert-danger').text('There was an error performing the requested operation');
                } else if (values === 300) {
                    $('.notification-area').addClass('alert alert-info').text("Your request has failed. Account(s) Already Exist.");
                } else if (values === 201) {
                    $('.float-approval-message').addClass('alert-success').text('Operation was successfully performed');
                } else if (values === 600) {
                    $('.float-approval-message').addClass('alert-info').text('Subscriber Already Exists with this mobile number');
                } else {
                    $('#approvalFailed').modal();
                }
                setTimeout(function () {
                    $('.float-approval-message').removeClass('alert alert-success').text('');
                    location.reload();
                }, 4000);

                $('#approvalFailed').on('hidden.bs.modal', function () {
                    location.reload();
                })
            }, function (response) {
                $('.float-approval-message').addClass('alert-danger').text('Failed Operation');
            });
        };

        $scope.getTodaysTime = function () {
            var date = new Date();
            return new Date(
                    date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + ' ' + date.getHours() + ':' + date.getMinutes()
                    )
        }

        $scope.getTodaysDate = function () {
            var date = new Date();
            return new Date(
                    date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate()
                    )
        }

        //Draw Business Profile charts
    var element3 = document.getElementById('nation-area-demo');

    if (element3 !== null) {
        var config5 = {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [$scope.initial_tab_data.applicant_info.zanzibaris,$scope.initial_tab_data.applicant_info.mainlanders,$scope.initial_tab_data.applicant_info.foreigners],
                    backgroundColor: [
                        '#bb5d16',
                        '#6b9080',
                        '#162667',
                    ],
                    label: 'Business Types',
                    hoverOffset: 4
                }],
                labels: ['Zanzibaris','Mainlands','Foreigners'],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: true,
                    position: 'left',
                },
                title: {
                    display: false,
                    text: 'Tickets by Status'
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 0
                },
                plugins: {
                    legend: {
                        display: false,
                        position: 'left',
                    },
                    title: {
                        display: false,
                        text: 'Tickets summary'
                    }
                }
            }
        };

        var ctx5 = element3.getContext('2d');
        new Chart(ctx5, config5);
    }

    var element4 = document.getElementById('job-area-demo');

    if (element4 !== null) {

        var config6 = {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [$scope.initial_tab_data.applicant_info.management_total,$scope.initial_tab_data.applicant_info.skilled_total,$scope.initial_tab_data.applicant_info.un_skilled_total],
                    backgroundColor: [
                        '#6a4c93',
                        '#e36414',
                        '#f15bb5',
                    ],
                    label: 'Business Types',
                    hoverOffset: 4
                }],
                labels: ['Management','Skilled','Unskilled']
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: true,
                    position: 'left',
                },
                title: {
                    display: false,
                    text: 'Tickets by Status'
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 0
                },
                plugins: {
                    legend: {
                        display: false,
                        position: 'left',
                    },
                    title: {
                        display: false,
                        text: 'Tickets summary'
                    }
                }
            }
        };

        var ctx6 = element4.getContext('2d');
        new Chart(ctx6, config6);
    }

    var element5 = document.getElementById('gender-area-demo');

    if (element5 !== null) {

        var config7 = {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [$scope.initial_tab_data.applicant_info.males,$scope.initial_tab_data.applicant_info.females],
                    backgroundColor: [
                        '#118ab2',
                        '#83c5be',
                    ],
                    label: 'Business Types',
                    hoverOffset: 4
                }],
                labels: ['Male','Female']
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false,
                    position: 'left',
                },
                title: {
                    display: false,
                    text: 'Tickets by Status'
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 0
                },
                plugins: {
                    legend: {
                        display: false,
                        position: 'left',
                    },
                    title: {
                        display: false,
                        text: 'Tickets summary'
                    }
                }
            }
        };

        var ctx7 = element5.getContext('2d');
        new Chart(ctx7, config7);
    }
    }]);

//this function is for modal controller called in modal
var ModalProfileCtrl = function ($scope, $modalInstance, $http, $compile, $interval, $filter, $sce) {
    //to close modal
    // $scope.form.investigation_team = [];
    // $scope.form.allowed_evidences = [];
    $scope.investigation_team_data = new Set();
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
        // location.reload();
        // if ($scope.device_status_interval != null) {
        //     clearInterval($scope.device_status_interval);
        // }
    };

    $scope.checkPhoneNumber = function (id) {
        var $mobile = $(`#${id}`).val();
        if ($mobile.length === 10 && $mobile.substr(0, 1) === '0') {
            $(document).find('div.' + id + '_error').text('');
            return false;
        } else {
            $(document).find('div.' + id + '_error').text('Phone number must be 10 digits in the format 07XXXXXXXX or 06XXXXXXXX');
            return true;
        }
    };

    $scope.saveProfileOperation = function (url, action) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = app_url + "/" + url + "/" + action + "/";
        if ($scope.form.opt_mx_commission_type_id !== undefined && $scope.form.opt_mx_commission_type_id.name === 'Slab') {
            $scope.extractCommissionData();
        }
        if ($scope.form.has_extra === 1) {
            $scope.configureExtraData(action);
        }
        if (action === "visaSubscription" || action === "unsubscribeVisa" || action === "post_reset_pin" || action === 'resetSubscriberImsi') {
            $scope.form.id = $scope.parent_id;
        } else if (action === "saveIncident") {
            $scope.form.opt_mx_subscriber_id = $scope.parent_id;
        } else if (action === "changeSubscriberMainAccount" || action === "saveNewSubscriberClass" || action === 'activateSubscriberScheme') {
            $scope.form.subscriber_id = $scope.parent_id;
        }
        if ($scope.dual_activity_page === true) {
            $scope.extractDualActivityData();
        }

        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(response);
        }).finally(function () {
            $scope.ProcessingData = false;
        });
    };

    $scope.saveFormWithUploads = function (url, action, uploads) {
        var post_url = app_url + "/" + url + "/" + action + "/";
        
        var formdata = new FormData();
        angular.forEach($scope.form, function (value, key) {
            formdata.append(key, value);
        });

        if (url === 'Property' && action === 'post_upload_images') {
            $scope.processPropertyData(formdata);
        }else if (uploads.length > 0) {
            angular.forEach(uploads, function (value) {
                var element = document.getElementById(value);
                if (element != null) {
                    var file = element.files[0];
                    formdata.append(value, file);
                }
            });
        }

        $http({
            method: 'POST',
            url: post_url,
            data: formdata,
            headers: {'Content-Type': undefined}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(data);
            $scope.ProcessingData = false;
        });
    };

    $scope.processPropertyData = function (formdata) {
        var uploads = [];
        var counter = 0;
        var container = $(document).find('tbody#uploads_table');
        container.children('tr').each(function () {
            var row = $(this);
            var data = {};
            row.children('td.input_cell').each(function () {
                var control = $(this).find('[data-input]');
                var value = control.val();
                var label = control.attr('data-input');
                if (label == 'txt_image_url') {
                    var td = $(this);
                    td.children('input[type=file]').each(function () {
                        var element = this;
                        var file = element.files[0];
                        data[label + '_' + counter] = file;
                        formdata.append(label + '_' + counter, file);
                    })
                } else {
                    data[label] = value;
                }
            });
            counter += 1;
            uploads.push(data);
        });
        formdata.append('uploads', JSON.stringify(uploads));
    }

    $scope.extractCommissionData = function () {
        var commission = [];
        var base = 0;
        var bank_base = 0;
        var container = $(document).find('tbody#slab_commission_data');
        container.children('tr').each(function () {
            var min = $(this).find('input.dbl_minimum').val();
            var max = $(this).find('input.dbl_maximum').val();
            var amount = $(this).find('input.dbl_commission').val();
//            var amount = $(this).find('input.dbl_commission').val();
//            if ($(this).find('input.dbl_base').val() !==undefined && $(this).find('input.dbl_bank_base').val() !==undefined){
            base = $(this).find('input.dbl_base').val();
            bank_base = $(this).find('input.dbl_bank_base').val();
            commission.push({'dbl_minimum': min, 'dbl_maximum': max, 'dbl_commission': amount, 'dbl_base': base, 'dbl_bank_base': bank_base});
//            }else{
//                commission.push({'dbl_minimum': min, 'dbl_maximum': max, 'dbl_commission': amount});
//            }

        });
        $scope.form.slabs = JSON.stringify(commission);
    };

    $scope.configureExtraData = function (action) {
        switch (action) {
            case 'post_register_account':
                var account = [];
                var container = $(document).find('tbody#account_table');
                container.children('tr').each(function () {
                    var row = $(this);
                    var data = {};
                    row.children('td.input_cell').each(function () {
                        var control = $(this).find('[data-input]');
                        var value = control.val();
                        var label = control.attr('data-input');
                        data[label] = value;

                    });
                    account.push(data);
                });
                $scope.form.account = JSON.stringify(account);
                break;
            default:
                break;
        }
    };

    $scope.extractDualActivityData = function () {
        var section = $(document).find('div#dual_activity_section');
        var data = [];
        section.children('div').each(function () {
            var input = $(this).find('select');
            if (input.val().length > 0) {
                data.push({'institution': input.attr('data-institution'), 'group': input.val()});
            }
        });
        $scope.form.groups = data;
    };

    $scope.initiateExtra = function () {

        if ($scope.form.has_extra === 1) {
            $scope.form.chkselct = true;

            //initiate extra
            if ($scope.url.toLowerCase() == 'subscriber' && $scope.form.txt_action == 'save') {
                var account_data = JSON.parse($scope.form.account);

                if (account_data.length > 0) {
                    //handle first tr
                    var last_row = angular.element(document.querySelector('#account_table tr')).last();
                    last_row.find('.account_name').val(account_data[0].txt_account_name);
                    last_row.find('.account_number').val(account_data[0].txt_account_number);

                    //handle other tr
                    for (var i = 1; i < account_data.length; i++) {

                        var my_account = angular.element(document.querySelector('.account-adder'));
                        var last_row = my_account.trigger('click').parent().parent().parent().find('tr').last();

                        last_row.find('.account_name').val(account_data[i].txt_account_name);
                        last_row.find('.account_number').val(account_data[i].txt_account_number);
                    }
                }
            }
        }
    }

    $scope.saveProfileOperationWithUploads = function (url, action, uploads) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = app_url + "/" + url + "/" + action + "/";
        if ($scope.form.dat_birth_date) {
            $scope.form.dat_birth_date = new Date($scope.form.dat_birth_date).toISOString();
        }

        var formdata = new FormData();
        angular.forEach($scope.form, function (value, key) {
            formdata.append(key, value);
        });
        if (uploads.length > 0) {
            angular.forEach(uploads, function (value) {
                var element = document.getElementById(value);
                if (element != null) {
                    var file = element.files[0];
                    formdata.append(value, file);
                }
            });
        }
        if ($scope.form.has_extra === 1) {
            $scope.configureExtraData(action);
        }

        $http({
            method: 'POST',
            url: post_url,
            data: formdata,
            headers: {'Content-Type': undefined}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(data);
            // if (data.errors) {
            //     $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            // } else {
            //     var response = $(data).find('#mabrexPageContent').text().trim();
            //
            //     if (response === '200' || response === '201') {
            //         $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
            //     } else if (response === '2903' || response === 2903) {
            //         status_color = 'danger';
            //         message = "Your request has failed. You can only use one receipt per application.";
            //     } else {
            //         $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
            //     }
            //     setTimeout(function () {
            //         $('.notification-area').removeClass('alert alert-success').text('');
            //         $modalInstance.dismiss('cancel');
            //         if (response === '201') {
            //             location.reload();
            //         }
            //     }, 4000);
            // }
        }).finally(function () {
            $scope.ProcessingData = false;
        });
    };

    $scope.generateReport = function () {
        $('.overlay').removeClass('hidden');
        $('#loader').css('display', '');
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var from_date = $scope.formatDate($scope.frmData['from_date']);
        var to_date = $scope.formatDate($scope.frmData['to_date']);
        var record_id = $scope.frmData['record_id'];
        var data_to_post = {'record_id': record_id, 'from_date': from_date, 'to_date': to_date};

        var _width = 900; //$(document).width();
        var _height = 800; //$(document).height();
        var fileName = app_url + "/pdf/tmp/report.pdf";
        $http({
            method: 'POST',
            url: app_url + "/views/applicant/applicant_report.php",
            data: data_to_post, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            if (response.status == 200) {
                $('.overlay').addClass('hidden');
                $(document).find('div#range').hide();
                $(document).find('div#range2').removeClass('hide');
                $(document).find('div#profile_preview_panel').removeClass('hide');
                var object = "<object data=\"{FileName}#toolbar=1\" type=\"application/pdf\" width=\"" + _width + "px\" height=\"" + (_height - 100) + "px\">";
                object += "If you are unable to view file, you can download from <a href = \"{FileName}\">here</a>";
                object += " or download <a target = \"_blank\" href = \"http://get.adobe.com/reader/\">Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                $(document).find('div#profile_report_preview').css('height', _height - 80 + 'px').html(object);
            } else if (response.status == 100) {
                (document).find('div#profile_report_preview').empty();
                (document).find('div#profile_preview_panel').addClass('hide');
                msg = '<p class="notification-msg">Sorry! There is no data available.</p>';
                type = 'danger';
                $scope.notify(msg, icon, title, type);
            } else {
                (document).find('div#profile_report_preview').empty();
                (document).find('div#profile_preview_panel').addClass('hide');
                msg = '<p class="notification-msg">Sorry! Something went wrong.</p>';
                type = 'danger';
                $scope.notify(msg, icon, title, type);
            }
            $('#loader').css('display', 'none');
        });
    };

    $scope.formatDate = function (dateString) {
        var date = dateString.getDate();
        var month = dateString.getMonth() + 1;
        var year = dateString.getFullYear();
        if (date < 10) {
            date = '0' + date;
        }
        if (month < 10) {
            month = '0' + month;
        }
        dateString = year + '-' + month + '-' + date;
        return dateString;
    };

    $scope.hideDiv = function () {

        $(document).find('div#range2').addClass('hide');
        $(document).find('div#range').show();
    };

    String.prototype.capitalize = function () {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };

    //Printing Test of Card

    $scope.target_template = null;
    $scope.selectCardTemplate = function (value) {
        $scope.target_template = value;
    };

    $scope.printCard = function (card,rootPath) {
        console.log(rootPath);
        $('#vaccine_card_form').css('display', 'none');
        $('#confirmPrintStatus').css('display', 'block');
        $scope.form = card;
        if ($scope.form['Name'].length > 29) {
            $scope.form.personFontSize = 8
        } else if ($scope.form['Name'].length > 26) {
            $scope.form.personFontSize = 9
        } else if ($scope.form['Name'].length > 23) {
            $scope.form.personFontSize = 10
        } else if ($scope.form['Name'].length > 20) {
            $scope.form.personFontSize = 11
        } else if ($scope.form['Name'].length > 17) {
            $scope.form.personFontSize = 12
        } else if ($scope.form['Name'].length > 14) {
            $scope.form.personFontSize = 13
        } else if ($scope.form['Name'].length > 11) {
            $scope.form.personFontSize = 14
        } else if ($scope.form['Name'].length > 8) {
            $scope.form.personFontSize = 15
        }

        var content = $('#cardPrintableArea');
        content.load(`${app_url}/sid_template/sid_card.php`, function () {
            $compile(content.contents())($scope);
            $scope.$apply();
            newWin = window.open("");
            var css = '@page {size: landscape; font-size: 8pt; margin: 0;}';
            var head = newWin.document.head || newWin.document.getElementsByTagName('head')[0];
            var style = newWin.document.createElement('style');
            style.type = 'text/css';
            style.media = 'print';
            if (style.styleSheet) {
                style.styleSheet.cssText = css;
            } else {
                style.appendChild(newWin.document.createTextNode(css));
            }

            head.appendChild(style);
            // $scope.generateBarcode(content.find('#barcode'));
            newWin.document.write(content.html());
            setTimeout(function () {
                newWin.print();
                newWin.close();
                // $scope.updateCardPrintCount(card);
            }, 300);
        });

        // $('#cardTemplateSelector').modal('show');
        // $('#cardTemplateSelector').on('hidden.bs.modal', function (e) {
        //     if ($scope.target_template === null) {
        //         return;
        //     }
        //
        //     var content = $('#cardPrintableArea');
        //
        // });
    };

    // $scope.printStatus = function (status,id) {
    //     $scope.ProcessingData = true;
    //     if (status){
    //         $http({
    //             method: 'POST',
    //             url: `${app_url}/Vaccination/post_print_card`,
    //             data: {'id': id},
    //             headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    //         }).success(function (data) {
    //             // console.log(data);
    //             // $scope.showProfileActionForm(url, action, params, 'static');
    //             $scope.ProcessingData = false;
    //         }).finally(function () {
    //             $scope.ProcessingData = false;
    //         });
    //     }
    //     else {
    //         // console.log("Printing Confirmed, fail by user.")
    //     }
    // };

    $scope.pushNotification = function (url, action) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        $('.overlay').removeClass('hidden');
        var post_url = `${action}`
        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $('#pushModal').modal('hide');
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
                $scope.ProcessingData = false;
            } else {
                var response = JSON.parse($(data).find('#mabrexPageContent').text().trim());
                if (response.response.code === 200) {
                    $('.notification-area').addClass('alert alert-success').text(`${response.response.message}`);
                } else if (response.response.code === 100) {
                    $('.notification-area').addClass('alert alert-danger').text(`${response.response.message[0]}`);
                } else if (response.response.code === 103) {
                    $('.notification-area').addClass('alert alert-info').text(`${response.response.message[0]}`);
                } else if (response === '901') {
                    $('.notification-area').addClass('alert alert-info').text("Your request has failed. Mobile Number Already Exist.");
                } else if (response === '903') {
                    $('.notification-area').addClass('alert alert-info').text("Your request has failed. Email AND Mobile Number Already Exist.");
                } else {
                    $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
                }
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');
                    $modalInstance.dismiss('cancel');
                    if (response === '200' || response.response.code === 200) {
                        location.reload();
                    } else {
                        if ($scope.is_profile_tab === true) {
                            $scope.getProfileRecords($scope.current_tab, $scope.parent_id);
                        } else {
                            $scope.getAssociatedRecords($scope.current_tab, $scope.parent_id);
                        }
                    }
                }, 2000);
                $scope.ProcessingData = false;
                $('.overlay').addClass('hidden');
            }
        }).finally(function () {
            $scope.ProcessingData = false;
        });
    }

    $scope.checkSelectedComplaintReason = function () {
        $scope.complaint_reason_other = false;
        let reason_id = $scope.form.opt_mx_complaint_dismissal_reason_id;
        let reasons = $scope.dropdowns.opt_mx_complaint_dismissal_reason_ids;
        reasons.forEach(function (data) {
            if (data.id === reason_id && data.name === 'Other') {
                $scope.complaint_reason_other = true;
            }
        })
    };

    $scope.goToBlock = (to, from) => {
        setTimeout(() => {
            $("#" + from).css('display', 'none');
            $("#" + to).css('display', 'block');
        }, 300);
    };

    $scope.addOfficer = () => {
        let selected_officer = $scope.form.officer_member;
        let officers = $scope.dropdowns.officers_ids;
        if ($scope.form.investigation_team === undefined) {
            $scope.form.investigation_team = [];
        }
        if ($scope.form.allowed_evidences === undefined) {
            $scope.form.allowed_evidences = [];
        }
        officers.forEach(function (officer) {
            if (officer.id === selected_officer && !$scope.investigation_team_data.has(officer.id)) {
                $scope.investigation_team_data.add(officer.id);
                $scope.form.investigation_team.push({
                    name: officer.name,
                    id: officer.id
                });
            }
        });
    };

    $scope.removeAddedOfficer = (id) => {
        const index = [...$scope.investigation_team_data].indexOf(id);
        if (index > -1) {
            $scope.investigation_team_data.delete(id);
            $scope.form.investigation_team.splice(index, 1);
        }
    };

    $scope.removeOfficer = () => {
        let removal_reason = $scope.form.change_reason;
        if ($scope.officer_removed_index > -1 && $scope.officer_removed !== null) {
            let index = $scope.officer_removed_index;
            $scope.form.investigation_team.splice(index, 1);

            if ($scope.form.removed_investigation_team_members === undefined) {
                $scope.form.removed_investigation_team_members = [];
            }
            $scope.form.removed_investigation_team_members.push({
                id: $scope.officer_removed,
                name: $scope.officer_removed_name,
                reason: removal_reason
            });
            $scope.form.change_reason = '';
        }
    };

    $scope.removingOfficer = (id) => {
        $scope.officer_removed = id;
        $scope.form.change_reason = '';
        $scope.form.investigation_team.forEach(function (officer, index) {
            if (officer.id === id) {
                $scope.officer_removed_index = index;
                $scope.officer_removed_name = officer.name;
                return true;
            }
        });
    };

    $scope.cancelRemoveOfficer = (id) => {
        $scope.officer_removed = id;
        $scope.form.removed_investigation_team_members.forEach(function (officer, index) {
            if (officer.id === id) {
                $scope.form.investigation_team.push({
                    id: officer.id,
                    name: officer.name,
                });
                $scope.form.removed_investigation_team_members.splice(index, 1);
                return true;
            }
        });
    };

    $scope.confirmEvidencePresence = (name, id) => {
        return new Promise((resolve, reject) => {
            $http({
                method: 'POST',
                url: '/Complaint/check_evidence',
                data: {file_name: name, id: id},
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (data) {
                var response = $(data).find('#mabrexPageContent').text().trim();
                resolve(true);
            }).error(function (error) {
                reject(false);
            });
        });
    };

    $scope.setEvidenceLink = (evidence) => {
        let url = "/uploads/applications/";
        let name = evidence.name;
        let id = evidence.id;
        $scope.confirmEvidencePresence(name, id)
                .then(data => {
                    $scope.selected_evidence = evidence.id;
                    $scope.evidence_status = evidence.selected;

                    $scope.evidence_link = $sce.trustAsResourceUrl(url + name);
                })
                .catch(error => {
                    $scope.evidence_link = $sce.trustAsResourceUrl(url + 'no-file.html');
                });
    };

    $scope.checkEvidenceLink = (evidence) => {
        let response = $scope.confirmEvidencePresence(evidence);

        if (response) {
            return evidence;
        }

        return 'no-file.html';
    };

    $scope.trustSrc = function (src) {
        let array = src.split('=');

        if (array.length > 1) {
            let url = "https://uzalendo.rahisi.co.tz/play2.php?filename=" + array[1];
            return $sce.trustAsResourceUrl(url);
        }

    }

    $scope.generateSubmissionForm = function (url, action, params) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
//        $('#loader').css('display', '');
//        $(document).find(".progress").each(function () {
//            $(this).css("visibility", "visible");
//        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var data_to_post;
        for (var i = 0; i < params.length; i++) {
            data_to_post = {'id': params[i]};
        }
        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName = '';
//           console.log($scope.ReportOptions.FilterFieldValue);
//url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: `${app_url}/${url}/${action.toLowerCase()}`,
            data: data_to_post, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            var data = JSON.parse($(response).find('#page-content').text());
            if (data.status == 200) {
                fileName = app_url + '/' + data.file;
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);
//                console.log(object);
                $('#reportPDFPreview').html(object);

                $scope.ExportTableToPDF();
                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
//                console.log(data.records);
            } else if (data.status == 100) {
                $('.notification-area').addClass('alert alert-success').text('No record found');
            } else if (data.status == 209) {
                $('.notification-area').addClass('alert alert-success').text('Sorry! You can not generate certificate for a positive result test');
            } else {
                $('.notification-area').addClass('alert alert-success').text('There was an error when generating your Certificate. Please try again later or contact your system administrator for assistance');
            }
            setTimeout(function () {
                $('.notification-area').removeClass('alert alert-success').text('');
            }, 2000);
            $scope.ProcessingData = false;
            $('.overlay').addClass('hidden');
        });
    };
    $scope.generateCertificate = function (url, action, params) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
//        $('#loader').css('display', '');
//        $(document).find(".progress").each(function () {
//            $(this).css("visibility", "visible");
//        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var data_to_post;
//        for (var i = 0; i < params.length; i++) {
//            data_to_post = {'id': params[i]};
//        }
        data_to_post = params[0]

        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName;

//           console.log($scope.ReportOptions.FilterFieldValue);
//url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: `${app_url}/${url}/${action.toLowerCase()}`,
            data: data_to_post, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            var data = JSON.parse($(response).find('#page-content').text());
            if (data.status == 200) {
                fileName = app_url + "/pdf/tmp/" + data.file;
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);
//                console.log(object);
                $('#reportPDFPreview').html(object);

                $scope.ExportTableToPDF();
                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
//                console.log(data.records);
            } else if (data.status == 100) {
                $('.notification-area').addClass('alert alert-success').text('No record found');
            } else if (data.status == 209) {
                $('.notification-area').addClass('alert alert-success').text('Sorry! You can not generate certificate for a positive result test');
            } else {
                $('.notification-area').addClass('alert alert-success').text('There was an error when generating your Certificate. Please try again later or contact your system administrator for assistance');
            }
            setTimeout(function () {
                $('.notification-area').removeClass('alert alert-success').text('');

            }, 4000);
            $scope.ProcessingData = false;
            $('.overlay').addClass('hidden');
        });
    };
    $scope.generateApplication = function (url, action, params) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
//        $('#loader').css('display', '');
//        $(document).find(".progress").each(function () {
//            $(this).css("visibility", "visible");
//        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var data_to_post = params[0];

        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName = app_url + "/pdf/tmp/application.pdf";

//           console.log($scope.ReportOptions.FilterFieldValue);
//url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: `${app_url}/${url}/${action.toLowerCase()}`,
            data: data_to_post, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            var data = JSON.parse($(response).find('#page-content').text());
            if (data.status == 200) {
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);
//                console.log(object);
                $('#reportPDFPreview').html(object);

                $scope.ExportTableToPDF();
                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
//                console.log(data.records);
            } else if (data.status == 100) {
                $('#report_preview').empty();
                $('#preview_panel').addClass('hide');
//                msg = '<p class="notification-msg">Sorry! No data available for the selected report options. Please try other options.</p>';
//                type = 'info';
//                $scope.notify(msg, icon, title, type);
                $('#noRecordFound').modal();
            } else {
                $('#report_preview').empty();
                $('#preview_panel').addClass('hide');
                msg = '<p class="notification-msg">Sorry! There was an error when generating your report. Please try again later or contact your system administrator for assistance.</p>';
                type = 'danger';
                $scope.notify(msg, icon, title, type);
            }
            $(document).find(".progress").each(function () {
                $(this).css("visibility", "hidden");
            });
        });
    };
    $scope.ExportTableToPDF = function () {
        //$('#preview_panel #report_preview').removeClass('hide');
        $('#DemoModal').modal('show');
    };

    $scope.toggleSymptoms = (name) => {
        // var $scope.form.symptoms = $scope.form.symptoms;
        var symptoms = $scope.extra_data.symptoms;

        for (let i = 0; i < symptoms.length; i++) {
            let symptom = symptoms[i];

            if (symptom['txt_name'] === name) {
                if ($scope.form.symptoms.includes(name)) {
                    symptom.selected = false;
                    let index = $scope.form.symptoms.indexOf(name);
                    $scope.form.symptoms.splice(index, 1);
                } else {
                    $scope.form.symptoms.push(name);
                    symptom.selected = true;
                }
                break;
            }
        }
    };

    $scope.checkSymptomType = function () {
        $scope.form.symptoms = [];
        $symptom_types = $scope.dropdowns.opt_mx_application_symptom_type_ids;
        $symptom_type_id = $scope.form.opt_mx_application_symptom_type_id;

        $symptom_types.forEach(function (type) {
            if (type.id === $symptom_type_id) {
                $scope.symptom_type = type.name;
            }
        });
    }

    $scope.checkAnatomicalSite = function () {
        $specimen_natures = $scope.extra_data.specimen_natures;
        $anatomical_site_id = $scope.form.opt_mx_anatomical_site_id;

        $specimen_natures.forEach(function (type) {
            if (type.anatomical_site_id === $anatomical_site_id) {
                $scope.specimen_nature = type['Specimen Nature'];
                $scope.form.opt_mx_specimen_nature_id = type['specimen_nature_id'];
            }
        });
    }

    $scope.calculatePaymentAmount = function () {
        var selected = $filter('filter')($scope.dropdowns.txt_currency_ids, {id: $scope.form.txt_currency})[0];
        if (selected != undefined) {
            if ($scope.form.original_currency != selected.name) {
                if ($scope.form.original_currency == 'TZS') {
                    $scope.form.dbl_amount = $scope.form.original_amount / $scope.form.local_rate;
                } else {
                    $scope.form.dbl_amount = $scope.form.original_amount * selected.rate;
                }
            } else {
                $scope.form.dbl_amount = $scope.form.original_amount;
            }
            //$scope.form.dbl_amount = 
        }
    };

    $scope.rotateImage = function (loc) {
        $http({
            method: 'POST',
            url: '/Application/rotate_image',
            data: {file_name: loc},
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            var response = $(data).find('#mabrexPageContent').text().trim();
            console.log(response);
        }).error(function (error) {
            console.log(error);
        });
    };
    $scope.stepper = 1;
    $scope.nextStep = function () {
        $scope.stepper++;
    }

    $scope.prevStep = function () {
        $scope.stepper--;
    }
    $scope.getAvailability = function () {
        $('.overlay').removeClass('hidden');
        console.log($scope.form);
        if (($scope.form.dat_test_date != null || $scope.form.dat_test_date != undefined) && ($scope.form.opt_mx_center_id != null || $scope.form.opt_mx_center_id != undefined)) {
            var data_to_post = {'dat_test_date': $scope.form.dat_test_date, 'opt_mx_center_id': $scope.form.opt_mx_center_id};
            console.log(data_to_post);
            $http({
                method: 'POST',
                url: `${app_url}/Application/getAvailability`,
                data: data_to_post, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                $('.overlay').addClass('hidden');
                $scope.availability = JSON.parse($(response.data).find('#mabrexPageContent').text());
                $scope.slots_available = $scope.availability['availability']
//                $scope.user_group_flag = true;

                localStorage.setItem('slots', JSON.stringify($scope.slots_available));
//               $scope.slots_available.forEach(function (item) {
//                        console.log(item)
//                    });
                $scope.addSlots($scope.slots_available);
            });
        } else {
            $scope.availability = {};
        }
    }
    $scope.get_slots = function () {
        $scope.slots_available = localStorage.getItem('slots');
        console.log('asdf');
    }
    $scope.addSlots = function (slots) {
        var tbody = '';
        var slab_tbody = $(document).find('div > #slots');
        console.log(slab_tbody)
        slab_tbody.empty();
        tbody += '<div class="col-lg-9">';
        for (var i = 0; i < slots.length; i++) {
            tbody += ` <div ng-class="{'col-md-2 col-sm-4': slots_available.length > 5,
                                                 'col-sm-4 col-md-6': slots_available.length < 2,
                                                 'col-sm-4 col-md-2': slots_available.length <= 5}">`;
            tbody += ` <a type="button" ng-click="getCenter(slots_available[${i}].slot_id,slots_available[${i}].test_time)">`
            tbody += '<div style="border: solid #007BFF 1px; width: auto; height: auto; margin-bottom: 10px;padding:15px; color: #007BFF; font-weight: bold; transition: all 0.3s ease-in-out;text-align: center;border-top-left-radius: 0.25rem !important;'
            tbody += ' border-top-right-radius: 0.25rem !important; border-bottom-right-radius: 0.25rem !important; border-bottom-left-radius: 0.25rem !important;">'

            tbody += $scope.slots_available[i]['test_time'] + '</div></a> </div>'
        }
        tbody += "</div>";
        slab_tbody.html($compile(tbody)($scope));

    }
    $scope.getCenter = function (slot_id, test_time) {
        $scope.form.slot_id = slot_id;
        $scope.form.test_time = test_time;
        console.log($scope.form)
    }

    $scope.responseHandler = function (response, reload = true) {
        console.log(response);

        let code = Number(response.code);
        let status = response.status;
        let message = response.message;

        $scope.ProcessingData = !(status === undefined || status == false);

        if (code === 200 || code === 201) {
            $('.notification-area').addClass('alert alert-success').html(message);
        } else if (code === 220) {
            $('.notification-area').addClass('alert alert-info').html(message);
        } else {
            $('.notification-area').addClass('alert alert-danger').html(message);
        }

        setTimeout(function () {
            $('.notification-area').removeClass('alert alert-success alert-danger alert-info').html('');
            if (code === 200 || code === 201) {
                $modalInstance.dismiss('cancel');
                if($scope.current_tab && $scope.parent_id) {
                    if (!$scope.is_profile_tab) {
                        $scope.getAssociatedRecords($scope.current_tab, $scope.parent_id);
                        $scope.fetchProfile($scope.parent_id)
                    } else {
                        $scope.getProfileRecords($scope.current_tab, $scope.parent_id);
                    }
                } else {
                    if (reload) {
                        location.reload();
                    }
                }
            }
        }, 2000);
    };
    $scope.associatedShowProfile = function (url, id) {
        $scope.showProfile(url, id);
        $scope.cancel();
    }
    $scope.checkCategories = function () {
        let _true = 0;
        if($scope.form.service_categories) {
            Object.keys($scope.form.service_categories).forEach( (item, index) => {
                if ($scope.form.service_categories[item]) _true++;
            })
        }
        return _true > 0;
    }

    //Function to print Vaccine card
    $scope.printTourGuideIDCard = function (card) {
        $('#tour_guide_card_form').css('display', 'none');
        $('#confirmPrintStatus').css('display', 'block');
        $scope.form = card;
        console.log($scope.form);
        if ($scope.form['tour_guide'].length > 29) {
            $scope.form.personFontSize = 8
        } else if ($scope.form['tour_guide'].length > 26) {
            $scope.form.personFontSize = 9
        } else if ($scope.form['tour_guide'].length > 23) {
            $scope.form.personFontSize = 10
        } else if ($scope.form['tour_guide'].length > 20) {
            $scope.form.personFontSize = 11
        } else if ($scope.form['tour_guide'].length > 17) {
            $scope.form.personFontSize = 12
        } else if ($scope.form['tour_guide'].length > 14) {
            $scope.form.personFontSize = 13
        } else if ($scope.form['tour_guide'].length > 11) {
            $scope.form.personFontSize = 14
        } else if ($scope.form['tour_guide'].length > 8) {
            $scope.form.personFontSize = 15
        }

        var content = $('#cardPrintableArea');
        content.load(`${app_url}/modules/Tour_Guide/Views/print_card.php`, function () {
            $compile(content.contents())($scope);
            $scope.$apply();
            newWin = window.open("");
            var css = '@page {size: landscape; font-size: 8pt; margin: 0;}';
            var head = newWin.document.head || newWin.document.getElementsByTagName('head')[0];
            var style = newWin.document.createElement('style');
            style.type = 'text/css';
            style.media = 'print';
            if (style.styleSheet) {
                style.styleSheet.cssText = css;
            } else {
                style.appendChild(newWin.document.createTextNode(css));
            }

            head.appendChild(style);
            // $scope.generateBarcode(content.find('#barcode'));
            newWin.document.write(content.html());
            setTimeout(function () {
                newWin.print();
                newWin.close();
                // $scope.updateCardPrintCount(card);
            }, 300);
        });
    };

    //
    $scope.callmxImageUploader = function () {
        var container = $(document).find("tbody#uploads_table");
        var row = container.find("tr:last");
        row.children("td.input_cell").each(function (key, value) {
            $(this).find("input[type=file]").each(function () {
                $(this).mxImageUploader();
            });
        });
    };

    //Remove property image
    $scope.removePropertyImage = function (image, index) {
        //Remove HTML table row
        var row = document.getElementById("image_" + index);
        row.remove();

        //Unset from js object
        $scope.initial_tab_data.images.splice(index, 1)
        
        //POST request to delete image
        var post_url = `${app_url}/${$scope.url.capitalize()}/remove_image/`;
        $http({
            method: 'POST',
            url: post_url,
            data: image,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(response);
        }).error(function (response) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(response);
        });
    }
};
