<!--
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="green">Add Subscriber</h4>
                </div>
                <div class="panel-body">
                    <form class="form-horizontal" id="user_subscriber">
                        <div class="form-group">
                            <label for="user_id" class="col-md-3 control-label">User Name</label>
                            <div class="col-md-9" >
                                <select class="form-control" ng-options="user as user.name for user in application_users track by user.id" ng-model="selected_user" ng-change="app_selected_user = selected_user.id; getUserReportSubscription(app_selected_user)">
                                    <option value="">Select User</option>
                                    user options comes from database
                                    <option ng-repeat="user in application_users" value="{{user.id}}">{{user.name}}</option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive" ng-hide="app_selected_user == ''">
                        <table class="table table-striped table-hover table-condensed table-bordered" id="user_subscriber">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="text-center"> REPORT TYPE </th>
                                    <th colspan="{{report_types.length}}" class="text-center"> FREQUENCY </th>
                                </tr>
                                <tr>
                                    <th ng-repeat="freq in frequencies" class="text-center">{{freq.frequency}} <input type="checkbox"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="type in report_types" data-report-type="{{type.id}}">
                                    <td>{{type.f_type}}</td>
                                    <td ng-repeat="freq in frequencies" class="text-center"><input type="checkbox" ng-checked="freq.state == 1"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    End of User Group Table
                </div>
            </div>
        </div>
    </div>-->