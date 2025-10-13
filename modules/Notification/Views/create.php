<div id="page-content">
    <div id="data_content"
         data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns,JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"></div>
    <div id="display_content">
        <!-- <form name="institution" enctype="multipart/form-data" ng-submit="saveFormWithUploads('Institution', 'save', ['institution_logo','app_logo','institution_splash'])" novalidate> -->
        <form name="institution" enctype="multipart/form-data" ng-submit="saveForm()" novalidate>
            <div class="modal-header" style="background-color: silver; color: black;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: black; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-culture pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <?php
                    include 'forms/notification.html';
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="institution.$invalid || ProcessingData === true" class="btn btn-info" name="submit"> Submit </button>
                <button ng-disabled="ProcessingData === true" ng-click="cancel()"type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>