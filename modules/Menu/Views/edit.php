<div id="page-content">
    <div id="data_content" 
        data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns,JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"></div>
    <div id="display_content">
        <form name="menu" ng-submit="saveProfileOperation('Menu', 'post_edit')" novalidate>
            <div class="modal-header"  style="background: rgb(135, 37, 94); /* Old browsers */
             background: -moz-linear-gradient(-45deg, rgba(135, 37, 94) 34%, rgba(53,115,143,1) 72%); /* FF3.6-15 */
             background: -webkit-linear-gradient(-45deg, rgba(135, 37, 94) 34%,rgba(53,115,143,1) 72%); /* Chrome10-25,Safari5.1-6 */
             background: linear-gradient(135deg, rgba(135, 37, 94) 34%,rgba(53,115,143,1) 72%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
             filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#14356e', endColorstr='#35738f',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
             color: white;">
                <button type="button"  ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-cash pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <div class="form-group">
                        <label for="txt_name" class="col-lg-3 control-label">Name</label>
                        <div class="col-lg-9">
                            <input type="text" id="txt_name" placeholder="Enter name" name="txt_name"  class="form-control" ng-class="menu.txt_name.$invalid && !menu.txt_name.$pristine" ng-model="form.txt_name" required/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="txt_icon" class="col-lg-3 control-label">Icon</label>
                        <div class="col-lg-9">
                            <input type="text" id="txt_icon" placeholder="Enter icon" name="txt_icon" class="form-control"  ng-class="menu.txt_icon.$invalid && !menu.txt_icon.$pristine" ng-model="form.txt_icon" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">Relation</label>
                        <label class="radio-inline col-md-2 control-label"><input type="radio" id="hideParent" name="isParent" value="0" ng-click="isParent()" ng-model="fom.relation" >Menu</label>
                        <label class="radio-inline col-md-2 control-label"><input type="radio" id="showParent" name="isParent" value="1" ng-click="isParent();getLastPosition()" ng-model="form.relation" >Sub menu</label>
                    </div>
                    <div class="form-group" id="parent">
                        <label for="int_parent" class="col-lg-3 control-label">Parent</label>
                        <div class="col-lg-9" >
                            <select  class="form-control" id="int_parent" name="int_parent" ng-model="form.int_parent" 
                                     ng-options="value.id as value.name for (key, value) in dropdowns.int_parent_ids">
                                <option value=""> Select  Main Menu</option>
                            </select>
                        </div>
                    </div>
                     <div class="form-group" id="opt_mx_permission_id">
                        <label for="int_parent" class="col-lg-3 control-label">Permission</label>
                        <div class="col-lg-9" >
                            <select  class="form-control" id="opt_mx_permission_id" name="int_parent" ng-model="form.opt_mx_permission_id" 
                                     ng-options="value.id as value.name for (key, value) in dropdowns.opt_mx_permission_ids">
                                <option value=""> Select  Permission</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="txt_link" class="col-lg-3 control-label">Link</label>
                            <div class="col-lg-9">
                                <input type="text" id="txt_link" placeholder="Enter link" name="txt_link" value=""  class="form-control" ng-class="menu.txt_link.$invalid && !menu.txt_link.$pristine" ng-model="form.txt_link"/>
                            </div>
                    </div>
                    <div class="form-group">
                        <label for="txt_title" class="col-lg-3 control-label">Title</label>
                        <div class="col-lg-9">
                            <input type="text" id="txt_title" placeholder="Enter title" name="txt_title"  class="form-control" ng-class="menu.txt_title.$invalid && !menu.txt_title.$pristine" ng-model="form.txt_title" required/>
                        </div>
                    </div> <div class="form-group">
                        <label for="txt_icon" class="col-lg-3 control-label">Position</label>
                        <div class="col-lg-9">
                            <input type="number" min="1" id="int_position" placeholder="Enter Position" name="int_position" class="form-control"  ng-class="menu.int_position.$invalid && !menu.int_position.$pristine" ng-model="form.int_position" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="menu.$invalid || ProcessingData === true" class="btn btn-info" name="submit">Submit</button>
                <button type="button" ng-click="cancel()" class="btn btn-danger" data-dismiss="modal" ng-disabled="ProcessingData === true">Close</button>
            </div>
        </form>
    </div>
</div>
<!--end div-->