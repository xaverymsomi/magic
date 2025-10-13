<?php

/**
 * @group applicant
 * @filesource /utility/report subscription/new
 * @author Said M
 */
?>

<div id="page-content">
    <form name="subscription" ng-submit="saveForm()" novalidate>
        <div class="modal-header" style="background: linear-gradient(to right, #000201, #085A78); color: white;">
            <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
            <h4 class="modal-title ocean"><i class="pe pe-7s-user pe-fw pe-va pe-2x"></i> Add Subscriber</h4>
        </div>
        <div class="modal-body">
            <div class="form-horizontal">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="form-group">
                                    <label for="user_id" class="col-md-3 control-label">User Name</label>
                                    <div class="col-md-9">
                                        <select id="report_subscription_user" class="form-control" ng-options="user as user.name for user in application_users track by user.id" ng-model="selected_user" ng-change="app_selected_user = selected_user.id; getUserReportSubscription(app_selected_user)">
                                            <option value="">Select User</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="table-responsive" ng-hide="app_selected_user == ''">
                                    <table class="table table-striped table-hover table-condensed table-bordered" id="user_subscriber">
                                        <thead>
                                            <tr>
                                                <th rowspan="2" class="text-center"> REPORT TYPE </th>
                                                <th colspan="{{frequencies.length}}" class="text-center"> FREQUENCY </th>
                                            </tr>
                                            <tr>
                                                <th ng-repeat="freq in frequencies" class="text-center">{{freq.frequency}}<br><input type="checkbox" onclick="selectColumn($(this));"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr ng-repeat="type in report_types" data-report-type="{{type.id}}">
                                                <td>{{type.f_type}}</td>
                                                <td ng-repeat="freq in type.t_frequencies" class="text-center"><input type="checkbox" ng-checked="freq.state == 1" data-frequency="{{freq.id}}"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-success" name="submit"> Save </button>
            <button ng-click="cancel()" type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
        </div>
    </form>
</div>