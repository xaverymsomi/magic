<div id="page-content">
    <div id="data_content"
         data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
    <div id="display_content">
        <form name="invoice"
              ng-submit="saveProfileOperation('<?php echo $this->controller ?>', '<?php echo $this->action ?>')"
              novalidate>
            <div class="modal-header" style="background: linear-gradient(to right, #000201, #085A78); color: white;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal"
                        style="color: white; font-size: 35px;">&times;
                </button>
                <h4 class="modal-title ocean"><i
                            class="pe pe-7s-scissors pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <h5 style="text-align: center; font-size: 1.3em; opacity: 0.6; text-transform: uppercase"><?php echo $this->form_title; ?></h5>
                                    <hr/>
                                    <div class="row">
                                        <div class="col-md-12 col-lg-12">
                                            <div class="row">
                                                <div class="col-md-12 col-lg-12">
                                                    <div class="panel panel-default">
                                                        <div class="panel-body">
                                                            <div class="row">
                                                                <div class="col-md-6 col-lg-6">
                                                                    <div class="form-group">
                                                                        <label for="invoice_no"
                                                                               class="col-md-12 col-lg-12">Invoice
                                                                            Number</label>
                                                                        <div class="col-md-12 col-lg-12">
                                                                            <input type="text" id="invoice_no"
                                                                                   placeholder="Enter invoice number"
                                                                                   name="invoice_no"
                                                                                   class="form-control"
                                                                                   ng-class="invoice.invoice_no.$invalid && !invoice.invoice_no.$pristine"
                                                                                   ng-model="form.invoice_no" required
                                                                                   readonly/>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6 col-lg-6">
                                                                    <div class="form-group">
                                                                        <label for="txt_control_number"
                                                                               class="col-md-12 col-lg-12">Control
                                                                            Number</label>
                                                                        <div class="col-md-12 col-lg-12">
                                                                            <input type="text" id="txt_control_number"
                                                                                   placeholder="Enter control number"
                                                                                   name="txt_control_number"
                                                                                   class="form-control"
                                                                                   ng-class="invoice.txt_control_number.$invalid && !invoice.txt_control_number.$pristine"
                                                                                   ng-model="form.txt_control_number"
                                                                                   required readonly/>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12 col-lg-12">
                                                                    <div class="form-group">
                                                                        <label for="txt_reason"
                                                                               class="col-md-12 col-lg-12">Reason</label>
                                                                        <div class="col-md-12 col-lg-12">
                                                                            <textarea id="txt_reason" rows="4"
                                                                                      placeholder="Enter cancellation reason"
                                                                                      name="txt_reason"
                                                                                      class="form-control"
                                                                                      ng-class="invoice.txt_reason.$invalid && !invoice.txt_reason.$pristine"
                                                                                      ng-model="form.txt_reason"
                                                                                      required>

                                                                            </textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12 col-lg-12">
                                                                    <input type="checkbox" id="confirm" name="confirm"
                                                                           ng-class="invoice.confirm.$invalid && !invoice.confirm.$pristine"
                                                                           ng-model="checkme" required>
                                                                    <strong>
                                                                        <span class="text-danger">Check the box to confirm invoice cancellation.</span>
                                                                    </strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="invoice.$invalid || ProcessingData === true" class="btn btn-info"
                        name="submit">Submit
                </button>
                <button type="button" ng-disabled="ProcessingData === true" ng-click="cancel()" class="btn btn-default"
                        data-dismiss="modal" ng-disabled="ProcessingRequest === true">Close
                </button>
            </div>
        </form>
    </div>
</div>
