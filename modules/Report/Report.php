<?php

namespace Modules\Report;

use Libs\Controller;
use Libs\Perm_Auth;

class Report extends Controller {

    public $model;

    public function __construct() {
        parent::__construct();
        $this->model = new Report_Model();
    }

    public function index() {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_reports') || $perm->verifyPermission('view_statements')) { //checking permission
            $this->view->report_types = $this->model->getReportTypes();
            if (sizeof($this->view->report_types)) {
                $this->render('index');
            } else {
                $this->_permissionDenied(__METHOD__);
            }
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function get_form_fields()
    {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_reports') || $perm->verifyPermission('view_statements')) { //checking permission
            $posted_data = json_decode(file_get_contents("php://input"), true);
            $type = $posted_data['report_type'];
            $this->view->form_fields = $this->model->getReportFormFields($type);
            $this->render('get_form_fields');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function get_filtering_fields()
    {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_reports') || $perm->verifyPermission('view_statements')) { //checking permission
            $posted_data = json_decode(file_get_contents("php://input"), true);
            $filter = $posted_data['filter_criteria'];
            $type = $posted_data['report_type'];
            $category = $posted_data['report_category'];
            $this->view->filtering_fields = $this->model->getReportFilterValues($filter, $type, $category);
            $this->render('get_filtering_fields');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function get_audit_actions()
    {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_reports') || $perm->verifyPermission('view_statements')) { //checking permission
            $posted_data = json_decode(file_get_contents("php://input"), true);
            $table = $posted_data['filter_value'];
            echo json_encode($this->model->getAuditActions($table));
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function generate_report() {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_reports') || $perm->verifyPermission('view_statements')) {
            $this->view->posted_data = json_decode(file_get_contents("php://input"), true);

            $class_object= 'Modules\\Report\\Reports\\Generate'. str_replace('_', '', $this->view->posted_data['report']);
            $report = (new $class_object)->init($this->view->posted_data);

          } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function subscribers()
    {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_subscribers')) {
            $this->view->title = 'Report Subscribers';
            $this->view->buttons = $this->model->getControls('subscribers');
            $this->view->class = get_class($this->model);
            $this->view->actions = $this->model->getActions('subscribers');
            $this->view->fields = $this->model->getClassFields("mx_report_subscriber");
            $this->view->formHiddenFields = $this->model->getFormHiddenFields();
            $this->render('subscription/subscribers');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

}
