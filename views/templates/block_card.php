<div id="page-content">
    <div id="data_content"
        data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-dropdowns="<?php echo json_encode([])?>"></div>
    <div id="display_content">
        <form name="block" ng-submit="saveProfileOperation('<?php echo $this->controller ?>', '<?php echo $this->action ?>')" novalidate>
            <div class="modal-header" style="background:#C70039; color: white;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-lock pe-fw pe-va pe-2x"></i><?php echo htmlspecialchars($this->title, ENT_NOQUOTES, 'UTF-8')?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <div class="form-group">
                        <label for="checkme" class="col-lg-2 control-label"></label>
                        <div class="col-lg-8">
                            <h2 class="text-danger"><i class="pe pe-7s-lock pe-fw pe-va pe-2x"></i><?php echo $this->subtitle ?></h2>
                            <span style="padding-left: 90px;">In order to block <?php echo htmlspecialchars($this->name, ENT_NOQUOTES, 'UTF-8')?>, you must click the</span>
                            <br><span style="padding-left: 90px;">“I have read and agreed to <?php echo APP_NAME ?> blockage policy” and then click the “Submit” button.</span><br><br>
                            <span style="padding-left: 90px;"><input type="checkbox" id="checkme" name="checkme"  ng-model="checkme" required> </span>
                            <strong>I have read and agreed to the <a target="_blank" href="/assets/public/Privacy%20Policy%20for%20Merchant.pdf"><span class="text-danger"><?php echo APP_NAME ?> Blockage Policy</span> </a></strong>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="tar_reason" class="col-lg-2 control-label">Reason</label>
                        <div class="col-lg-8">
                            <textarea class="form-control" cols="50" id="tar_reason" placeholder="Write reason" name="tar_reason" ng-class="form.tar_reason.$invalid && !form.tar_reason.$pristine" ng-model="form.tar_reason" required></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="block.$invalid || ProcessingData === true" class="btn btn-info" name="submit">Submit</button>
                <button ng-disabled="ProcessingData === true" type="button" ng-click="cancel()" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>