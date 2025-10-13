<div id="page-content">
    <!-- Controller -->
    <div ng-controller="permissionCtrl">
        <div class='container-fluid'>
            <ul class="nav nav-tabs">
                <li ng-class="{active: isActiveTab(1)}"><a href="#" ng-click="setActiveTab(1);">Menu Management</a></li>
            </ul>
        </div>
        <br>
        <div ng-show="isActiveTab(1);" ng-init="getMenuDropdowns(<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>)">
            <div class="row">
                <!-- Section -->
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="orange">Add New Menu</h4>
                        </div>
                        <div class="panel-body">
                            <form class="form-horizontal" id="new_menu" name="new_menu" novalidate>
                                <div class="form-group">
                                    <label for="txt_name" class="col-lg-3 control-label">Name</label>
                                    <div class="col-lg-9">
                                        <input type="text" id="txt_name" placeholder="Enter name" name="txt_name"  class="form-control" ng-class="menu.txt_name.$invalid && !menu.txt_name.$pristine" ng-model="new_menu_form.txt_name" required/>
                                    </div>
                                </div>

                                <div class="form-group" ng-if="new_menu_form.relation == 0">
                                    <label for="txt_icon" class="col-lg-3 control-label">Icon</label>
                                    <div class="col-lg-9">
                                        <input type="text" id="txt_icon" placeholder="Enter icon" name="txt_icon" class="form-control"  ng-class="menu.txt_icon.$invalid && !menu.txt_icon.$pristine" ng-model="new_menu_form.txt_icon" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="" class="col-lg-3 control-label">Relation</label>
                                    <label class="radio-inline col-md-2 control-label"><input type="radio" id="hideParent" name="isParent" value="0" ng-model="new_menu_form.relation" checked="checked" >Menu</label>
                                    <label class="radio-inline col-md-2 control-label"><input type="radio" id="showParent" name="isParent" value="1" ng-click="new_menu_form.txt_icon = ''" ng-model="new_menu_form.relation" >Sub menu</label>


                                </div>
                                <div class="form-group" id="parent" ng-if="new_menu_form.relation > 0">
                                    <label for="int_parent" class="col-lg-3 control-label">Parent</label>
                                    <div class="col-lg-9" >
                                        <select  class="form-control" id="int_parent" name="int_parent" ng-model="new_menu_form.int_parent">
                                            <option value=""> Select  Main Menu</option>
                                            <option ng-repeat="(key,value) in dropdowns.all_parents" value="{{value.id}}"> {{value.txt_name}}</option>
                                            <!--user options comes from database-->
                                        </select>
                                    </div>
                                </div>
                                <!--                                <div class="form-group" id="opt_mx_permission_id">
                                                                    <label for="int_parent" class="col-lg-3 control-label">Permission</label>
                                                                    <div class="col-lg-9" >
                                                                        <select  class="form-control" id="opt_mx_permission_id" name="int_parent" ng-model="form.opt_mx_permission_id" 
                                                                                 ng-options="value.id as value.name for (key, value) in dropdowns.opt_mx_permission_ids">
                                                                            <option value=""> Select  Permission</option>
                                                                        </select>
                                                                    </div>
                                                                </div>-->

                                <div class="form-group" ng-if="new_menu_form.relation == 0">
                                    <label for="int_position" class="col-lg-3 control-label">Position</label>
                                    <div class="col-lg-9">
                                        <input type="number" id="int_position" placeholder="Enter position" name="int_position" class="form-control"  ng-class="menu.int_position.$invalid && !menu.int_position.$pristine" ng-model="new_menu_form.int_position" min="2" required=""/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="txt_link" class="col-lg-3 control-label">Link</label>
                                    <div class="col-lg-9">
                                        <input type="text" id="txt_link" placeholder="Enter link" name="txt_link" value=""  class="form-control" ng-class="menu.txt_link.$invalid && !menu.txt_link.$pristine" ng-model="new_menu_form.txt_link"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="txt_title" class="col-lg-3 control-label">Title</label>
                                    <div class="col-lg-9">
                                        <input type="text" id="txt_title" placeholder="Enter title" name="txt_title"  class="form-control" ng-class="menu.txt_title.$invalid && !menu.txt_title.$pristine" ng-model="new_menu_form.txt_title" required/>
                                    </div>
                                </div>
                                <div class="col-md-offset-3 col-md-9">
                                    <button type="submit" class="btn btn-default green" ng-disabled="new_menu.$invalid" ng-click="saveMenu();">Add Menu</button>
                                </div>
                            </form>
                            <!-- End of Select User Form -->

                        </div>
                    </div>
                </div>

                <!-- Available  Permission  -->
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="blue">Available Menu</h4>
                        </div>
                        <div class="panel-body">

                            <!--Group Permission Table-->
                            <div class="table-responsive scrolled-div" style="max-height: 70vh">
                                <table class="table table-striped table-hover table-condensed table-bordered" id="menus_table">                              
                                    <tbody ng-repeat="(key, value) in dropdowns.all_menus">
                                        <tr style="background-color: #D5D8DC !important;">
                                            <th >{{value.int_position}} {{value.txt_name}}</th> 
                                            <th class="text-center">
                                                <a id="Edit" style="text-decoration:none; margin:1px; padding:1px;" href="#" class="ccm" ng-click="showActionForm(value.txt_row_value, 'Menu', 'edit')">
                                                    <i class="fa fa-edit text-centered mxtooltip" title="Edit" style="vertical-align:middle;"></i>
                                                </a>&nbsp;
                                            </th>                                           
                                        </tr>
                                        <tr ng-repeat="menu in value.children">
                                            <td>{{menu.txt_name}}</td>
                                            <td class="text-center">
                                                <a id="Edit" style="text-decoration:none; margin:1px; padding:1px;" href="#" class="ccm" ng-click="showActionForm(menu.txt_row_value, 'Menu', 'edit')">
                                                    <i class="fa fa-edit text-centered mxtooltip" title="Edit" style="vertical-align:middle;"></i>
                                                </a>&nbsp;
                                            </td>

                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!--End of Group Permission Table-->
                        </div>
                    </div>
                </div>
                <!--End of Assign Group Permission-->

            </div>

        </div>
    </div>
    <!-- End of Container 3 -->
</div>
<!-- End of Permission Section -->