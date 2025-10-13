<div id="page-content">
    <div id="data_content" data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>" data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
    <div id="display_content">
        <form name="backup" ng-submit="saveForm('post_backup_database')" novalidate>
            <div class="modal-header" style="background: linear-gradient(to right, #000201, #085A78); color: white;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-database pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <div class="form-group">
                        <div class='col-lg-9 col-lg-offset-2'>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" ng-model="confirm_backup"><span class="blue" style="font-size: 14pt;">Please Confirm Database Backup</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="confirm_backup !== true && ProcessingData !== true" class="btn btn-info" name="submit"> Submit </button>
                <button ng-disabled="ProcessingData === true" ng-click="cancel()" type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>