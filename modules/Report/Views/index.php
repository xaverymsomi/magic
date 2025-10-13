
<div id="page-content">
    <?php
    $report_types = $this->report_types;
    $login_type = $_SESSION['login_type'];
    ?>
    <div ng-controller="reportCtrl">
        <div class="row">
            <div class="col-md-12">
                <div class="notification-area"></div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>
                            <span class="blue"><?php echo trans('report_title'); ?> </span>
                            <span class="pull-right" ng-click="togglePanelReport()">
                                <i id="reportHeaderToggler" data-toggle="collapse" data-target="#collapsePanelReport" class="pe pe-2x pe-7s-angle-up"></i>
                            </span>
                        </h4>
                        <h5 class="orange"><?php echo trans('report_sub_title'); ?> </h5>
                    </div>
                    <div class="panel-body collapse in" id="collapsePanelReport" >
                        <form id="ReportForm">
                            <ul class="nav nav-pills" style="border-bottom: 1px solid #ccc; padding-bottom: 5px;"
                                ng-init="getFormFields('General_Report', ReportOptions.Type)">
                                    <?php
                                    foreach ($report_types as $type) {
                                        $is_active = '';

                                        if ($login_type == 'stakeholder') {
                                            if ($type['report_type'] == 'Transaction_Report') {
                                                $is_active = 'active';
                                            }
                                        } else {
                                            if ($type['report_type'] == 'General_Report') {
                                                $is_active = 'active';
                                            }
                                        }
                                        ?>
                                    <li class="<?php echo $is_active; ?>"><a href="#<?php echo $type['report_type']; ?>" ng-click="getFormFields('<?php echo $type['report_type']; ?>', <?php echo $type['report_id'] ?>)" data-toggle="tab"><?php echo $type['report_title']; ?></a></li>
                                <?php } ?>
                            </ul>

                            <div id="myTabContent" class="tab-content">
                                <?php
                                foreach ($report_types as $type) {
                                    $is_active = '';
                                    if ($_SESSION['login_type'] == 'stakeholder') {
                                        if ($type['report_type'] == 'Transaction_Report') {
                                            $is_active = 'active in';
                                        }
                                    } else {
                                        if ($type['report_type'] == 'General_Report') {
                                            $is_active = 'active in';
                                        }
                                    }
                                    ?>
                                    <div class="tab-pane fade <?php echo $is_active; ?>" id="<?php echo $type['report_type']; ?>">
                                        <?php include $type['report_header']; ?>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="form-group form-inline mx-inline-form">
                                <div ng-if="ReportOptions.Type === 5 || ReportOptions.Type === 6" class="input-group input-group-sm">
                                    <span class="input-group-addon">Date</span>
                                    <input type="date" class="form-control" id="FromDate" name="FromDate"
                                           ng-class="ReportForm.FromDate.$invalid && !ReportForm.FromDate.$pristine"
                                           ng-model="ReportOptions.StartDate" required>
                                </div>
                                <div ng-if="ReportOptions.Type !== 5 && ReportOptions.Type !== 6" class="input-group input-group-sm">
                                    <span class="input-group-addon"><?php echo trans('date_from'); ?></span>
                                    <input type="date" class="form-control" id="FromDate" name="FromDate"
                                           ng-class="ReportForm.FromDate.$invalid && !ReportForm.FromDate.$pristine"
                                           ng-model="ReportOptions.StartDate" required>
                                </div>
                                <div ng-if="ReportOptions.Type !== 5 && ReportOptions.Type !== 6" class="input-group input-group-sm">
                                    <span class="input-group-addon"><?php echo trans('date_to'); ?></span>
                                    <input type="date" class="form-control" id="ToDate" name="ToDate"
                                           ng-class="ReportForm.ToDate.$invalid && !ReportForm.ToDate.$pristine"
                                           ng-model="ReportOptions.EndDate" required>
                                </div>

                                <div class="input-group input-group-sm" ng-if="ReportCategories.length > 0">
                                    <span class="input-group-addon">View</span>
                                    <select name="ReportCategory" id="ReportCategory" class="form-control" ng-model="ReportOptions.Category"
                                            ng-options="field.Id as field.Name for (key, field) in ReportCategories"
                                            ng-class="ReportForm.ReportCategory.$invalid && !ReportForm.ReportCategory.$pristine" required>
                                    </select>
                                </div>

                                <div class="input-group input-group-sm" ng-if="ReportFilters.length > 0">
                                    <span class="input-group-addon">Filter By</span>
                                    <select name="ReportFilter" id="ReportFilter" class="form-control" ng-model="ReportOptions.FilterField"
                                            ng-options="field.Id as field.Name for (key, field) in ReportFilters"
                                            ng-change="getFilteringValues()"
                                            ng-class="ReportForm.ReportFilter.$invalid && !ReportForm.ReportFilter.$pristine" required>
                                    </select>
                                </div>

                                <div class="input-group input-group-sm" ng-if="ReportFilterValues.length > 0 && (ReportOptions.FilterField !== 6)">
                                    <span class="input-group-addon">Filter Value</span>
                                    <select name="FilterFieldValue" id="FilterFieldValue" class="form-control" ng-model="ReportOptions.FilterFieldValue"
                                            ng-options="field as field.Name for field in ReportFilterValues"
                                            ng-change="getAuditActions()"
                                            ng-class="ReportForm.FilterFieldValue.$invalid && !ReportForm.FilterFieldValue.$pristine" required>
                                    </select>
                                </div>

                                <div class="input-group input-group-sm" ng-if="ReportOptions.Type === 9 && (ReportOptions.FilterField === 6 || ReportOptions.FilterField === 7)">
                                    <span class="input-group-addon" ng-if="ReportOptions.FilterField === 6">User</span>
                                    <span class="input-group-addon" ng-if="ReportOptions.FilterField === 7">Subscriber</span>
                                    <ui-select ng-model="ReportOptions.FilterFieldValue">
                                        <ui-select-match>
                                            <span ng-bind="$select.selected.Name"></span>
                                        </ui-select-match>
                                        <ui-select-choices repeat="item in autoCompleteSelectOptions.ReportFilterValues" refresh="autoComplete($select.search,'ReportFilterValues')" refresh-delay="0" minimum-input-length="1">
                                            <span ng-bind="item.Name"></span>
                                        </ui-select-choices>
                                    </ui-select>
                                </div>

                                <div class="input-group input-group-sm" ng-if="AuditActions.length > 0">
                                    <span class="input-group-addon">Action</span>
                                    <select name="AuditAction" id="AuditAction" class="form-control" ng-model="ReportOptions.AuditAction"
                                            ng-options="field as field.Name for field in  AuditActions"
                                            ng-class="ReportForm.AuditAction.$invalid && !ReportForm.AuditAction.$pristine" required>
                                    </select>
                                </div>
                                <div class="input-group input-group-sm" ng-if="AuditUsers.length > 0">
                                    <span class="input-group-addon">User</span>
                                    <select name="AuditUsers" id="AuditUsers" class="form-control" ng-model="ReportOptions.AuditUsers"
                                            ng-options="field.Id as field.Name for (key, field) in AuditUsers"
                                            ng-class="ReportForm.AuditUsers.$invalid && !ReportForm.AuditUsers.$pristine" required>
                                    </select>
                                </div>
                                <div class="input-group input-group-sm" ng-if="PaymentStatusValues.length > 0">
                                    <span class="input-group-addon">Payment Status</span>
                                    <select name="PaymentStatusValues" id="ReportFilterFerry" class="form-control" ng-model="ReportOptions.PaymentStatusValues"
                                            ng-options="field.Id as field.Name for (key, field) in PaymentStatusValues"
                                            ng-class="ReportForm.PaymentStatusValues.$invalid && !ReportForm.PaymentStatusValues.$pristine" required>
                                    </select>
                                </div>

<!--                                <div class="input-group input-group-sm" ng-if="PaymentProviders.length > 0 && ReportOptions.Type === 10">-->
<!--                                    <span class="input-group-addon">Providers</span>-->
<!--                                    <select name="PaymentProvider" id="PaymentProvider" class="form-control" ng-model="ReportOptions.PaymentProvider"-->
<!--                                            ng-options="field.Id as field.Name for field in PaymentProviders"-->
<!--                                            ng-class="ReportForm.PaymentProvider.$invalid && !ReportForm.PaymentProvider.$pristine" required>-->
<!--                                    </select>-->
<!--                                </div>-->

                                <div class="input-group input-group-sm" ng-if="LabValues.length > 0">
                                    <span class="input-group-addon">Assign Lab</span>
                                    <select name="PaymentStatusValues" id="ReportFilterFerry" class="form-control" ng-model="ReportOptions.FilterFieldValue"
                                            ng-options="field as field.Name for field in  LabValues"
                                            ng-class="ReportForm.FilterFieldValue.$invalid && !ReportForm.FilterFieldValue.$pristine" required>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <p class="text-danger" ng-if="ReportOptions.Category == 0 && ReportOptions.GroupingField == 3">You can't group a summarized report by Status - only <b>date</b> and <b>nature</b> are allowed. Choose a detailed report instead.</p>
                            </div>
                            <div class="form-group">
                                <button type="button" ng-disabled="(ReportOptions.FilterFieldValue.Id == 0 && ReportOptions.Type === 5) || (ReportOptions.Category == 0 && ReportOptions.FilterField == 1 && [5].indexOf(ReportOptions.Type) > 0) || ReportForm.$invalid || (ReportOptions.Category == 0 && ReportOptions.GroupingField == 3)" class="btn btn-info" ng-click="generateReport();">View Report</button>
                                <button type="button" ng-if="ReportIsOpen" class="btn btn-danger" ng-click="closeReportViewer();">Close Report Viewer</button>
                            </div>
                        </form>

                    </div>

                </div>
                <!--Report Preview Panel-->
                <div class="panel panel-default hide" id="preview_panel">
                    <div class="panel-heading" style="margin: 0 !important;">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="blue">Report Preview Panel Can Only Display Up To 10,000 Records.</h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <button ng-if=" ReportOptions.Type === 5" class="btn btn-default" title="Export to Excel"
                                        data-toggle="collapse" data-target="#TransferLog" aria-expanded="false" aria-controls="TransferLog">
                                    <i class="fa fa-file-excel-o text-success"></i></button>

                                <button ng-if="ReportOptions.Type === 1" class="btn btn-default" onclick="ExportTableToExcel('Report')" title="Export to Excel"><i class="fa fa-file-excel-o text-success"></i></button>
                                <button ng-if="ReportOptions.Type === 1" class="btn btn-default" onclick="ExportTableToPDF()" title="PDF Version"><i class="fa fa-file-pdf-o text-danger"></i></button>
                                <button class="btn btn-default" ng-if=" [1].indexOf(ReportOptions.Type) <= -1 " ng-click="ExportToExcel(ReportOptions.ReportTitle, records);" title="Export to Excel"><i class="fa fa-file-excel-o text-success"></i></button>
                                <button ng-if="[1].indexOf(ReportOptions.Type) <= -1 " class="btn btn-default" onclick="ExportTableToPDF()" title="PDF Version"><i class="fa fa-file-pdf-o text-danger"></i></button>
                            </div>
                        </div>

                        <div class="collapse" id="TransferLog">
                            <p colspan="6" class="bg-danger" style="border: 1px solid silver;">
                            <p class="text-center" style="font-size: 150%;">You are about to transfer test sample. </p>
                            <p class="text-center" style="font-size: 150%;">The changes can not be undone after submission.</p>
                            <p class="text-center" style="font-size: 150%;">Are you sure you want to do this?</p>
                            <p class="text-center" style="margin-top: 24px;">
                                <button type="button" class="btn btn-success" ng-click="ExportToExcel('labsample', records); submitToLab(records)" data-toggle="collapse" data-target="#TransferLog" aria-expanded="false" aria-controls="TransferLog" style="padding-left: 25px; padding-right: 25px; margin-right: 10px;">YES</button>
                                <button type="button" class="btn btn-danger" data-toggle="collapse" data-target="#TransferLog" aria-expanded="false" aria-controls="TransferLog" style="padding-left: 25px; padding-right: 25px; margin-left: 10px;">NO</button>
                            </p>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div id="reportHtmlSection" class="panel-body div-report-table-for-export" style="overflow-x:auto;">
                                <!--HTML table section -->
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div id="hasMany" class="modal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header btn-danger">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title ">NOTE</h4>
                    </div>
                    <div class="modal-body text-center" style="overflow-x: auto;">
                        <h4 class="text-info">The requested report has a total number {{ totalRecords | number}} records. </h4>
                        <h4 class="text-info">The preview panel will only display <b><span class="text-danger">10,000</span></b> records,</h4>
                        <h4 class="text-info"> To view all records in excel or pdf format please download the report by clicking the following buttons
                        </h4>
                        <h4 class="text-info">
                            <button class="btn btn-default" ng-click="ExportToExcel(ReportOptions.ReportTitle, records);" title="Export to Excel"><i class="fa fa-file-excel-o text-success"></i> </button>
                            <button ng-if=" ReportOptions.Type !== 5 && ReportOptions.Type !== 8" class="btn btn-default" onclick="ExportTableToPDF()" title="PDF Version"><i class="fa fa-file-pdf-o text-danger"></i></button>
                        </h4>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Contents -->
    <div id="DemoModal" class="modal fade">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title">Report PDF Viewer</h4>
                </div>
                <div class="modal-body text-center" id="reportPDFPreview" style="overflow-x: auto;">
                    <!-- PDF Viewer here -->
                </div>
                <div class="modal-footer">
                    <!--<button type="button" class="btn btn-primary" >Download</button>-->
                    <button type="button" class="btn btn-danger" ng-click="cancel()" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="noRecordFound" tabindex="-1" role="dialog" aria-labelledby="noRecordFoundTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <h3 style="color: #0290FC; text-align: center" > <i class="pe pe-7s-info pe-fw pe-va pe-4x"></i>  <br>Sorry!</h3>
                    <h4 style="color: #333333; text-align: center">No Report Found for the records you have specified.</h4>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>