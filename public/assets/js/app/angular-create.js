var app = angular.module("mxcreate.modal", ['ui.bootstrap', 'angular.filter', 'ngFileUpload']);

app.controller("formController", ['$scope', '$modal', '$log', '$compile', '$http', '$filter', 'toaster', function ($scope, $modal, $log, $compile, $http, $filter, toaster) {
    $scope.files = [];
    $scope.form = {};
    $scope.dropdowns = {};
    $scope.datearray = {};
    $scope.property = [];
    $scope.url = "";
    $scope.actionname = "";
    $scope.app_selected_user = '';
    $scope.current_task = "login"; // For Login or Password Recovery View Switch
    $scope.section_title = "";
    $scope.section_values;
    //$scope.other_actions = ["Edit_Email_Setup", "Transfer_Cards", "Receive_Cards", 'Transfer_Sample', 'Receive_Transfer', 'Control_Application', 'Exempted_Application', 'Add_Vaccination', 'Print_Card', "Edit_Sms_Setup", "Add_Float", "Settle_Collection_Account", 'Manage_Service_Limit', "Subscribe_Service", "Manage_Classes", "Approve_Transfer_Request", "Upload_Cards", "Upload_Devices", "service_subscription_request", "Backup_Database", 'Upload_Results', 'Publish_Bulk_Results', 'Manage_Price_Public', 'Manage_Price_Private'];
    $scope.officer = {};

    $scope.extraControl = {};

    $scope.autoCompleteSelectOptions = {};

    $scope.autoComplete = function (searchKey, searchComponent) {
        // check if institution selected or add other inputs to before proceed for BCX
        if (typeof ($scope.form.opt_mx_institution_id) === 'undefined' && $scope.extraControl.institution == 0) {
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

    $scope.initiateAutocomplete = function () {
        // initiate autocomplete on start
        if ($scope.url === 'incident') {
            if ($scope.extraControl.institution == 0) {
                $scope.form.opt_mx_institution_id = $scope.dropdowns.opt_mx_institution_ids[0].id;
            }
            $scope.autoComplete('', 'opt_mx_subscriber_ids');
        }
    };

    $scope.onFocusShowRecaptcha = function (event) {
        angular.element('.tooltip').addClass('show-tooltip');
    };

    $scope.onKeyShowRecaptcha = function (event) {
        if (event.which === 13) {
            event.preventDefault();
            angular.element('.tooltip').addClass('show-tooltip');
        }
    };

    $scope.showForm = function (url, action, params = null) {
        $('.overlay').removeClass('hidden');
        $scope.FinishedFileUpload = false;
        $scope.ProcessingRequest = false;
        $scope.url = url;
        $scope.actionname = action;
        $scope.property = [];
        var form_url = "";
        var _url = url[0].toUpperCase() + url.substring(1);

        form_url = app_url + "/" + _url + "/" + action.toLowerCase();
        if (params !== null) {
            var params_str = '';
            for (var i = 0; i < params.length; i++) {
                params_str += '/' + params[i];
            }
            form_url += params_str;
        }
        // console.log(form_url);

        var modalInstance;
        var div = $('<div/>').load(form_url + ' #page-content', function () {
            var template = div.find('#display_content').html();
            $scope.dropdowns = JSON.parse(div.find('#data_content').attr('data-dropdowns'));
            if (div.find('#data_content').attr('data-form') !== undefined) {
                $scope.form = JSON.parse(div.find('#data_content').attr('data-form'));
                if (_url.indexOf('Application') > -1) {
                    $scope.form.SelectedSymptoms = [];
                    $scope.form.id = 0;
                }
            }

            modalInstance = $modal.open({
                template: template,
                controller: modalFormCtrl,
                windowClass: 'mx-modal-form',
                scope: $scope
            });
            $scope.$apply();

            $('.overlay').addClass('hidden');
            modalInstance.opened.then(function () {
                if (_url === 'Result' && action.toLowerCase() === 'upload_results') {
                    $(document).find('input[type=file]').each(function () {
                        $(this).mxResultUploader();
                        // echo $(this);
                    });
                } else {
                    if (div.find('input[type=file]').length > 0) {
                        $scope.imageCount = 0;
                        $(document).find('input[type=file]').each(function (key, value) {
                            $scope.imageCount = key;
                            $(this).mxImageUploader();
                        });
                    }
                }
                if (div.find('textarea[name=tar_sms_content]').length > 0) {
                    $(document).find('textarea[name=tar_sms_content]').height(100).smsArea({maxSmsNum: 3});
                }
                if (div.find('#data_content').attr('data-disabled') !== undefined) {
                    $scope.disabled = JSON.parse(div.find('#data_content').attr('data-disabled'));
                    //console.log($scope.disabled);
                    angular.forEach($scope.disabled, function (value) {
                        $(document).find('#' + value).parent().parent().css('display', 'none');
                    });
                }

                if ($scope.form.classes !== undefined) {
                    $scope.writeClassData($scope.form.classes);
                }
            }, function () {
            });
        });
    };

    $scope.writeClassData = function (class_data) {
        if (class_data.length > 0) {
            var tbody = '';
            var class_tbody = $(document).find('table > tbody#classes_data');
            class_tbody.empty();
            for (var i = 0; i < class_data.length; i++) {
                let check_value = '';
                if (class_data[i].opt_mx_state_id == 1) {
                    check_value = 'checked="true"';
                }
                tbody += '<tr class_row_id="' + class_data[i].id + '"><td><input type="text" placeholder="Class Name" name="txt_name[]" value="' + class_data[i].txt_name + '" class="form-control txt_name" ';
                tbody += ' ng-class="manage_classes.txt_name.$invalid && !manage_classes.txt_name.$pristine" /></td>';
                tbody += '<td><input type="number" placeholder="Maximum amount" name="dbl_max_amount[]" value="' + class_data[i].dbl_max_amount + '" class="form-control dbl_max_amount"';
                tbody += ' ng-class="manage_classes.dbl_max_amount.$invalid && !manage_classes.dbl_max_amount.$pristine" /></td>';
                tbody += '<td><input type="number" placeholder="Maximum Daily Transaction" name="int_max_transaction[]" value="' + class_data[i].int_max_transaction + '" class="form-control int_max_transaction"';
                tbody += ' ng-class="manage_classes.int_max_transaction.$invalid && !manage_classes.int_max_transaction.$pristine" /></td>';
                tbody += '<td><input type="checkbox" ' + check_value + ' class="opt_mx_state_id"></td>';
                tbody += '<td><button type="button" class="btn btn-success btn-sm class-data-adder"><i class="fa fa-plus fa-fw"></i></button>';
                if (i > 0) {
                    tbody += ' <button type="button" class="btn btn-danger btn-sm class-data-remover" disabled><i class="fa fa-minus fa-fw"></i></button>';
                } else {
                    tbody += ' <button type="button" class="btn btn-danger btn-sm class-data-remover hidden"><i class="fa fa-minus fa-fw"></i></button>';
                }
                tbody += '</td></tr>';
            }
            class_tbody.append(tbody);
        }
    };

    $scope.getApplicationUsers = function () {
        $http.get(app_url + "/views/utility/subscription/get_application_users.php").success(function (response) {
            $scope.application_users = response.users;
            $scope.app_selected_user = $scope.application_users[0].id;
        });
    };

    $scope.getServiceCategoryLimit = function (action, institution_class_id) {
        $('.overlay').removeClass('hidden');
        if (institution_class_id >= 1) {
            var request_url = `${app_url}/ClassService/${action}`;
            $http({
                method: 'POST',
                url: request_url,
                data: JSON.stringify(institution_class_id),
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                $('.overlay').addClass('hidden');
                var class_limit_data = JSON.parse($(response.data).find('#mabrexPageContent').text().trim());
                //console.log(class_limit_data);
                if (class_limit_data.length > 0) {
                    $scope.institution_class_flag = true;
                    var tbody = '';
                    var class_tbody = $(document).find('table > tbody#service_limit');
                    class_tbody.empty();
                    for (var i = 0; i < class_limit_data.length; i++) {
                        let check_value = '';
                        if (class_limit_data[i].check > 0) {
                            check_value = 'checked="true"';
                        }
                        tbody += '<tr limit_row_id="' + class_limit_data[i].id + '"><td><input type="text" name="txt_name[]" value="' + class_limit_data[i]['Limit Category'] + '" class="form-control txt_name" ';
                        tbody += ' ng-class="class_category.txt_name.$invalid && !class_category.txt_name.$pristine" disabled/></td>';
                        tbody += '<td><input type="number" placeholder="Maximum amount" name="dbl_max_amount[]" value="' + class_limit_data[i]['maximum Amount'] + '" class="form-control dbl_max_amount"';
                        tbody += ' ng-class="class_category.dbl_max_amount.$invalid && !class_category.dbl_max_amount.$pristine" /></td>';
                        tbody += '<td><input type="number" placeholder="Maximum Transaction" name="dbl_max_transaction[]" value="' + class_limit_data[i]['Maximum Number Of Transaction'] + '" class="form-control dbl_max_transaction"';
                        tbody += ' ng-class="class_category.int_max_transaction.$invalid && !class_category.int_max_transaction.$pristine" /></td>';
                        tbody += '<td><input type="checkbox" ' + check_value + 'value="' + class_limit_data[i].check + '" class="opt_mx_state_id" hidden></td>';
                        tbody += '</td></tr>';
                    }
                    class_tbody.append(tbody);
                }
            });
        } else {
            $scope.institution_class_flag = undefined;
        }
    };

    $scope.getUserReportSubscription = function (usesr_id) {
        $http.get(app_url + "/views/utility/subscription/get_report_subscription_data.php?user_id=" + usesr_id).success(function (response) {
            $scope.report_types = response.report_types;
            $scope.frequencies = response.frequencies;
        });
    };

    $scope.changePassword = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        $scope.url = '/User/';
        var post_url = app_url + "/User/changePassword/";
        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {
                var response = $(data).find('#mabrexPageContent').text().trim();
                if (response === '200' || response === '201') {
                    $('.notification-area').addClass('alert alert-success').text("Password changed successfully. Please login with your new password.");
                } else if (response === '210' || response === '101') {
                    $('.notification-area').addClass('alert alert-success').text("Password could not be changed.");
                } else if (response === '2000') {
                    $('.notification-area').addClass('alert alert-info').text("New Password and Confirm New Password do not match.");
                } else if (response === '1000') {
                    $('.notification-area').addClass('alert alert-danger').text("Old Password is incorrect");
                } else {
                    $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
                }
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');
                    location.href = app_url + "/Logout";
                }, 4000);

            }
        });
    };

    $scope.getDropdowns = function (url) {
        var request_url = `${app_url}/${url[0].toUpperCase()}${url.substring(1)}/get_dropdowns`;
        $http({
            method: 'POST',
            url: request_url,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            $scope.dropdowns = JSON.parse($(response.data).find('#mabrexPageContent').text().trim());
            $scope.initiateAutocomplete();
        });
    };

    // Field and Function to filter and return select options based on selected master dropdown
    $scope.filteredDropdownOptions = {$: undefined}; // initial non-filtering value

    $scope.setFilteredDropdownOptions = function (master, details) {
        $scope.filteredDropdownOptions = {};
        $scope.filteredDropdownOptions = details.filter((m) => m.master === master);
    };
    $scope.getMedicalData = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = `${app_url}/Result/getMedicalTransferData/`;
        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;
            console.log(data);
            if (data.code == '100') {
                $('.notification-area').addClass('alert alert-info').text(data.message);
            } else {
                $('.notification-area').addClass('alert alert-info').text(data.message);
                console.log(data);
                $scope.form = data.result;
            }
            setTimeout(() => {
                $scope.ProcessingData = false;
                $('.notification-area').removeClass('alert alert-info').text('');
            }, 2000)
        }, function () {
            $scope.ProcessingData = false;
        });
    };
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
            var template = div.find('#display_content').html();
            $scope.tabs = JSON.parse(div.find('#data_content').attr('data-tabs'));
            $scope.initial_tab_data = JSON.parse(div.find('#data_content').attr('data-initial'));
            $scope.hidden_columns = JSON.parse(div.find('#data_content').attr('data-hidden-columns'));
            if (div.find('#data_content').attr('data-account-detail') !== undefined) {
                $scope.account_detail = JSON.parse(div.find('#data_content').attr('data-account-detail'));
            }
            if (div.find('#data_content').attr('data-current-institution') !== undefined) {
                $scope.cur_institution = JSON.parse(div.find('#data_content').attr('data-current-institution'));
            }
            if (div.find('#data_content').attr('data-missing-accounts') !== undefined) {
                $scope.missing_accounts = JSON.parse(div.find('#data_content').attr('data-missing-accounts'));
            }
            modalInstance = $modal.open({
                template: template,
                controller: modalFormCtrl,
                windowClass: 'mx-modal-form',
                scope: $scope
            });
            $('.overlay').addClass('hidden');
            $scope.$apply();
        });
    };

    $scope.goToBlock = (to, from) => {
        setTimeout(() => {
            $(from).css('display', 'none');
            $(to).css('display', 'block');
        }, 300);
    };

    $scope.parseDate = function (date) {
        var newDate = new Date(date);
        var day = newDate.getDate();
        var month = newDate.getMonth() + 1;
        var year = newDate.getFullYear();

        return year + '-' + month + '-' + day;
    }
    $scope.getTodaysTime = function () {
        var date = new Date();
        return new Date(
            date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + ' ' + date.getHours() + ':' + date.getMinutes()
        )
    }
}]);

var modalFormCtrl = function ($scope, $modalInstance, $http, $modal, $window, $filter, Upload) {
    $scope.action = null;
    $scope.valid_card = false;

    $scope.verifyZanId = function () {
        $('.overlay').removeClass('hidden');
        clearNotification();
        $scope.ProcessingData = true;
        var zanid = $("#txt_id_number").val();
        var url = `${app_url}/${$scope.url.capitalize()}/verify_zan_id/`;
        $http({
            method: 'POST',
            url: url,
            data: {zan_id: zanid}, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            let values = JSON.parse($(data).find('#mabrexPageContent').text().trim());
            if (values.status === "0") {
                $scope.form = values;
                $scope.form.dat_date_of_birth = $scope.parseDate($scope.form.dat_date_of_birth);
                $scope.goToBlock('#officer_details_block', '#verify_zan_id_block');
                $scope.ProcessingData = false;
            } else {
                let message = values.message + "<br>";

                message += values.location ? values.location + "<br>" : '';
                message += values.council_name ? values.council_name + "<br>" : '';
                message += values.business_location ? values.business_location + "<br>" : '';
                message += values.officer_category ? values.officer_category + "<br>" : '';

                $scope.action = values.action;
                $scope.officer.id = values.row_value;
                notify('error', message);
                $scope.ProcessingData = false;
            }
        });
    };

    $scope.saveTransferRequest = function () {
        $('.overlay').removeClass('hidden');
        clearNotification();
        $scope.ProcessingData = true;
        var url = "/TransferRequest/confirm_transfer_request";
        $http({
            method: 'POST',
            url: app_url + url,
            data: $scope.form, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            let values = JSON.parse($(data).find('#mabrexPageContent').text().trim());

            if (values.status === 200) {
                notify('success', values.message);
                setTimeout(() => {
                    $scope.ProcessingData = false;
                    $scope.cancel();
                }, 3000)
            } else {
                notify('error', values.message);
            }
        });
    };

    String.prototype.capitalize = function () {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };

    $scope.registerOfficer = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = `${app_url}/Inspector/save/`;

        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(data);
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {

                if (response === 100) {
                    notify('error', "Failed to Register Officer.")
                } else {
                    notify('success', "Successfully Registered officer.");
                    $modalInstance.dismiss('cancel');
                }
                $scope.ProcessingData = false;
            }
        }, function () {
            $scope.ProcessingData = false;
        });
    };

    $scope.registerOwner = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = `${app_url}/Owner/save/`;

        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {
                var response = $(data).find('#mabrexPageContent').text().trim();
                response = JSON.parse(response);
                if (response === 100) {
                    notify('error', "Failed to Register Site Owner.")
                } else {
                    notify('success', "Successfully Registered Site Owner.");
                    $modalInstance.dismiss('cancel');
                }
                $scope.ProcessingData = false;
            }
        }, function () {

            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;
        });
    };

    $scope.saveForm = function (action = "") {
        $('.notification-area').removeClass('alert alert-success alert-danger alert-info').html('');
        $('.overlay').removeClass('hidden');
        if (localStorage.getItem("ShowProfile") !== null) {
            localStorage.removeItem("ShowProfile")
        }
        if (localStorage.getItem("ApplicantId") !== null) {
            localStorage.removeItem("ApplicantId")
        }
        $scope.ProcessingData = true;
        var post_url = `${app_url}/${$scope.url.capitalize()}/save/`;
        if (action !== "") {
            post_url = `${app_url}/${$scope.url.capitalize()}/${action}/`;
        }
        if ($scope.form.has_extra === 1) {
            $scope.configureExtraData(action);
        }
        // console.log($scope.form)
        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            if (action === 'post_generate_lab_document') {
                $scope.download_file = response.title;
                $scope.application_list = JSON.stringify(response.application_list);
                $scope.title = response.title;
                $scope.form = {};
                // $scope.generateLabTestListExcel(response.title, response.application_list);
                $scope.responseHandler(response, false);
//                    $scope.responseHandler(response, false);
            } else {
                $scope.responseHandler(response);
            }
        }).error(function (response) {
            $('.overlay').addClass('hidden');

            $scope.responseHandler(response);

        });
    };
    $scope.generateLabTestListExcel = function (title, data) {
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

//        console.log(data_to_post);
//
//        console.log(data_to_post);
        var _width = 800; //$(document).width() / 2 - 200;
        var _height = 800; //$(document).height();
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
//            console.log(data)
            if (data.status == 200) {
                fileName = app_url + "/" + data.file;
//                console.log(fileName)
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

    $scope.ExportTableToPDF = function () {
        $scope.cancel();
        //$('#preview_panel #report_preview').removeClass('hide');
        $('#DemoModal').modal('show');
    };
    //called when record row clicked to open the profile modal

    $scope.getTransferReference = function (action) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = `${app_url}/${action}/getTransferReference/`;
        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {
                var response = JSON.parse($(data).find('#mabrexPageContent').text().trim());
                if (response.status === 200) {
                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                    $scope.form = response.data;
//                    $scope.$apply();
//                    $modalInstance.$apply();

                } else if (response.status === 100) {
                    $('.notification-area').addClass('alert alert-danger').text("Reference number does not exist.");
                } else if (response.status === 105) {
                    $('.notification-area').addClass('alert alert-danger').text("This Transfer is already received by " + response.data + ".");
                }
//                console.log(response)
            }
            setTimeout(function () {
                $scope.ProcessingData = false;
                $('.notification-area').removeClass('alert alert-success').text('');
//                    $modalInstance.dismiss('cancel');

            }, 4000);
        }, function () {
            $scope.ProcessingData = false;
        });
    };
    $scope.getSampleData = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        if ($scope.form.dat_added_date != undefined && $scope.form.opt_mx_center_id !== undefined) {
            var post_url = `${app_url}/Transfers/getTransferData/`;
            $http({
                method: 'POST',
                url: post_url,
                data: $scope.form,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (data) {
                $('.overlay').addClass('hidden');
                $scope.ProcessingData = false;
                if (data.errors) {
                    $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
                } else {
                    var response = JSON.parse($(data).find('#mabrexPageContent').text().trim());
                    response.dat_added_date = new Date($scope.form.dat_added_date)
                    $scope.form = response;
                }
            }, function () {
                $('.overlay').addClass('hidden');
                $scope.ProcessingData = false;
            });
        } else {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;

        }
    };
    $scope.responseHandler = function (response, reload = true) {
        let code = Number(response.code);
        let status = response.status;
        let message = response.message;

        let timeout = 0;
        $scope.ProcessingData = !(status === undefined || status == false);

        if (code === 200 || code === 201) {
            $('.notification-area').addClass('alert alert-success').html(message);
            timeout = 1;
        } else if (code === 220) {
            $('.notification-area').addClass('alert alert-info').html(message);
            timeout = 1;
        } else {
            $('.notification-area').addClass('alert alert-danger').html(message);
        }

        if (timeout === 1) {
            setTimeout(function () {
                $('.notification-area').removeClass('alert alert-success alert-danger alert-info').html('');
                if (reload) {
                    if (code === 200 || code === 201) {
                        $modalInstance.dismiss('cancel');
                        location.reload();
                    }
                }
            }, 2000);
        }
    };

    $scope.saveFormWithUploads = function (url, action, uploads) {
        // console.log(uploads);
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = app_url + "/" + url + "/" + action + "/";
        if ($scope.form.has_extra === 1) {
            $scope.configureExtraData(action);
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
        $http({
            method: 'POST',
            url: post_url,
            data: formdata,
            headers: {'Content-Type': undefined}
        }).success(function (data) {
            // console.log(data);
            $('.overlay').addClass('hidden');
            if (data.status === false || !data.status) {
                $('.notification-area').addClass('alert alert-danger').text(`${data.message}`);
            } else if (data.status === '111' || data.status === 111) {
                $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled, with errors");
                $scope.download_file = data?.file;
                // console.log($scope.download_file);
                close_modal = false;
            } else {
                $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                var close_modal = true;
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');
                    $modalInstance.dismiss('cancel');
                    location.reload();
                }, 4000);
            }
        }).finally(function () {
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
            // console.log(key)
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
        // console.log(uploads)
    }
    $scope.parseDate = function (date) {
        var newDate = new Date(date);
        var day = newDate.getDate();
        var month = newDate.getMonth() + 1;
        var year = newDate.getFullYear();
        return year + '-' + month + '-' + day;
    }
    $scope.parseTime = function (time) {
        var date = new Date();
        var Selecteddate = new Date(time);
        return new Date(
            date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + ' ' + Selecteddate.getHours() + ':' + Selecteddate.getMinutes()
        )
    }
    $scope.configureExtraData = function (action) {
        // console.log($scope.url);
        // console.log(action);
        if ($scope.url == "Auction" && action == '') {
            var property = [];
            var container = $(document).find('tbody#property_table');
            container.children('tr').each(function () {
                var row = $(this);
                var data = {};
                row.children('td.input_cell').each(function () {
                    var control = $(this).find('[data-input]');
                    var value = control.val();
                    var label = control.attr('data-input');
                    data[label] = value;
                });
                property.push(data);
            });
            $scope.form.property = property;
        } else {
            switch (action) {
                case 'save':
                    var account = [];
                    if ($scope.form.chkselct) {
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
                    }
                    $scope.form.account = JSON.stringify(account);
                    break;
                case 'post_manage_classes':
                    let classes = [];
                    var container = $(document).find('tbody#classes_data');
                    container.children('tr').each(function () {
                        let class_id = $(this).attr('class_row_id');
                        let name = $(this).find('input.txt_name').val();
                        let max_amount = $(this).find('input.dbl_max_amount').val();
                        let max_transaction_number = $(this).find('input.int_max_transaction').val();
                        let check_value = $(this).find('input.opt_mx_state_id').is(':checked');
                        let state = 1;
                        if (check_value === false) {
                            state = 4;
                        }
                        classes.push({
                            'id': class_id,
                            'txt_name': name,
                            'dbl_max_amount': max_amount,
                            'int_max_transaction': max_transaction_number,
                            'opt_mx_state_id': state
                        });
                    });
                    $scope.form.class_data = JSON.stringify(classes);
                    break;
                case 'save_service_category_limit':
                    let class_service_limit = [];
                    var container = $(document).find('tbody#service_limit');
                    container.children('tr').each(function () {
                        let class_id = $(this).attr('limit_row_id');
                        let name = $(this).find('input.txt_name').val();
                        let max_amount = $(this).find('input.dbl_max_amount').val();
                        let max_transaction_number = $(this).find('input.dbl_max_transaction').val();
                        let check_value = $(this).find('input.opt_mx_state_id').val();
                        let state = 0;
                        if (check_value > 0) {
                            state = check_value;
                        }
                        class_service_limit.push({
                            'id': class_id,
                            'txt_name': name,
                            'dbl_max_amount': max_amount,
                            'dbl_max_transaction': max_transaction_number,
                            'state': state
                        });
                    });
                    $scope.form.limit_data = JSON.stringify(class_service_limit);
                    break;
                default:
                    break;
            }
        }

    };
    $scope.addAuctionItem = function (column) {
        // var row = $(this).parent().parent();
        var container = $(document).find('tbody#property_table');
        container.children('tr').each(function () {
            var row = $(this);
            var data = {};
            var check_value = true;
            row.children('td.input_cell').each(function () {
                if (!check_value) {
                    data = [];
                    return;
                }
                var control = $(this).find('[data-input]');
                var value = control.val();
                var label = control.attr('data-input');
                if (!value) {
                    check_value = false;
                }
                data[label] = value;


            });
            console.log(data);
            if (check_value) {
                $scope.property.push(data);
            }
            // row.find('.property-remover').removeClass('hidden');
            row.find('input[type=number]').val('');
            row.find('select').val('');
        });

        $scope.form.property = $scope.property;
        console.log($scope.form.property);
        $scope.processPropertyInfo();
    }
    $scope.removePropertyItem = function (id) {
        console.log($scope.property)
        console.log($scope.records)
        console.log(id)
        $scope.property.splice(id, 1);
        $scope.records.splice(id, 1);
        $scope.form.property = $scope.property;
        console.log($scope.form.property);
        $scope.processPropertyInfo();
    }
    $scope.processPropertyInfo = function () {
        $scope.records = [];

        $.each($scope.property, function (key, value) {
            // console.log(value.auction_dropdown)
            var property_name = $scope.dropdowns.auction_dropdowns.filter((item) => item.id === value.auction_dropdown)[0]['name'];
            console.log(property_name)
            $scope.records[key] = {'property_name': property_name};
            $scope.records[key].auction_dropdown = value.auction_dropdown;
            $scope.records[key].reserved_amount = value.reserved_amount;
            $scope.records[key].starting_bid = value.starting_bid;
            $scope.records[key].increment_interval = value.increment_interval;
            // let product_name = $scope.dropdowns.opt_mx_product_ids.filter(item => item.id == $scope.form.opt_mx_product_id[key])[0]['name']
        });

        console.log($scope.records);
    };
// This function is used to validate the file we are trying to upload
    // It is called once the user selects a file
    // The function is being currently used in upload_devices file
    $scope.validateFile = function () {
        var input = $(document).find('input#data_cards');
        var msgArea = $(document).find('div#dataUploadResultMessage');
        var file = input.prop('files')[0];
        var fileTypes = ['text/csv', 'application/vnd.ms-excel'];
        console.log(fileTypes)
        for (var i = 0; i < fileTypes.length; i++) {
            if (file.type === fileTypes[i]) {
                msgArea.html('').removeClass('well');
                return true;
            }
        }
        input.val('');
        msgArea.append('<p class="text-danger">Please select a valid csv file.</p>');
        return false;
    };
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
        $scope.action = null;
    };

    $scope.showDistrictsByLocation = function (loc) {
        return function (item) {
            return item.location === loc;
        };
    };

    $scope.closeAndOpenProfile = function () {
        $modalInstance.dismiss('cancel');
        $scope.showProfile('Officer', $scope.officer.id);
        $scope.action = null;
    };
//dynamic table for FAQ
    var faqIndex = 0;
    $scope.addFaqRow = function () {
        if ($scope.form.faq === undefined) {
            $scope.form.faq = [];
        }
//        i=$scope.form.faq.length;
        $scope.form.faq.unshift({
            id: faqIndex,
            tar_question_en: '',
            tar_answer_en: '',
            tar_question_sw: '',
            tar_answer_sw: '',
            tar_question_it: '',
            tar_answer_it: ''
        });
        faqIndex++;
    };
    $scope.removeFaqRow = function (id) {
        $scope.form.faq.forEach(function (faq, index) {
            if (faq.id === id) {
                $scope.form.faq.splice(index, 1);
            }
        });
    };

    $scope.validateFile = function () {
//        console.log($scope.form.result_file);

//        var $scope = $(document).find('input#txt_reference_file').scope();
        var allowedFiles = [".xls", ".xlsx"];
        var fileUpload = document.getElementById("result_file");
        var lblError = document.getElementById("file_error");
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:\)\(])+(" + allowedFiles.join('|') + ")$");
//        console.log(fileUpload.value.toLowerCase());
        if (!regex.test(fileUpload.value.toLowerCase())) {
//            console.log("Please upload files having extensions: <b>" + allowedFiles.join(', ') + "</b> only.");
            $scope.FileInvalid = true;
            // $("div#file_error").removeClass('hidden');
            // $('div#file_error').addClass('alert alert-danger').text("Please upload files having extensions: " + allowedFiles.join(', ') + " only.");
        } else {
            // lblError.innerHTML = "";
            // $("div#file_error").addClass('hidden');
            $scope.FileInvalid = false;
        }
//        console.log($scope.FileInvalid)
    };

    $scope.step = 1;
    $scope.test_type = null;
    $scope.reference_ok = false;
    $scope.nextStep = function (test_type) {
//        console.log('sadf')
        if (test_type !== undefined) {
            if (test_type == 'non_control_test') {
                $scope.form = {};
                $scope.center_availability = {};
            }
            $scope.test_type = test_type;
            $('#' + test_type).css('display', 'block');
        }

        $scope.step++;
//        console.log($scope.step);
    }

    $scope.prevStep = function () {
        if ($scope.step === 0) {
            $('#non_control_test').css('display', 'none');
            $('#control_test').css('display', 'none');
        }
        $scope.step--;
    }

    $scope.checkExistingExemptedApplication = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        const existingExemptedApplicationUrl = app_url + '/Application/check_existing_exempted_application';
        const post_data = {
            id: 'undefined',
            reference_number: $scope.form.reference_number
        }
        $http({
            method: 'POST',
            url: existingExemptedApplicationUrl,
            data: post_data,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;
//            console.log($(data).find('#mabrexPageContent').text());
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {

                var response = JSON.parse($(data).find('#mabrexPageContent').text().trim());
                if (response.status === 200) {
                    $('.notification-area').addClass('alert alert-success').text(response.message);
                    $scope.form = response.data;
                    $scope.control = 1;
                    $scope.step++;
//                    $scope.$apply();
//                    $modalInstance.$apply();

                } else if (response.status === 100) {
                    $('.notification-area').addClass('alert alert-danger').text(response.message);
                } else if (response.status === 110) {
                    $('.notification-area').addClass('alert alert-danger').text(response.message);
                } else {
                    $('.notification-area').addClass('alert alert-danger').text("Error: Something has occurred ");
                }
//                $scope.$apply();
            }
            setTimeout(function () {
                $scope.ProcessingData = false;
                $('.notification-area').removeClass('alert alert-success').text('');
//                    $modalInstance.dismiss('cancel');

            }, 4000);
        }).error(function (error) {
            $scope.ProcessingData = false;
//            console.log(error);
        });
    }

    $scope.submitForm = function () {
        // submit code goes here
//        console.log($scope.form)
    }
    $scope.getAvailability = function () {
//        console.log($scope.form);
        if (($scope.form.opt_mx_test_type_id != null || $scope.form.opt_mx_test_type_id != undefined) && ($scope.form.dat_test_date != null || $scope.form.dat_test_date != undefined) && ($scope.form.opt_mx_test_center_id != null || $scope.form.opt_mx_test_center_id != undefined)) {
            var data_to_post = {
                'dat_test_date': $scope.form.dat_test_date,
                'opt_mx_center_id': $scope.form.opt_mx_test_center_id,
                'opt_mx_test_type_id': $scope.form.opt_mx_test_type_id
            };
//            console.log(data_to_post);
            $http({
                method: 'POST',
                url: `${app_url}/Application/getAvailability`,
                data: data_to_post, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                $scope.center_availability = JSON.parse($(response.data).find('#mabrexPageContent').text());
                $scope.user_group_flag = true;
            });
        } else {
            $scope.center_availability = {};
        }
    }
    $scope.getTestCenter = function () {
//        console.log($scope.form);
        if (($scope.form.opt_mx_test_type_id != null || $scope.form.opt_mx_test_type_id != undefined)) {
            var data_to_post = {'opt_mx_test_type_id': $scope.form.opt_mx_test_type_id};
//            console.log(data_to_post);
            $http({
                method: 'POST',
                url: `${app_url}/Application/getTestCenter`,
                data: data_to_post, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                $scope.dropdowns.opt_mx_test_center_ids = JSON.parse($(response.data).find('#mabrexPageContent').text());
                $scope.user_group_flag = true;
            });
        } else {
            $scope.center_availability = {};
        }
    }
    $scope.getCenter = function (slot_id, test_time, control) {
        $scope.form.slot_id = slot_id;
        $scope.form.test_time = test_time;
        $scope.form.int_control = control;
        $scope.slot_avail = 1;
    }
    $scope.validatePassportFile = function () {
//        var $scope = $(document).find('input#txt_reference_file').scope();
        var allowedFiles = [".jpg", ".jepg", ".jpeg", ".png", "pdf"];
        var fileUpload = document.getElementById("txt_passport_image");
//        $scope.FileInvalid = false;
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
        if ($(document).find('input#txt_passport_image')) {
            $(document).find('input#txt_passport_image').each(function () {
                var files = $(this)
                var file = files.prop('files')[0]
                if (file != undefined || file != null) {
//                    console.log(file)
                    filename = file.name;
//                    console.log(file);
                    // echo $(this);
                    if (!regex.test(fileUpload.value.toLowerCase())) {
                        if ((file.size / 1048576) > 6) {
                            files.next('div').html("<p>" + filename + ": File size must Be less than 6 Mb</p>");
                            $scope.FileInvalid = false;
                        } else {
                            $scope.FileInvalid = true;
                        }
                    } else {
                        $scope.FileInvalid = false;
                    }
                }

            });
        }


    };

    $scope.getZanID = function (id_number, id_type) {
        $('.overlay').removeClass('hidden');
        clearNotification();
        $scope.ProcessingData = true;
        var url = `${app_url}/${$scope.url.capitalize()}/verify_zan_id/`;
        $http({
            method: 'POST',
            url: url,
            data: {id: id_number}, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            let values = JSON.parse($(data).find('#mabrexPageContent').text().trim());
            if (values.status === 200) {
                $scope.valid_card = true;
                $scope.form = values.data;
                $scope.form.opt_mx_e_card_type_id = id_type;
                $scope.form.dat_birth_date = new Date($scope.form.birth_date);
                // $scope.goToBlock('#officer_details_block', '#verify_zan_id_block');
                $scope.ProcessingData = false;
            } else {
                let message = values.message;

                $scope.officer.id = values.row_value;
                notify('error', message);
                $scope.ProcessingData = false;
            }
        });
    }

    $scope.checkID = function () {
        let id_type = $scope.form.opt_mx_e_card_type_id;
        let id_number = $scope.form.txt_e_card_number;
        if (id_type === 1) {
            return $scope.getZanID(id_number, id_type);
        }
    }

    $scope.verifyZanId = function () {
        $('.overlay').removeClass('hidden');
        clearNotification();
        $scope.ProcessingData = true;
        var zanid = $("#txt_id_number").val();
        var url = `${app_url}/${$scope.url.capitalize()}/verify_zan_id/`;
        $http({
            method: 'POST',
            url: url,
            data: {zan_id: zanid}, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            let values = data;
            if (values.status === "0") {
                $scope.form = values;
                $scope.form.dat_date_of_birth = $scope.parseDate($scope.form.dat_date_of_birth);
                $scope.goToBlock('#officer_details_block', '#verify_zan_id_block');
                $scope.ProcessingData = false;
            } else {
                let message = values.message + "<br>";

                message += values.location ? values.location + "<br>" : '';
                message += values.council_name ? values.council_name + "<br>" : '';
                message += values.business_location ? values.business_location + "<br>" : '';
                message += values.officer_category ? values.officer_category + "<br>" : '';

                $scope.action = values.action;
                $scope.officer.id = values.row_value;
                notify('error', message);
                $scope.ProcessingData = false;
            }
        });
    };

    $scope.registerOfficer = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = `${app_url}/Inspector/save/`;

        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(data)
        }, function () {
            $scope.ProcessingData = false;
        });
    };

    $scope.validateFinancialInstitutionImage = function () {

        var financialInstitutionLogoUpload = document.getElementById("txt_image_url");

        var allowedFiles = "";
        var fileUpload = "";
        $scope.FileType = [];
        $scope.FinancialInstitutionFileInvalid = [];
        var file_key = null;

        //Check uploaded file
        if (financialInstitutionLogoUpload.files.length != 0) {
            allowedFiles = [".png"];
            fileUpload = financialInstitutionLogoUpload;
            file_key = "txt_image_url";
            $scope.FileType.push(file_key);
            $scope.checkFileUpload(fileUpload, allowedFiles, file_key);
        }

    };
    $scope.checkFileUpload = function (file_upload, allowedFiles, file_key) {
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:\)\(])+(" + allowedFiles.join('|') + ")$");

        if (!regex.test(file_upload.value.toLowerCase())) {
            $scope.FileInvalid.push(file_key);
        }
    }
    $scope.fileFinancialInstitutionValidity = function (file_key) {
        return $scope.FinancialInstitutionFileInvalid.includes(file_key) && $scope.FileType.push(file_key);
    }

    $scope.callmxImageUploader = function () {
        var container = $(document).find("tbody#uploads_table");
        var row = container.find("tr:last");
        row.children("td.input_cell").each(function (key, value) {
            $(this).find("input[type=file]").each(function () {
                $(this).mxImageUploader();
            });
        });
    };
};
