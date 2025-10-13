var app = angular.module('mxpermission.modal', ['ui.bootstrap']);

app.controller("permissionCtrl", ['$scope', '$modal', '$log', '$timeout', '$http', '$compile', '$interval', '$filter', 'toaster', function ($scope, $modal, $log, $timeout, $http, $compile, $interval, $filter, toaster) {

        $scope.user_permissions = {};
        $scope.group_permissions = {};
        $scope.groups = {};
        $scope.url;
        $scope.form = {};
        $scope.new_group_form = {};
        $scope.user_group_flag = false;
        $scope.group_permission_flag = false;
        $scope.user_permission_flag = false;
        $scope.activetab = 1;
        $scope.data = {};
        $scope.dropdowns = {};
        var div = $('<div/>').load(`${app_url}/${$scope.url}` + ' #page-content', function () {
            if (div.find('#data_content').attr('data-permission-detail') !== undefined) {
                $scope.account_detail = JSON.parse(div.find('#data_content').attr('data-permission-detail'));
                console.log($scope.account_detail)
            }

        });
        $scope.getInitData = function () {
            if ($scope.account_detail) {
                $scope.data = $scope.account_detail;
            }

//        console.log($scope.data);

//            });
        };
        $scope.setActiveTab = function (tab) {
            $scope.activetab = tab;
            if (tab == 1) {//group
                $scope.user_group_flag = false;
                $scope.user_permission_flag = false;
//            $scope.getOptionData('group_id', 'mx_group', 'new_group_permission');
            } else if (tab == 2) {//user
                $scope.group_permission_flag = false;
            } else if (tab == 3) {//permissions
                $scope.user_group_flag = false;
                $scope.user_permission_flag = false;
                $scope.group_permission_flag = false;
            }
        };

        $scope.isActiveTab = function (tab) {
            return $scope.activetab === tab;
        };
        $scope.isParent = function () {
            $(document).ready(function () {
                $("#hideParent").click(function () {
                    $("#parent").hide();
                });
                $("#showParent").click(function () {
                    $("#parent").show();
                });
            });

        }
        $scope.getOptionData = function (ctrl_name, table, form_id) {
            var data_to_post = {};
            var sel_opt = $(document).find('form#' + form_id + ' select[name="' + ctrl_name + '"]');
            data_to_post = {'table': table};
            $http({
                method: 'POST',
                url: app_url + "/views/permission/get_option_data.php",
                data: table, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {

                $scope.writeSelectOptions(sel_opt, response.data);
            });
        };
        $scope.getData = function () {
            var data_to_post = {};
            $http({
                method: 'POST',
                url: app_url + "/Permission/loadData",
                data: data_to_post, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                $scope.account_detail = response.data;
//            console.log($scope.account_detail);
                $scope.groups_data = 1;
//            $scope.writeSelectOptions(sel_opt, response.data);
            });
        };
        $scope.saveForm = function (url, action = "") {
//        console.log($scope.form);
            $scope.url = url;

            post_url = app_url + "/" + url + "/" + action;
//        console.log(post_url);
            $http({
                method: 'POST',
                url: post_url,
                data: $scope.form, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                var div = $('<div/>').load(`${app_url}/${url}` + ' #page-content', function () {
                    if (div.find('#data_content').attr('data-permission-detail') !== undefined) {
                        $scope.account_detail = JSON.parse(div.find('#data_content').attr('data-permission-detail'));
                    }
//                console.log($scope.account_detail);

                });
//            console.log(response.data);
                let values = response.data;
                $scope.notify_reload(values.status, values.title);
            });
        }

        $scope.saveMenu = function () {
            var data_to_post = {'new_data': $scope.new_menu_form, 'func_name': 'saveMenu'};
//        console.log(data_to_post);
            $http({
                method: 'POST',
                url: app_url + "/Menu/saveMenu",
                data: data_to_post, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
//            console.log(response);

                let values = response.data;
                $scope.notify_reload(values.status, values.title);
            });
        };

        //Fetches user groups
        $scope.getUserGroup = function (user_id) {
            $scope.postdata = user_id;
            console.log($scope.postdata);
            $http({
                method: 'POST',
                url: `${app_url}/Permission/getUserGroups/${$scope.postdata}`,
                data: $scope.postdata, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                $scope.groups = response.data;
                $scope.user_group_flag = true;
            });
        };

        //Fetches group permissions
        $scope.getPermissions = function (action, id) {
            var datas = id.split(',', 2);
            id = datas[0];
            domain = datas[1];
            console.log(domain)
            if (id) {
                $scope.postdata = id;
                if (action == "getUserGroups" || action == "getUserPermissions") {
                    $scope.postdata = JSON.stringify({id: id, 'domain': domain});
//            console.log($scope.postdata);
                }
                $http({
                    method: 'POST',
                    url: app_url + "/Permission/" + action,
                    data: $scope.postdata, //forms user object
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).then(function (response) {
//            console.log(response.data);
                    if (action == "getUserPermissions") {
                        $scope.user_permissions = response.data;
//                console.log($scope.user_permissions);
                        $scope.user_permission_flag = true;
                    } else if (action == "getGroupPermissions") {
                        $scope.group_permissions = response.data;
//                console.log($scope.group_permissions);
                        $scope.group_permission_flag = true;
                    } else if (action == "getUserGroups") {
                        $scope.groups = response.data;
//                console.log($scope.groups);
                        $scope.user_group_flag = true;
                    }



                });
            } else {
                if (action == "getUserPermissions") {
                    $scope.user_permission_flag = false;
                } else if (action == "getGroupPermissions") {
                    $scope.group_permission_flag = false;
                } else if (action == "getUserGroups") {
                    $scope.user_group_flag = false;
                }
            }

        };

        //Fetches user permissions
        $scope.getAllPermissions = function () {
            $scope.postdata = {'permission': true};
            $http({
                method: 'POST',
                url: app_url + "/Permission/getAllPermissions",
                data: $scope.postdata,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
//            console.log(response.data);
                $scope.all_permissions = response.data;
                $scope.all_permission_flag = true;
            });
        };

        $scope.getAllMenus = function () {
            $scope.postdata = {'menu': true};
            $http({
                method: 'POST',
                url: app_url + "/views/permission/get_all_menus.php",
                data: $scope.postdata,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {

                $scope.all_menus = response.data;

                $scope.all_menu_flag = true;
            });
        };
        $scope.getMenuDropdowns = function (data) {
            $scope.dropdowns = data;
            console.log($scope.dropdowns)
        }

        $scope.getLastPosition = function (parent_id = 0) {
//        $scope.postdata = {};
            $http({
                method: 'POST',
                url: app_url + "/views/permission/get_last_position.php",
                data: parent_id,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {

                $scope.last_position = parseInt(response.data);
//            console.log($scope.last_position);
                $scope.all_menu_flag = true;
            });
        };
        $scope.getAllParents = function () {
            $scope.postdata = {'menu': true};
            $http({
                method: 'POST',
                url: app_url + "/views/permission/get_all_parents.php",
                data: $scope.postdata,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {

                $scope.all_parents = response.data;
                console.log($scope.all_parents);
                $scope.all_parent_flag = true;
            });
        };

        $scope.saveTableData = function (table_id, func_name, id, nested = true) {
            var data_to_post = {};
            var _table = $(document).find('table#' + table_id);
            var collection_data = [];
            console.log(_table)
            if (nested) {
                _table.children('tbody').each(function () {
                    var _body = $(this);
                    _body.children().not(':first').each(function () {
                        var row = $(this);
                        var data = $scope.getRowData(row);
                        collection_data.push(data);
                    });
                });
            } else {
                _table.children('tbody').children().each(function () {
                    var row = $(this);
                    var data = $scope.getRowData(row);
                    console.log(data)
                    collection_data.push(data);
                });
            }
            var datas = id.split(',', 2);
            id = datas[0];
            domain = datas[1];
            data_to_post = {'new_data': collection_data, 'id': id,'domain':domain};

//        console.log(collection_data);
            $http({
                method: 'POST',
                url: app_url + "/Permission/" + func_name,
                data: data_to_post, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                let values = response.data;
                var status = values.status;
                $scope.notify_reload(status, values.title);
//              console.log(response.data);
            });

        };

        /**
         * This function gets a row and extracts the selection the users made
         * If the user has selected a check in that row, the value at index 0 is
         * set to 1, otherwise the value is 0. The value at index 1 is always
         * set to the ID of the item being read.
         * 
         * @param {tr} row
         * @returns {object}
         */
        $scope.getRowsData = function (row) {
            //var row = $(this);
            var data = {};
            var _id = row.children('td:nth-child(5)').children('input').val();
            var _check = row.children('td:nth-child(6)').children('input').is(':checked');
            _check === true ? data[0] = 1 : data[0] = 0;
            data[1] = _id;

            return data;
        };
        $scope.getRowData = function (row) {
            //var row = $(this);
            var data = {};
            var _id = row.children('td:nth-child(3)').children('input').val();
            var _check = row.children('td:nth-child(4)').children('input').is(':checked');
            _check === true ? data[0] = 1 : data[0] = 0;
            data[1] = _id;

            return data;
        };

        //For notifications
        $scope.notify_reload = function (return_value, title) {

            var _type;
            var _icon;
            var _msg;
            var _reload = false;
            if (return_value == 200) {
                _type = 'success';
                _icon = "pe pe-7s-check fa-2x";
                _reload = true;
            } else if (return_value == 220) {
                _type = 'success';
                _icon = "pe pe-7s-check fa-2x";
                _reload = true;
            } else if (return_value == 100) {
                _type = 'danger';
                _icon = "pe-7s-close fa-2x";
            } else {
                _type = 'danger';
                _icon = "pe-7s-close fa-2x";
                if (title == undefined) {
                    title = 'Sorry, the operation could not be completed'
                }
            }


            _msg = '<p class="notification-msg">' + title + '</p>';
            if (_reload) {
                $interval(function () {
                    location.reload();
                }, 4000);
            }

            $.notify({
                title: '<b class="notification-title">Permission Management</b>',
                message: _msg,
                icon: _icon
            }, {
                delay: 4000,
                type: _type,
                placement: {
                    from: 'top',
                    align: 'center'
                }
            });
        };
        $scope.getInstitutionUser = function (ctrl_name, table, form_id) {
//           $scope.institution_flag = false;
            var data_to_post = {};
            $scope.groups_data = 0;
            var sel_opt = $(document).find('form#' + form_id + ' select[name="' + ctrl_name + '"]');
//        if(institution_id==null){
//            $scope.institution_flag = false;
//        }
            data_to_post = {'table': table};
//        console.log(data_to_post);
            $http({
                method: 'POST',
                url: app_url + "/views/permission/get_option_data.php",
                data: data_to_post, //forms user object
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                console.log(response.data);
                if (response.data != "") {
                    $scope.writeSelectOptions(sel_opt, response.data);
                    $scope.groups_data = 1;
                    $scope.institution_flag = true;
//                 console.log($scope.institution_flag)
                }
//           
            });
        };

        //Removes select options, and add new ones
        $scope.writeSelectOptions = function (control, data) {
            control.find('option').not(':first').each(function () {
                $(this).remove();
            });
            for (var i = 0; i < data.length; i++) {
                control.append('<option value="' + data[i].id + '">' + data[i].name + '</option>');
            }
        };

        // Open form for a clicked action
        $scope.showActionForm = function (id, url, action) {
            var formURL = `${app_url}/${url}/${action.toLowerCase()}/${id}`;
            $scope.url = url;
            $scope.action_name = action;

            var modalInstance;
            var div = $('<div/>').load(formURL + ' #page-content', function () {
                var template = div.find('#display_content').html();
                $scope.dropdowns = JSON.parse(div.find('#data_content').attr('data-dropdowns'));
                $scope.form = JSON.parse(div.find('#data_content').attr('data-form'));

                console.log($scope.dropdowns.int_parent_ids);
                console.log($scope.form);
                modalInstance = $modal.open({
                    template: $compile(template)($scope),
                    controller: ModalProfileCtrl,
                    windowClass: 'mx-modal-form',
                    scope: $scope
                });
                $scope.$apply();
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
            });
        };
    }]);


////this function is for modal controller called in modal
//var ModalProfileCtrl = function ($scope, $modalInstance, $http, $compile, $interval, $filter) {
//    //to close modal 
//    $scope.cancel = function () {
//        $modalInstance.dismiss('cancel');
//        if ($scope.device_status_interval != null) {
//            clearInterval($scope.device_status_interval);
//        }
//    };
//
//    $scope.saveProfileOperation = function (url, action) {
//        $scope.ProcessingData = true;
//        var post_url = app_url + "/" + url + "/" + action + "/";
//        if ($scope.form.opt_mx_commission_type_id !== undefined && $scope.form.opt_mx_commission_type_id.name === 'Slab') {
//            $scope.extractCommissionData();
//        }
//        if ($scope.form.has_extra === 1) {
//            $scope.configureExtraData(action);
//        }
//
//        if (action === "visaSubscription" || action === "unsubscribeVisa" || action === "resetPin" || action === 'resetSubscriberImsi'|| action === "post_subscription" || action === "post_add_permit"|| action === "post_add_float") {
//            $scope.form.id = $scope.parent_id;
//        } else if (action === "saveIncident" || action === "post_subscription" || action === "post_add_permit" ) {
//            $scope.form.opt_mx_subscriber_id = $scope.parent_id;
//        } else if (action === "changeSubscriberMainAccount" || action === "saveNewSubscriberClass" || action === 'activateSubscriberScheme') {
//            $scope.form.subscriber_id = $scope.parent_id;
//        }
//        
//        console.log(url);
////         if(url == 'Route'){
////            var classes = [];
////            var container = $(document).find('tbody#class_table');
////            
////            container.children('tr').each(function () {
////                let id = $(this).find('input.id').val();
////                let class_id = $(this).find('input.opt_mx_class_id').val();
////                let price = $(this).find('input.db_price').val();
////                let price_child = $(this).find('input.db_price_child').val();
////                let price_usd = $(this).find('input.db_price_usd').val();
////                let db_price_child_usd = $(this).find('input.db_price_child_usd').val();
////
////                classes.push({'id':id,'opt_mx_class_id': class_id, 'db_price': price, 'db_price_child': price_child, 'db_price_usd': price_usd, 'db_price_child_usd': db_price_child_usd});
////               
////            });
////            $scope.form.classes = classes;
////            //console.log(classes)
////        }
//        console.log($scope.form.classes);
//        $http({
//            method: 'POST',
//            url: post_url,
//            data: $scope.form,
//            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
//        }).success(function (data) {
//            if (data.errors) {
//                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
//            } else {
//                var response = $(data).find('#mabrexPageContent').text().trim();
//                if (response === '200' || response === '201') {
//                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
//                }else if(response === '210') {
//                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled. Waiting for Approval.");
//                } else {
//                    $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
//                }
//                setTimeout(function () {
//                    $('.notification-area').removeClass('alert alert-success').text('');
//                    $modalInstance.dismiss('cancel');
//                    if (response === '201') {
//                        location.reload();
//                    } else {
//                        if ($scope.is_profile_tab === true) {
//                            $scope.getProfileRecords($scope.current_tab, $scope.parent_id);
//                        } else {
//                            $scope.getAssociatedRecords($scope.current_tab, $scope.parent_id);
//                        }
//                    }
//                }, 2000);
//            }
//        }).finally(function () {
//            $scope.ProcessingData = false;
//        });
//    };
//
//    $scope.extractCommissionData = function () {
//        var commission = [];
//        var container = $(document).find('tbody#slab_commission_data');
//        container.children('tr').each(function () {
//            var min = $(this).find('input.dbl_minimum').val();
//            var max = $(this).find('input.dbl_maximum').val();
//            var amount = $(this).find('input.dbl_commission').val();
//            commission.push({'dbl_minimum': min, 'dbl_maximum': max, 'dbl_commission': amount});
//        });
//        $scope.form.slabs = JSON.stringify(commission);
//    };
//
//    $scope.configureExtraData = function (action) {
//        switch (action) {
//            case 'post_register_account':
//                var account = [];
//                var container = $(document).find('tbody#account_table');
//                container.children('tr').each(function () {
//                    var row = $(this);
//                    var data = {};
//                    row.children('td.input_cell').each(function () {
//                        var control = $(this).find('[data-input]');
//                        var value = control.val();
//                        var label = control.attr('data-input');
//                        data[label] = value;
//
//                    });
//                    account.push(data);
//                });
//                $scope.form.account = JSON.stringify(account);
//                break;
//            default:
//                break;
//        }
//    };
//
//    $scope.saveProfileOperationWithUploads = function (url, action, uploads) {
//        $scope.ProcessingData = true;
//        var post_url = app_url + "/" + url + "/" + action + "/";
//        var formdata = new FormData();
//        angular.forEach($scope.form, function (value, key) {
//            formdata.append(key, value);
//        });
//        if (uploads.length > 0) {
//            angular.forEach(uploads, function (value) {
//                var file = document.getElementById(value).files[0];
//                formdata.append(value, file);
//            });
//        }
//        if ($scope.form.has_extra === 1) {
//            $scope.configureExtraData(action);
//        }
//
//        //console.log($scope.form);
//        $http({
//            method: 'POST',
//            url: post_url,
//            data: formdata,
//            headers: {'Content-Type': undefined}
//        }).success(function (data) {
//            if (data.errors) {
//                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
//            } else {
//                var response = $(data).find('#mabrexPageContent').text().trim();
//                if (response === '200' || response === '201') {
//                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
//                } else {
//                    $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
//                }
//                setTimeout(function () {
//                    $('.notification-area').removeClass('alert alert-success').text('');
//                    $modalInstance.dismiss('cancel');
//                    if (response === '201') {
//                    location.reload();                        
//                    }
//                }, 2000);
//            }
//        }).finally(function () {
//            $scope.ProcessingData = false;
//        });
//    };
//
//    $scope.generateReport = function () {
//        $('#loader').css('display', '');
//        var msg = '';
//        var title = '<b class="notification-title">Report Preview</b>';
//        var icon = 'pe-7s-close fa-2x';
//        var type = '';
//        var from_date = $scope.formatDate($scope.frmData['from_date']);
//        var to_date = $scope.formatDate($scope.frmData['to_date']);
//        var record_id = $scope.frmData['record_id'];
//        var data_to_post = {'record_id': record_id, 'from_date': from_date, 'to_date': to_date};
//
//        var _width = 900; //$(document).width();
//        var _height = 800; //$(document).height();
//        var fileName = app_url + "/pdf/tmp/report.pdf";
//        $http({
//            method: 'POST',
//            url: app_url + "/views/applicant/applicant_report.php",
//            data: data_to_post, //forms user object
//            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
//        }).success(function (response) {
//            if (response.status == 200) {
//                $(document).find('div#range').hide();
//                $(document).find('div#range2').removeClass('hide');
//                $(document).find('div#profile_preview_panel').removeClass('hide');
//                var object = "<object data=\"{FileName}#toolbar=1\" type=\"application/pdf\" width=\"" + _width + "px\" height=\"" + (_height - 100) + "px\">";
//                object += "If you are unable to view file, you can download from <a href = \"{FileName}\">here</a>";
//                object += " or download <a target = \"_blank\" href = \"http://get.adobe.com/reader/\">Adobe PDF Reader</a> to view the file.";
//                object += "</object>";
//                object = object.replace(/{FileName}/g, fileName);
//                $(document).find('div#profile_report_preview').css('height', _height - 80 + 'px').html(object);
//            } else if (response.status == 100) {
//                (document).find('div#profile_report_preview').empty();
//                (document).find('div#profile_preview_panel').addClass('hide');
//                msg = '<p class="notification-msg">Sorry! There is no data available.</p>';
//                type = 'danger';
//                $scope.notify(msg, icon, title, type);
//            } else {
//                (document).find('div#profile_report_preview').empty();
//                (document).find('div#profile_preview_panel').addClass('hide');
//                msg = '<p class="notification-msg">Sorry! Something went wrong.</p>';
//                type = 'danger';
//                $scope.notify(msg, icon, title, type);
//            }
//            $('#loader').css('display', 'none');
//        });
//    };
//
//    $scope.formatDate = function (dateString) {
//        var date = dateString.getDate();
//        var month = dateString.getMonth() + 1;
//        var year = dateString.getFullYear();
//        if (date < 10) {
//            date = '0' + date;
//        }
//        if (month < 10) {
//            month = '0' + month;
//        }
//        dateString = year + '-' + month + '-' + date;
//        return dateString;
//    };
//
//    $scope.hideDiv = function () {
//
//        $(document).find('div#range2').addClass('hide');
//        $(document).find('div#range').show();
//    };
//
//    String.prototype.capitalize = function () {
//        return this.charAt(0).toUpperCase() + this.slice(1);
//    };
//};
