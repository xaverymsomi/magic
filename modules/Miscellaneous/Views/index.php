<div id="page-content">
    <div id="data_content" 
        data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
        data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>">
        <div id="display_content">
            <div class="notification-area"></div>
            <div class="panel panel-default hidden-md hidden-lg">
                <div class="panel-heading">
                    <h4 class="panel-title">Add New Setting</h4>
                </div>
                <div class="panel-body">
                    <form name="rule" novalidate >
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label for="int_mx_rule_id" class="col-lg-12 text-right">Rule:</label>
                                <div class="col-lg-12">
                                    <select name="int_mx_rule_id" id="int_mx_rule_id" class="form-control"
                                            ng-model="form.int_mx_rule_id" required
                                            ng-class="rule.int_mx_rule_id.$invalid && !rule.int_mx_rule_id.$pristine"
                                            ng-change="checkType()"
                                            ng-options="value.id as value.name for (key, value) in dropdowns.opt_mx_rule_ids">
                                        <option value="">Select Rule</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="txt_value" class="col-lg-12 text-right">Value:</label>
                                <div class="col-lg-12">
                                    <input id="txt_value" type="{{form.type}}" ng-class="{'form-control': form.type !== 'checkbox'}" ng-model="form.txt_value" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="dat_effective_start_date" class="col-lg-12 text-right">Effective Start Date:</label>
                                <div class="col-lg-12">
                                    <input type="date" id="dat_effective_start_date" placeholder="Enter effective start date" min="<?php echo date('Y-m-d');?>" name="dat_effective_start_date" class="form-control"  ng-class="rule.dat_effective_start_date.$invalid && !rule.dat_effective_start_date.$pristine" ng-model="form.dat_effective_start_date" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="dat_effective_end_date" class="col-lg-12 text-right">Effective End Date:</label>
                                <div class="col-lg-12">
                                    <input type="date" id="dat_effective_end_date" placeholder="Enter effective end date" min="<?php echo date('Y-m-d');?>" name="dat_effective_end_date" class="form-control"  ng-class="rule.dat_effective_end_date.$invalid && !rule.dat_effective_end_date.$pristine" ng-model="form.dat_effective_end_date" />
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm edit" ng-click="saveMiscellaneous($event, rule)">Save Setting</button>
                        </div>
                    </form>

                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title" style="height: 30px;">
                        <span class="pull-left">Miscellaneous Settings</span>
                        <span class="panel-title pull-right">
                            <button type="button" class="btn btn-primary btn-sm edit" ng-click="viewMiscellaneous('all')">View All Settings</button>
                            <button type="button" class="btn btn-primary btn-sm edit" ng-click="viewMiscellaneous('active')">View Active Settings</button>
                            <button type="button" class="btn btn-primary btn-sm edit" ng-click="viewMiscellaneous('pending')">View Pending Settings</button>
                        </span>
                    </h4>

                </div>
                <div class="panel-body">
                    <table class="table table-hover table-striped ">
                        <thead>
                            <tr class="hidden-xs hidden-sm">
                                <td colspan="2" style="vertical-align: middle">
                                    <select name="int_mx_rule_id" id="int_mx_rule_id" class="form-control"
                                            ng-model="form.int_mx_rule_id" required
                                            ng-class="rule.int_mx_rule_id.$invalid && !rule.int_mx_rule_id.$pristine"
                                            ng-change="checkType()"
                                            ng-options="value.id as value.name for (key, value) in dropdowns.opt_mx_rule_ids">
                                        <option value="">Select Rule</option>
                                    </select>
                                </td>
                                <td style="vertical-align: middle">
                                    <input id="txt_value" type="{{form.type}}" ng-class="{'form-control': form.type !== 'checkbox'}" ng-model="form.txt_value" />
                                </td>
                                <td style="vertical-align: middle">
                                    <input type="date" id="dat_effective_start_date" placeholder="Enter effective start date" min="<?php echo date('Y-m-d');?>" name="dat_effective_start_date" class="form-control"  ng-class="rule.dat_effective_start_date.$invalid && !rule.dat_effective_start_date.$pristine" ng-model="form.dat_effective_start_date" />
                                </td>
                                <td style="vertical-align: middle">
                                    <input type="date" id="dat_effective_end_date" placeholder="Enter effective end date" min="<?php echo date('Y-m-d');?>" name="dat_effective_end_date" class="form-control"  ng-class="rule.dat_effective_end_date.$invalid && !rule.dat_effective_end_date.$pristine" ng-model="form.dat_effective_end_date" />
                                </td>
                                <td style="vertical-align: middle">
                                    <button type="button" class="btn btn-primary btn-sm edit" ng-click="saveMiscellaneous($event, rule)">Save Setting</button>
                                </td>
                            </tr>
                            <tr>
                                <th>Rule</th>
                                <th>Description</th>
                                <th>Current Setting</th>
                                <th>Effective Start Date</th>
                                <th>Effective End Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tr ng-repeat="rule in misc_data">
                            <td>{{rule.txt_name}}</td>
                            <td>{{rule.txt_description}}</td>
                            <td>
                                <span class="rule_value{{rule.config_id}}">{{rule.txt_value}}</span>
                                <span class="rule_editor{{rule.config_id}} hidden">
                                <input type="{{rule.txt_type}}" ng-class="{'form-control': rule.txt_type !== 'checkbox'}" ng-model="rule.txt_value" />
                            </span>
                            </td>
                            <td>
                                <span class="rule_value{{rule.config_id}}">{{rule.dat_effective_start_date | date:"dd-MM-yyyy"}}</span>
                                <span class="rule_editor{{rule.config_id}} hidden">
                                 <input type="date" id="dat_effective_start_date" placeholder="Enter effective start date" min="<?php echo date('Y-m-d');?>" name="dat_effective_start_date" class="form-control"  ng-class="rule.dat_effective_start_date.$invalid && !rule.dat_effective_start_date.$pristine" ng-model="rule.dat_effective_start_date" />
                            </span>
                            </td>
                            <td>
                                <span class="rule_value{{rule.config_id}}">{{rule.dat_effective_end_date | date:"dd-MM-yyyy"}}</span>
                                <span class="rule_editor{{rule.config_id}} hidden">
                                 <input type="date" id="dat_effective_end_date" placeholder="Enter effective end date" min="<?php echo date('Y-m-d');?>" name="dat_effective_end_date" class="form-control"  ng-class="rule.dat_effective_end_date.$invalid && !rule.dat_effective_end_date.$pristine" ng-model="rule.dat_effective_end_date" />
                            </span>
                            </td>
                            <td class="text-right">
                                <button type="button" ng-show="dateDiff(rule.dat_effective_start_date, rule.dat_effective_end_date).value > 0" class="btn btn-primary btn-sm edit" ng-click="editMiscellaneous($event, rule)">Change</button>
                                <button type="button" class="btn btn-success btn-sm hidden save" ng-click="updateMiscellaneous($event, rule)">Save</button>
                                <button type="button" class="btn btn-danger btn-sm hidden cancel" ng-click="cancelMiscellaneous($event, rule)">Cancel</button>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>