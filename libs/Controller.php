<?php

namespace Libs;

use Exception;
use Modules\Error\Error;

class Controller
{
//    public $new_audit_data;
//    public $old_audit_data;
//    public $audit_model;
//    public $audit_id;
//    public $log;
    public $module;
    public $view;
    public $model;

    public function __construct()
    {
        $this->view = new View();
    }

    public function pageFilter($title, $data, $permission, $view = true): void
    {
        $permissions = Perm_Auth::getPermissions();
        if (!$permissions->verifyPermission($permission)) {
            $this->_permissionDenied(debug_backtrace()[1]['class'] . '::' . debug_backtrace()[1]['function']);
        }
        $this->view->title = $title;
        $this->view->buttons = $this->model->getControls();
        $this->view->class = getClassName(get_class($this->model));
        $this->view->table = $this->model->getTable($view);
        $this->view->allRecords = $data[0];
        $this->view->headings = $this->model->getClassFields($this->model->getTable($view))['properties'];
        $this->view->hidden = $this->model->getHiddenFields();
        $this->view->actions = $this->model->getActions();
        $this->view->resultData = $data[1];
        $this->view->postData = $data[2];
        $this->view->labels = $this->model->getTableLabels() ?? [];
        $this->render('index');
    }

    public function _permissionDenied($unauthorized_task = null): void
    {
        if ($unauthorized_task != null && $unauthorized_task != '') {
            $msg = $unauthorized_task;
        } else {
            $msg = debug_backtrace()[1]['class'] . '::' . debug_backtrace()[1]['function'];
        }

        $log = new Log();
        $log->sysLog('No permission to access: ' . $msg);

        $error = new Error("Error 007", "Permission Denied", "You are not authorised to perform this task", "pe-7s-lock");
        $error->index();
        exit;
    }

    public function render($name): void
    {
        $this->view->render(getClassName(get_called_class()), $name);
    }

    public function getProfile($record_id, $permission, $extra_data = []): void
    {
        $permissions = Perm_Auth::getPermissions();
        if ($permissions->verifyPermission($permission)) {
            $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $record_id);

            if ($returned_id > -1) {
                $data = $this->model->getProfileData($returned_id, $this->model->getTable());

                $this->view->primary_color = filter_input(INPUT_COOKIE, 'primary', FILTER_SANITIZE_SPECIAL_CHARS, ["options" => ["default" => "#000000"]]);
                $this->view->secondary_color = filter_input(INPUT_COOKIE, 'secondary', FILTER_SANITIZE_SPECIAL_CHARS, ["options" => ["default" => "#FF0000"]]);
                $this->view->title = $this->model->getTitle() . ' Profile';
                $this->view->data = array_merge($data, $extra_data);
                $this->view->tabs = $this->model->getTabs();
                $this->view->hidden_columns = $this->model->getProfileHiddenColumns();
                $this->view->account_details = [];
                $this->view->extras = [];
                $this->view->buttons = $this->model->getProfileButtons();

                $this->render('profile/profile');
            } else {
                $this->view->subtitle = "Record not found";
                $this->renderFull('views/templates/not_found');
            }
        } else {
            $this->_permissionDenied(debug_backtrace()[1]['class'] . '::' . debug_backtrace()[1]['function']);
        }
    }

    function renderFull($name): void
    {
        $this->view->renderFull($name);
    }

    public function getAssociatedRecords($record_id, $valid_caller, $call_mappers, $permission): void
    {
        $table = 'mx_' . rtrim(strtolower($valid_caller), 's');

        if (array_key_exists($valid_caller, $call_mappers)) {
            $table = $call_mappers[$valid_caller];
        }

        $permissions = Perm_Auth::getPermissions();
        if ($permissions->verifyPermission($permission)) {
            $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $record_id);
            if ($returned_id > -1) {
                $associated_record_details = $this->model->getAssociatedRecordDetails($valid_caller);
                $this->view->hiddens = $associated_record_details['hiddens'] ?? [];
                $this->view->labels = $associated_record_details['labels'] ?? [];
                $this->view->formatters = $associated_record_details['formatters'] ?? [];
                $this->view->data = $this->model->getAssociatedRecords($returned_id, $table, $this->model->getParentKey());
                $this->view->table_headers = $this->model->getTableColumns($table . '_view');
                $this->view->caller = str_replace("_", " ", filter_var($valid_caller, FILTER_SANITIZE_SPECIAL_CHARS));
                $this->view->actions = $this->model->getAssociatedRecordActions($valid_caller);
                $this->view->show_cards = false;
                $this->render('associated_records/main');
            } else {
                $this->view->subtitle = "Record not found";
                $this->renderFull('views/templates/not_found');
            }
        } else {
            $this->_permissionDenied(debug_backtrace()[1]['class'] . '::' . debug_backtrace()[1]['function']);
        }
    }

    public function getGUID(): string
    {
        if (function_exists('com_create_guid') === true) {
            return trim($this->com_create_guid(), '{}');
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    function renderJson($name): void
    {
        $this->view->renderJson(getClassName(get_called_class()), $name);
    }

	public function suspendPage($data, $permission) : void
	{
		$permissions = Perm_Auth::getPermissions();
		if (!$permissions->verifyPermission($permission)) {
			$this->_permissionDenied(__METHOD__);
		}
		$this->view->title = "Suspend " . $this->model->getTitle();
		$this->view->subtitle = $this->model->getTitle() . " Suspension";
		$this->view->controller = $this->model->getController();
		$this->view->action = 'post_suspend';
		$this->view->name = '';
		$this->view->data = ['id' => $data];
		$this->renderFull('views/templates/suspend');
	}

	public function activatePage($data, $permission) : void
	{
		$permissions = Perm_Auth::getPermissions();
		if (!$permissions->verifyPermission($permission)) {
			$this->_permissionDenied(__METHOD__);
		}
		$this->view->title = "Activate " . $this->model->getTitle();
		$this->view->subtitle = $this->model->getTitle() . " Activation";
		$this->view->controller = $this->model->getController();
		$this->view->action = 'post_activate';
		$this->view->name = '';
		$this->view->data = ['id' => $data];
		$this->renderFull('views/templates/activate');
	}

	public function postSuspend($posted_data, ?string $status_column = 'opt_mx_state_id') : void
	{
		try {
			$name = $this->model->getTitle();
			$posted_id = filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS);
			$operator_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
			$this->model->update([$status_column => filter_var(INACTIVE, FILTER_VALIDATE_INT)], $this->model->getTable(), $operator_id);

			response(['status' => false, 'code' => 201, 'message' => $name . ' suspended successfully']);
		} catch (Exception $ex) {
			response(['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to suspend ' . $name]);
			echo $ex->getMessage();
		}
	}

	public function postActivation($posted_data, ?string $status_column = 'opt_mx_state_id') : void
	{
		try {
			$name = $this->model->getTitle();
			$posted_id = filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS);
			$operator_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
			$this->model->update([$status_column => filter_var(ACTIVE, FILTER_VALIDATE_INT)], $this->model->getTable(), $operator_id);

			response(['status' => false, 'code' => 201, 'message' => $name . ' activated successfully']);
		} catch (Exception $ex) {
			response(['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to activate ' . $name]);
			echo $ex->getMessage();
		}
	}

	private function com_create_guid() : string
	{
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

}
