<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd..
 *
 * (c) 2022
 *
 */
?>

<div id="page-content">
    <div id="data_content"
         data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>">
    </div>
    <div id="display_content">
        <form name="Notification" ng-submit="saveProfileOperation('Notification', 'post_edit')" novalidate>
            <div class="modal-header" style="background-color: silver; color: black;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: black; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-culture pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <div class="row" style="margin-top: 5px; margin-bottom: 10px;">
                        <div class="col-md-12 col-lg-12 col-xl-12">
                            <h5 class="text-center" style="font-size: 1.7em;">Edit the Notification</h5>
                        </div>
                    </div>
                    <?php
                    include 'forms/notification.html';
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="Notification.$invalid || ProcessingData === true" class="btn btn-info" name="submit"> Submit </button>
                <button ng-disabled="ProcessingData === true" ng-click="cancel() "type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>