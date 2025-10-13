<div id="page-content">
    <div id="data_content"
        data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-dropdowns="<?php echo json_encode([])?>"></div>
    <div id="display_content">
        <form name="approval" ng-submit="saveProfileOperation('<?php echo $this->controller ?>', '<?php echo $this->action ?>')" novalidate>
            <div class="modal-header" style="background:#148F77; color: white;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-unlock pe-fw pe-va pe-2x"></i><?php echo htmlspecialchars($this->title, ENT_NOQUOTES, 'UTF-8')?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <div class="form-group">
                        <label for="checkme" class="col-lg-2 control-label"></label>
                        <div class="col-lg-8">
                            <h2 class="text-success"><i class="pe pe-7s-unlock pe-fw pe-va pe-2x"></i><?php echo $this->subtitle ?></h2>
                            <span style="padding-left: 90px;">In order to approve <?php echo htmlspecialchars($this->name, ENT_NOQUOTES, 'UTF-8')?>, you must click the</span>
                            <br><span style="padding-left: 90px;">“I have read and agreed to <?php echo APP_NAME ?> approval policy” and then click the “Submit” button.</span><br><br>
                            <span style="padding-left: 90px;"><input type="checkbox" id="checkme" name="checkme"  ng-model="checkme" required></span>
                            <strong>I have read and agreed to the <span class="text-success"><a target="_blank" href="/assets/public/Privacy%20Policy%20for%20Merchant.pdf"><?php echo APP_NAME ?> approval Policy </a></span></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="approval.$invalid || ProcessingData === true" class="btn btn-info" name="submit">Submit</button>
                <button ng-disabled="ProcessingData === true" type="button" ng-click="cancel()" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>