<div id="page-content">
    <div id="data_content" 
        data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-disabled="<?php echo htmlspecialchars(json_encode($this->disabled, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"
        data-client-functions="<?php echo htmlspecialchars(json_encode($this->client_functions, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8')?>"></div>
    <div id="display_content">
        <form name="<?php echo $this->form_name ?>"  ng-submit="saveProfileOperation('<?php echo $this->controller ?>', '<?php echo $this->action ?>')" id="edit_institution_service" novalidate>
            <div class="modal-header" style="background: linear-gradient(to right, #000201, #085A78); color: white;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-<?php echo $this->icon ?> pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>   
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <?php 
                        include MX17_APP_ROOT . '/forms/' . $this->template_url;
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="institution_service.$invalid || ProcessingData === true" class="btn btn-info" name="submit">Submit</button>
                <button ng-disabled="ProcessingData === true" ng-click="cancel()"type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>