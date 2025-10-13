<div id="page-content">
    <div id="data_content"
        data-form="<?php echo json_encode([])?>"
        data-dropdowns="<?php echo json_encode([])?>"></div>
    <div id="display_content">
        <div class="modal-header" style="background:#C70039; color: white;">
            <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
            <h4 class="modal-title ocean"><i class="pe pe-7s-signal pe-fw pe-va pe-2x"></i>Not Found</h4>
        </div>
        <div class="modal-body">
            <div class="notification-area"></div>
            <div class="form-horizontal">
                <div class="form-group">
                    <label for="checkme" class="col-lg-2 control-label"></label>
                    <div class="col-lg-8">
                        <h2 class="text-danger text-center"><i class="pe pe-7s-lock pe-fw pe-va pe-2x"></i><?php echo $this->subtitle ?></h2>
                        <p class="text-center">Could not find the request data</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" ng-click="cancel()" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>