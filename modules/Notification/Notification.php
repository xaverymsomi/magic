<?php

namespace Modules\Notification;

use Exception;
use Libs\Controller;
use Libs\Log;
use Libs\Perm_Auth;

use Modules\Notification\Actions\CreateNewNotification;
use Modules\Notification\Actions\UpdateNotification;
use Modules\Notification\Notification_Model;

class Notification extends Controller
{

    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Notification_Model();
    }

    public function index()
    {
        $permission = 'view_notifications';
        $data = $this->model->getAllRecords($this->model->getTable(true));
        $title = "All " . $this->model->getTitle(true);
        $this->pageFilter($title, $data, $permission);
    }

    public function profile($id)
    {
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();

        if ($permission->verifyPermission('view_shehias')) {
            $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $record_id);
            if ($returned_id > -1) {

                $profile_data = $this->model->getProfileData($returned_id, $this->model->getTable());

                $this->view->primary_color = filter_input(INPUT_COOKIE, 'primary', FILTER_SANITIZE_SPECIAL_CHARS, ["options" => ["default" => "#000000"]]);
                $this->view->secondary_color = filter_input(INPUT_COOKIE, 'secondary', FILTER_SANITIZE_SPECIAL_CHARS, ["options" => ["default" => "#FF0000"]]);
                $this->view->title = $this->model->getTitle() . ' Profile';
                $this->view->data = array_merge($profile_data);
                $this->view->tabs = $this->model->getTabs();
                $this->view->hidden_columns = $this->model->getProfileHiddenColumns();
                $this->view->account_details = [];
                $this->view->extras = [];
                $this->view->buttons = $this->model->getProfileButtons($returned_id);
                $this->render('profile/profile');
            } else {
                $this->view->subtitle = "Notification not found";
                $this->renderFull('views/templates/not_found');
            }
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function create()
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('add_notifications')) { //checking permission
            $this->view->class = getClassName(get_class($this->model));
            $this->view->title = 'New ' . $this->model->getTitle();
            $this->view->data = ['has_extra' => 0];
            $this->view->dropdowns = $this->model->getFormDropdowns();
            $this->view->disabled = [];
            $this->render('create');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function save()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);

        $this->model->db->beginTransaction();
        try {
            $result = (new CreateNewNotification($posted_data, $this->model))->init();

            if ($result['status']) {
                $this->model->db->commit();
            } else {
                $this->model->db->rollBack();
            }
            response($result);
        } catch (Exception $exception) {
            Log::sysErr($exception->getMessage());
            response(['status' => false, 'code' => 100, 'message' => 'Something went wrong with the operation']);
        }
    }

    public function edit($id)
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('edit_institution')) {
            $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
            if ($returned_id > -1) {
                $data = $this->model->getRecord($returned_id, $this->model->getTable());

                $view_data = [
                    'id' => $returned_id,
                    'opt_mx_notification_type_id' => $data['opt_mx_notification_type_id'],
                    'tar_message' => $data['tar_message'],
                    'dat_from_date' => $data['dat_from_date'],
                    'dat_to_date' => $data['dat_to_date'],
                    'opt_mx_application_id' => $data['opt_mx_application_id']
                ];

                $this->view->title = 'Update ' . $this->model->getTitle();
                $this->view->controller = 'Notification';
                $this->view->action = 'post_edit';
                $this->view->form_title = "Careful edit notification details";
                $this->view->data = $view_data;
                $this->view->dropdowns = $this->model->getFormDropdowns();
                $this->render('edit');
            } else {
                $this->view->subtitle = 'Notification Editing Error';
                $this->renderFull('views/templates/not_found');
            }
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function post_edit()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);

        $this->model->db->beginTransaction();
        try {
            $result = (new UpdateNotification($posted_data, $this->model))->init();

            if ($result['status']) {
                $this->model->db->commit();
            } else {
                $this->model->db->rollBack();
            }
            response($result);
        } catch (Exception $exception) {
            Log::sysErr($exception->getMessage());
            response(['status' => false, 'code' => 100, 'message' => 'Something went wrong with the operation']);
        }
    }

    public function suspend($id)
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();

        if ($permission->verifyPermission('suspend_notification')) {
            $this->view->title = 'Suspend Notification';
            $this->view->subtitle = 'Notification Suspension';
            $this->view->controller = 'Notification';
            $this->view->action = 'process_suspend';
            $this->view->name = '';
            $this->view->data = ['id' => $posted_id];
            $this->renderFull('views/templates/suspend');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function process_suspend()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        try {
            $posted_id = filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS);
            $operator_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
            $this->model->update(['opt_mx_state_id' => filter_var(INACTIVE, FILTER_SANITIZE_NUMBER_INT)], $this->model->getTable(), $operator_id);

            response(['status' => true, 'code' => 200, 'message' => 'Notification suspended successfully']);
        } catch (Exception $ex) {
            response(['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to suspend Notification.']);
            echo $ex->getMessage();
        }
    }

    public function activate($id)
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('activate_notification')) {
            $this->view->title = 'Activate Notification';
            $this->view->subtitle = "Notification Activation";
            $this->view->controller = "Notification";
            $this->view->action = "process_activate";
            $this->view->name = "";
            $this->view->data = ['id' => $posted_id];
            $this->renderFull('views/templates/activate');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function process_activate()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        try {
            $posted_id = filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS);
            $operator_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
            $this->model->update(['opt_mx_state_id' => filter_var(ACTIVE, FILTER_SANITIZE_NUMBER_INT)], $this->model->getTable(), $operator_id);

            response(['status' => true, 'code' => 201, 'message' => 'Notification activated successfully']);
        } catch (Exception $ex) {
            response(['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to activate Notification.']);
            echo $ex->getMessage();
        }
    }


}