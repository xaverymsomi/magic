var app = angular.module('mxreport.modal', ['datatables']);

app.controller("reportCtrl", function ($scope, $http, $interval, $compile, $filter) {
    // Model to hold report options data
    $scope.ReportOptions = {
        'Type': 1,
        'StartDate': new Date(),
        'EndDate': new Date(),
        'Category': 0,
        'FilterField': 0,
        'GroupingField': 0,
        'ReportTitle': 'GENERAL REPORT',
        'FilterFieldValue': {'Id': 0, 'Name': '', 'Type': 0}
    };

    $scope.institution;
    $scope.ReportIsOpen = false;
    $scope.ReportFilters = {};
    $scope.LabValues = {};
    $scope.Centers = {};
    $scope.ReportGroupings = {};
    $scope.ReportCategories = [{'Id': 0, 'Name': 'Summary'}];
    $scope.ReportFilterValues = {};
    $scope.AuditUsers = {};
    $scope.AuditActions = {};
    $scope.StatementReportCheck = 0;
    $scope.ReportOptions.Center = 0;

    $scope.extraControl = {};

    $scope.autoCompleteSelectOptions = {};

    $scope.autoComplete = function (searchKey, searchComponent) {
        // check if institution selected or add other inputs to before proceed for BCX
        $scope.data = [];
        if (searchKey !== '') {
            for (var i = 0; i < $scope.ReportFilterValues.length; i++) {
                var name = $scope.ReportFilterValues[i]['Name'].toLowerCase();
                if (name.indexOf(searchKey) > -1) {
                    $scope.data.push($scope.ReportFilterValues[i]);
                }
            }
            $scope.autoCompleteSelectOptions[searchComponent] = $scope.data;
        } else {
            $scope.autoCompleteSelectOptions[searchComponent] = $scope.ReportFilterValues;
        }

    };

    $scope.initiateAutocomplete = function () {
        $scope.autoComplete('', 'ReportFilterValues');
    };


    $scope.getFormFields = function (report_type, value) {
        $('#loader').css('display', '');
        $scope.ReportOptions.Type = value;
        $scope.ReportFilters = {};
        $scope.LabValues = {};
        $scope.ReportGroupings = {};
        $scope.ReportFilterValues = {};
        $scope.PaymentStatusValues = {};
        $scope.AuditUsers = {};
        $scope.Centers = {};
        $scope.ReportOptions.PaymentProvider = 1;
        $scope.AuditActions = {};
        $scope.ReportOptions.PaymentStatusValues = 0;
        $http.post(app_url + "/Report/get_form_fields", {'report_type': report_type}).success(function (response) {
            var data = JSON.parse($(response).find('#page-content').text());

            $scope.ReportFilters = data.filters;
            $scope.ReportGroupings = data.group_by;
            if (data.centers !== undefined) {
                $scope.Centers = data.centers;
            }

            if (data.paymentproviders !== undefined) {
                $scope.PaymentProviders = data.paymentproviders;
            }


            $scope.ReportCategories = data.categories;
            $scope.ReportOptions.ReportTitle = data.title;
            $scope.PaymentStatusValues = data.payment_status;
            $scope.LabValues = data.labs;
            $scope.AuditUsers = data.users;
            $scope.reportName = report_type;
//            console.log($scope.ReportFilters);
            if ($scope.ReportFilters.length > 0) {
                $scope.ReportOptions.FilterField = $scope.ReportFilters[0].Id;
            }
            if ($scope.ReportGroupings.length > 0) {
                $scope.ReportOptions.GroupingField = $scope.ReportGroupings[0].Id;
            }
            if ($scope.ReportCategories.length > 0) {
                $scope.ReportOptions.Category = $scope.ReportCategories[0].Id;
            }
            if ($scope.PaymentStatusValues !== undefined && $scope.PaymentStatusValues.length > 0) {
                $scope.ReportOptions.PaymentStatusValues = $scope.PaymentStatusValues[0].Id;
            }
            if ($scope.LabValues !== undefined && $scope.LabValues.length > 0) {
                $scope.ReportOptions.FilterFieldValue = $scope.LabValues[0];
            }
            if ($scope.Centers !== undefined && $scope.Centers.length > 0) {
                $scope.ReportOptions.Center = $scope.Centers[0].Id;
            }
            if ($scope.AuditUsers !== undefined && $scope.AuditUsers.length > 0) {
                $scope.ReportOptions.AuditUsers = $scope.AuditUsers[0].Id;
            }

            $('#loader').css('display', 'none');
        });
    };

    $scope.getFilteringValues = function () {
//        console.log($scope.institution);
//        console.log($scope.ReportOptions.FilterField);
        $http.post(app_url + "/Report/get_filtering_fields", {
                'filter_criteria': $scope.ReportOptions.FilterField,
                'report_type': $scope.ReportOptions.Type,
                'report_category': $scope.ReportOptions.Category
            }
        ).success(function (response) {
            var data = JSON.parse($(response).find('#page-content').text());

            $scope.ReportFilterValues = data;
            if ($scope.ReportFilterValues.length > 0) {
                $scope.ReportOptions.FilterFieldValue = $scope.ReportFilterValues[0];
            }
            if ($scope.ReportOptions.Type === 9 && ($scope.ReportOptions.FilterField === 6 || $scope.ReportOptions.FilterField === 7)) {
                $scope.initiateAutocomplete();
            }
            $('#loader').css('display', 'none');
        });
    };
    $scope.getAuditActions = function () {
        $scope.AuditActions = {};
//        console.log($scope.institution);
//        console.log($scope.ReportOptions.FilterField);
        if ($scope.ReportOptions.Type === 9) {
            $http.post(app_url + "/Report/get_audit_actions", {
                    'filter_value': $scope.ReportOptions.FilterFieldValue.Name
                }
            ).success(function (response) {
                var data = JSON.parse($(response).find('#mabrexPageContent').text());
                $scope.AuditActions = data;
                if ($scope.AuditActions !== undefined && $scope.AuditActions.length > 0) {
                    $scope.ReportOptions.AuditAction = $scope.AuditActions[0];
                }
                $('#loader').css('display', 'none');
            });
        }

    };

    $scope.generateReport = function () {
        console.log('NG - generateReport');
        $('#loader').css('display', '');
        $('.overlay').removeClass('hidden');
        $(document).find(".progress").each(function () {
            $(this).css("visibility", "visible");
        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';

        var data_to_post = {
            'report_type': $scope.ReportOptions.Type,
            'from_date': $scope.formatDate($scope.ReportOptions.StartDate),
            'to_date': $scope.formatDate($scope.ReportOptions.EndDate),
            'filter_criteria': $scope.ReportOptions.FilterField,
            'group_criteria': $scope.ReportOptions.GroupingField,
            'category': $scope.ReportOptions.Category,
            'title': $scope.ReportOptions.ReportTitle,
            'filter_value': $scope.ReportOptions.FilterFieldValue.Id,
            'filter_name': $scope.ReportOptions.FilterFieldValue.Name,
            'provider': $scope.ReportOptions.PaymentProvider,
            'statementreportcheck': $scope.StatementReportCheck,
            'payment_status': $scope.ReportOptions.PaymentStatusValues,
            'institution': $scope.ReportOptions.Institution,
            'source': $scope.ReportOptions.Source,
            'report': $scope.reportName
        };
        console.log('data to post', data_to_post);
        if ($scope.ReportOptions.AuditUsers !== undefined && $scope.ReportOptions.AuditUsers !== null) {
            data_to_post['audit_user'] = $scope.ReportOptions.AuditUsers;
        }
        if ($scope.ReportOptions.AuditAction !== undefined && $scope.ReportOptions.AuditAction.Id !== 0) {
            data_to_post['audit_action'] = $scope.ReportOptions.AuditAction.Name;
        }
//        console.log(data_to_post);
        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName = app_url + "/uploads/report/";

//           console.log($scope.ReportOptions.FilterFieldValue);
//url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: app_url + "/Report/generate_report",
            data: data_to_post, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (response) {
            var data;
            if ($(response).find('#page-content').text()) {
                data = JSON.parse($(response).find('#page-content').text());
            } else {
                data = response;
            }
            if (data.status === 200) {
                fileName += data.pdf_name;
                console.log(fileName);
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
//                console.log(object);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);

                $('#reportPDFPreview').html(object);

                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
//                console.log(data.records);
                $scope.writeHtmlTable(data.records);
            } else if (data.status === 100) {
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
            $('.overlay').addClass('hidden');
            $(document).find(".progress").each(function () {
                $(this).css("visibility", "hidden");
            });
        });
        $('.overlay').addClass('hidden');
    };

    $scope.notify = function (msg, icon, title, type) {
        $.notify({
            title: title,
            message: msg,
            icon: icon
        }, {
            delay: 3000,
            type: type,
            placement: {
                from: 'top',
                align: 'center'
            }
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

    //Fetch data for option fields
    $scope.getOptionData = function (ctrl_name, table, form_id) {
        var data_to_post = {};
        var sel_opt = $(document).find('form#' + form_id + ' select[name="' + ctrl_name + '"]');
        data_to_post = {'table': table};
        $http({
            method: 'POST',
            url: app_url + "/views/permission/get_option_data.php",
            data: data_to_post, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {

            $scope.writeSelectOptions(sel_opt, response.data);
        });
    };

    //Removes select options, and add new ones
    $scope.writeSelectOptions = function (control, data) {
        control.find('option').each(function () {
            $(this).remove();
        });
        control.append('<option value="">Select Filter</option><option value="0">All</option>');
        for (var i = 0; i < data.length; i++) {
            control.append('<option value="' + data[i].id + '">' + data[i].name + '</option>');
        }
    };

    $('form#Form2 input#from_date').on('change', function () {
        if ($(this).val() !== '') {
            var selected = $(this).val();
            $('form#Form2 input#to_date').attr('min', selected);
        }
    });

    $('form#Form2 input#to_date').on('change', function () {
        if ($(this).val() !== '') {
            var selected = $(this).val();
            $('form#Form2 input#from_date').attr('max', selected);
        }
    });

    $scope.hideDiv = function () {
        $(document).find('div#range2').addClass('hide');
        $(document).find('div#collapsePanelReport').show();
    };

    $scope.togglePanelReport = function () {
        var control = $(document).find('i#reportHeaderToggler');
        if ($(document).find('div#collapsePanelReport').hasClass('in') === true) {
            control.removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
        } else {
            control.removeClass('pe-7s-angle-down').addClass('pe-7s-angle-up');
        }
    };

    $scope.closeReportViewer = function () {
        $('#preview_panel').addClass('hide');
        $scope.ReportIsOpen = false;
    };

    $scope.writeHtmlTable = function (data) {
        $scope.records = JSON.stringify(data);
        var header = '';
        var by_value = '';
        if ($scope.ReportOptions.FilterField === 4 && $scope.ReportOptions.ReportTitle === "TRANSACTION REPORT") {
            header = '<h3>' + $scope.ReportOptions.FilterFieldValue['Name'] + '</h3>';
        }
        if ($scope.StatementReportCheck === 1) {
            $scope.ReportOptions.ReportTitle = 'STATEMENT REPORT';
        }
        // console.log($scope.institution);
        if ($scope.ReportOptions.Type === 11 && $scope.ReportOptions.FilterField === 2) {
            by_value = ' BY ' + $scope.ReportOptions.FilterFieldValue['Name'];
            // console.log( by_value);
        } else if ($scope.ReportOptions.Type === 11 && $scope.ReportOptions.FilterField === 4) {
            by_value = ' BY ' + $scope.ReportOptions.FilterFieldValue['Name'];

        }
        if ($scope.ReportOptions.Type === 6) {
            header += '<h4>' + $scope.ReportOptions.ReportTitle + by_value + ' as of ' + $filter('date')($scope.ReportOptions.StartDate, 'MMMM dd, yyyy') + '</h4>';
        } else {
            header += '<h4>' + $scope.ReportOptions.ReportTitle + by_value + ' as of ' + $filter('date')($scope.ReportOptions.StartDate, 'MMMM dd, yyyy') + ' to ' + $filter('date')($scope.ReportOptions.EndDate, 'MMMM dd, yyyy') + '</h4>';
        }
        if ($scope.ReportOptions.Type > 0) {
            var paragraph1 = "";
            var paragraph2 = "";
            var table_data = [];
            var table = "";
            var n = 1000;
            $scope.hasMany = false;
            $scope.totalRecords = data.length;

            if (data.length > n && $scope.ReportOptions.Type !== 5) {
                console.log('Total records:', data.length);
                $scope.hasMany = true;
                const result = new Array(Math.ceil(data.length / n)).fill().map(_ => data.splice(0, n));
                table_data = result[0];
                // console.log("Data before processing:", table_data);
            } else {
                table_data = data;
            }
            if ($scope.ReportOptions.Type === 1) {
                var titles = {
                    'Applicants': "APPLICANT SUMMARY REPORT",
                    'Permit': "PERMIT SUMMARY REPORT",
                    'Applications': "APPLICATION SUMMARY REPORT",
                    'Invoice': "INVOICE SUMMARY REPORT",
                    'Receipt': "RECEIPT SUMMARY REPORT",
                    'Finances': "FINANCE SUMMARY REPORT"
                };

                $.each(data, function (key, value) {
                    // Check if the key exists in titles and value is an array
                    if (titles[key] && Array.isArray(value)) {
                        table += $scope.writeTableSection(value, null, null, false, titles[key]);
                    }
                });
            }
            else if ($scope.ReportOptions.Type === 5 && $scope.StatementReportCheck === 1) {//TRANSACTION REPORT
                table += $scope.writeStatementReport(table_data);
            } else if ($scope.ReportOptions.Type === 14) {//TRANSACTION REPORT
                table += $scope.writeCommissionReport(table_data);
            } else {
                table += $scope.writeTableSection(table_data);
            }

            $('#reportHtmlSection').html(header + table);

            // Trigger modal display
            if ($scope.hasMany) {
                $('#hasMany').modal();
            } else {
                console.log('Modal condition not met, hasMany:', $scope.hasMany);
            }

            console.log('Generated Table:', table);
            console.log('Displaying modal, hasMany:', $scope.hasMany);
        }
    };
    $scope.ExportToExcel = function (titles, data) {
        var file = titles.replace(/\s/g, '_');
        console.log(file);
        var records = JSON.parse(data);
        alasql(`SELECT *
                INTO XLSX("${file}.xlsx",{headers:true})
                FROM ?`, [records]);
    }
    $scope.writeTableSection = function (rows, opening_balance = 0, closing_balance = 0, show_opening_closing = false, title = "") {
        console.log("msomi:", rows);

        var money_columns = ['Amount In', 'TOTAL INVOICE', 'AMOUNT', 'Amount Out', 'Difference', 'Money In', 'Money Out', 'Balance', 'Amount(TZS)', 'Amount(USD)', 'Amount', 'Commission', 'USD', 'TZS', 'RATE'];
        var exclude_total = ['AGE', 'Receipt Number', 'Transaction Number', 'Control Number', 'Payment Reference', 'Reference', 'CONTROL NUMBER', 'ID', 'RATE', 'PAYMENT REFERENCE', 'S/N', 'TOTAL NUMBER'];
        var header1 = "", header2 = "", column_count = 1, footer = "";
        var total_row_data = {};
        var table = "<table class='table table-condensed table-striped table-bordered report-table-for-export'>";
        table += "<thead><tr>";

        // Add header for S/N
        header2 += "<th class='text-center'> S/N </th>";

        // Process header columns
        angular.forEach(rows[0], function (value, key) {
            if (money_columns.indexOf(key) >= 0) {
                header2 += "<th class='text-center'>" + key +  "</th>";
            } else {
                header2 += "<th class='text-center'>" + key + "</th>";
            }
            column_count += 1; // Increment column count for each data column
            total_row_data[key] = $.isNumeric(value) && exclude_total.indexOf(key) < 0 ? 0 : "";
        });

        if (title.length > 0) {
            header1 += "<th class='text-center text-white' style='background:rgb(224,224,224)' colspan='" + column_count + "'>" + title + "</th></tr><tr>";
        }

        if (show_opening_closing) {
            header1 += "<th>Opening Balance</th><th class='text-right' colspan='" + (column_count - 1) + "'>" + $filter('number')(parseFloat(opening_balance), 2) + " TZS</th></tr>";
            footer = "<tr><th>Closing Balance</th><th class='text-right' colspan='" + (column_count - 1) + "'>" + $filter('number')(parseFloat(closing_balance), 2) + " TZS</th></tr>";
        }

        table += header1 + header2;
        table += "</tr></thead>";
        table += "<tbody>";

        // Process table rows
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            table += "<tr>";
            table += `<td>${i + 1}</td>`; // Add S/N
            angular.forEach(row, function (value, key) {
                var display_value = value;
                var align = '';
                if (money_columns.indexOf(key) >= 0) {
                    display_value = $filter('number')(parseFloat(value), 2);
                    align = ' class="text-right"';
                }
                table += `<td${align}>${display_value}</td>`;
                if ($.isNumeric(value) && exclude_total.indexOf(key) < 0) {
                    total_row_data[key] = parseFloat(total_row_data[key]) + parseFloat(value);
                }
            });
            table += "</tr>";
        }

        table += "</tbody>";
        table += '<tfoot><tr>';

        // Ensure the total row has the correct number of columns
        // table += "<td></td>"; // S/N column
        table += "<td class='text-center' colspan=''>TOTAL</td>";
        // Process total values for each column
        angular.forEach(total_row_data, function (value, key) {
            var output = "";
            if (key !== 'S/N') {
                output = value;
            } else {
                table += "<th class='column-total-cell'>TOTAL</th>";
            }
            if ($scope.ReportOptions.Type !== 5) {
                table += "<th class='column-total-cell'>" + $filter('number')(parseFloat(output), 2) + "</th>";
            }
        });

        table += footer;
        table += "</tfoot></table>";

        return table;
    };

    $scope.writeStatementReport = function (rows) {
        var money_columns = ['Balance', 'Amount'];
        var header1 = "", header2 = "", column_count = 0, footer = "";
        var total_row_data = [];
        var table = "<table class='table table-condensed table-striped table-bordered report-table-for-export'>";
        table += "<thead><tr>";

        angular.forEach(rows[0], function (value, key) {
            column_count += 1;
//            total_row_data[key] = $.isNumeric(value) ? 0 : "";
            if (money_columns.indexOf(key) >= 0) {
                header2 += "<th class='text-center'>" + key + ' (TZS)' + "</th>";
            } else {
                header2 += "<th class='text-center'>" + key + "</th>";
            }

        });
        var balance = rows[0]['Balance'];
        if (rows[0]['Balance'] === '' || rows[0]['Balance'] === null) {
            balance = 0;
        }
        table += "<th>Opening Balance</th><th class='text-right' colspan='" + (column_count - 1) + "'>" + $filter('number')(parseFloat(balance), 2) + " TZS</th></tr>";

        table += header1 + header2;
        table += "</tr></thead>";
        table += "<tbody>";
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            table += "<tr>";
            angular.forEach(row, function (value, key) {
                var display_value = value;
                var align = '';
                if (money_columns.indexOf(key) >= 0) {

                    if (key === "Balance") {
                        if ((row['Description'].indexOf('TIGO-PESA') > -1 || row['Description'].indexOf('MPESA') > -1) && $scope.institution == 30000004) {
                            value = balance;
                        } else {
                            balance = (row['Type'] === "Debit") ? (balance - row['Amount']) : (parseFloat(balance) + parseFloat(row['Amount']));

                            value = balance;
                        }
                    }

                    if (key !== "Balance") {
                        if (row['Type'] === "Debit") {
                            display_value = "-" + $filter('number')(parseFloat(row['Amount']), 2);
                        } else {
                            display_value = "+" + $filter('number')(parseFloat(row['Amount']), 2);
                        }
                    } else {
                        display_value = $filter('number')(parseFloat(value), 2);
                    }

                    align = ' class="text-right"';
                }
                table += `<td${align}>${display_value}</td>`;
            });
            table + "</tr>";
        }
        table += "</tbody>";
        table += '<tfoot><tr>';
        table += "<tr><th>Closing Balance</th><th class='text-right' colspan='" + (column_count - 1) + "'>" + $filter('number')(parseFloat(balance), 2) + " TZS</th></tr>";
        table += "</tfoot></table>";

        return table;
    };

    $scope.writeCommissionReport = function (rows) {
        var money_columns = ['Commission', 'Amount', 'BCX Commission'];
        var header1 = "", header2 = "", column_count = 0, footer = "";
        var total_row_data = [];
        var table = "<table class='table table-condensed table-striped table-bordered report-table-for-export'>";
        table += "<thead><tr>";

        angular.forEach(rows[0], function (value, key) {
            column_count += 1;
//            total_row_data[key] = $.isNumeric(value) ? 0 : "";
            if (money_columns.indexOf(key) >= 0) {
                header2 += "<th class='text-center'>" + key + ' (TZS)' + "</th>";
            } else {
                header2 += "<th class='text-center'>" + key + "</th>";
            }

        });

        table += header1 + header2;
        table += "</tr></thead>";
        table += "<tbody>";
        var total_amount = 0;
        var total_commission = 0;
        var total_bcx_commission = 0;
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            table += "<tr>";
            angular.forEach(row, function (value, key) {
                var display_value = value;
                var align = '';
                if (money_columns.indexOf(key) >= 0) {
                    if (row['Type'] === "Debit") {
                        display_value = "-" + $filter('number')(parseFloat(row[key]), 2);
                    } else {
                        display_value = "+" + $filter('number')(parseFloat(row[key]), 2);
                    }

                    align = ' class="text-right"';
                }
                table += `<td${align}>${display_value}</td>`;
            });
            table + "</tr>";
            if (row['Type'] === "Debit") {
                total_amount = total_amount - parseFloat(row['Amount']);
                total_commission = total_commission - parseFloat(row['Commission']);
                total_bcx_commission = total_bcx_commission - parseFloat(row['BCX Commission']);
            } else {
                total_amount = total_amount + parseFloat(row['Amount']);
                total_commission = total_commission + parseFloat(row['Commission']);
                total_bcx_commission = total_bcx_commission + parseFloat(row['BCX Commission']);
            }
        }
        table += "</tbody>";
        table += '<tfoot><tr>';
        table += "<tr><th colspan='" + (column_count - 3) + "'>Total</th>";
        table += "<th class='text-right'>" + $filter('number')(parseFloat(total_amount), 2) + "</th>";
        table += "<th class='text-right'>" + $filter('number')(parseFloat(total_commission), 2) + "</th>";
        table += "<th class='text-right'>" + $filter('number')(parseFloat(total_bcx_commission), 2) + "</th></tr>";
        table += "</tfoot></table>";

        return table;
    };
    $scope.submitToLab = function (data) {
        console.log(data);
        $http({
            method: 'POST',
            url: app_url + "/Application/submitToLab",
            data: data, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data) {
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {
                var response = $(data).find('#mabrexPageContent').text().trim();
                if (response === '200' || response === '201') {
                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                } else {
                    $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
                }
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');
                    if (response === '200') {
                        location.reload();
                    }
                }, 4000);
            }
        });
    }
});
