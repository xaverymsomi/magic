<div id="page-content">
    <div id="data_content"
        data-form="<?php echo json_encode([])?>"
        data-dropdowns="<?php echo json_encode([])?>"></div>
    <div id="display_content">
        <div class="modal-header" style="background:#148F77; color: white;">
            <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
            <h4 class="modal-title ocean"><i class="pe pe-7s-info pe-fw pe-va pe-2x"></i><?php echo htmlspecialchars($this->title, ENT_NOQUOTES, 'UTF-8')?></h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-lg-2"></div>
                <div class="col-lg-8">
                    <h2 class="text-info text-center"><i class="pe pe-7s-info pe-fw pe-va pe-2x"></i><?php echo $this->subtitle ?></h2>
                    <p class="text-center">Please contact your system administrator for more information about this feature.</p>
                </div>
                <div class="col-lg-2"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" ng-click="cancel()" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>