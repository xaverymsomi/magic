<?php

namespace Modules\User;

use Exception;
use Libs\Controller;
use Libs\Hash;
use Libs\iMartSMS;
use Libs\Log;
use Libs\MXMailGun;
use Libs\MXSms;
use Libs\SmsSender;
use Libs\Perm_Auth;
use Libs\Session;

/**
 * Description of User
 * MX file for system user
 *
 * @author abdirahmanhassan
 */
class User extends Controller
{

    /**
     * @var User_Model
     */
    public $model;

    public function __construct()
    {
        $this->model = new User_Model();
        parent::__construct();
    }

    public function index() : void
    {
        $permission = 'view_users';
        $data = $this->model->getAllRecords($this->model->getTable(true));
        $title = "All " . $this->model->getTitle();
        $this->pageFilter($title, $data, $permission);
    }

    public function active() : void
    {
        $permission = 'view_users';
        $data = $this->model->getFilteredRecords($this->model->getTable(true), ['opt_mx_status_id'], [filter_var(ACTIVE, FILTER_SANITIZE_NUMBER_INT)]);
        $title = "Active " . $this->model->getTitle();
        $this->pageFilter($title, $data, $permission);
    }

    public function inactive() : void
    {
        $permission = 'view_users';
        $data = $this->model->getFilteredRecords($this->model->getTable(true), ['opt_mx_status_id'], [filter_var(INACTIVE, FILTER_SANITIZE_NUMBER_INT)]);
        $title = "Inactive " . $this->model->getTitle();
        $this->pageFilter($title, $data, $permission);
    }

    public function profile($id) : void
    {
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = 'view_users';
        $extra_data = [];
        parent::getProfile($record_id, $permission, $extra_data);
    }

    public function password() : void
    {
        $this->view->title = "All " . $this->model->getTitle();
        $this->view->buttons = $this->model->getControls();
        $this->view->class = getClassName(get_class($this->model));
        $this->view->allRecords = $this->model->getFilteredRecords($this->model->getTable(), ['opt_mx_status_id'], [ACTIVE])[0];
        $this->view->headings = $this->model->getClassFields($this->model->getTable())['properties'];
        $this->view->hidden = $this->model->getHiddenFields();
        $this->view->actions = $this->model->getActions();
        $this->view->table = $this->model->getTable();
        $this->render('change_password');
    }

    public function associated_records($id, $caller) : void
    {
        $call_mappers = [];

        $permission = 'view_users';
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $valid_caller = filter_var($caller, FILTER_SANITIZE_SPECIAL_CHARS);
        parent::getAssociatedRecords($record_id, $valid_caller, $call_mappers, $permission);
    }

    public function save() : void
    {
        $filters = $this->model->getInputFilters();
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $validated_data = filter_var_array($posted_data, $filters);

        $this->model->db->beginTransaction();

        $password = $this->model->generateRandomString(8);
        $email = filter_var($posted_data['email'], FILTER_SANITIZE_EMAIL);
        $email_exist = $this->checkEmailIfExists($email);

        $mobile = '255' . substr($posted_data['txt_mobile'], 1);
        $name = $posted_data['txt_name'];
        $user_no = '2' . $this->model->generateRandomNo(5);
        $pin = $this->model->generateRandomNo(4);
        if (!$email_exist) {
            try {
                if (!isset($posted_data['id'])) {

                    $posted_data['id'] = 'US' . $this->model->generateRandomString(6);
                }
                $id = 'US' . $this->model->generateRandomString(6);
                $user_id = filter_var($_SESSION['user_id'], FILTER_SANITIZE_SPECIAL_CHARS);
                $user_login_id = filter_var($_SESSION['id'], FILTER_SANITIZE_NUMBER_INT);
                // Get institution data

                if (isset($posted_data['added_by'])) {
                    $data = [
                        'id' => $posted_data['id'],
                        'txt_name' => $posted_data['txt_name'],
                        'txt_added_by' => $user_login_id,
                        'dat_added_date' => date('Y-m-d', strtotime($posted_data['date'])),
                        'txt_attended_by' => $user_id,
                        'dat_attended_date' => date('Y-m-d H:i:s'),
                        'opt_mx_status_id' => ACTIVE,
                        'int_token' => time(),
                        'txt_pin' => Hash::create(HASH_ALGO, $pin, PASS_SALT),
                        'txt_mobile' => $mobile,
                        'email' => $email,
                    ];
                } else {
                    $data = [
                        'id' => $id,
                        'txt_name' => $posted_data['txt_name'],
                        'txt_added_by' => $user_login_id,
                        'dat_added_date' => date('Y-m-d'),
                        'opt_mx_status_id' => ACTIVE,
                        'int_token' => time(),
                        'txt_mobile' => $mobile,
                        'email' => $email,
                        'txt_pin' => Hash::create(HASH_ALGO, $pin, PASS_SALT)
                    ];
                }


                $result = $this->model->create($data, $this->model->getTable());

                if ($result) {
                    // Login Data
                    $login_credential = [
                        'user_id' => $id,
                        'txt_username' => $email,
                        'password' => Hash::create(HASH_ALGO, $password, PASS_SALT),
                        'opt_mx_status_id' => ACTIVE,
                        'txt_domain' => $this->model->getTable()
                    ];

                    $login_details = $this->model->create($login_credential, 'mx_login_credential');

                    $login_id = $this->model->db->lastInsertId();

                    $group_data = [
                        'opt_mx_login_credential_id' => $login_id,
                        'opt_mx_group_id' => $posted_data['group_id']
                    ];
                    $this->model->create($group_data, 'mx_login_credential_group');

                    if ($group_data['opt_mx_group_id'] == 4) {
                        $center_data = [
                            'opt_mx_login_credential_id' => $login_id,
                            'opt_mx_test_center_id' => $posted_data['opt_mx_test_center_id'] ?? $_SESSION['center'],
                            'opt_mx_status_id' => ACTIVE
                        ];
                        $this->model->create($center_data, 'mx_login_credential_center');
                    }

                }


                $this->model->db->commit();

                $sms = new MXSms();
                $sms->sendTemplateSMS(MX_USER_CREATED_REASON, $mobile, $id, null, null, ['_name', '_email', '_password', '_link'], [$name, $email, $password, URL]);
                response(['code' => 201, 'status' => true, 'message' => 'User created successfully']);
            } catch (Exception $ex) {
                $this->model->db->rollBack();
                $error = ['message' => $ex->getMessage(), 'trace' => $ex->getTrace()];
                Log::sysErr(json_encode($error));
                response(['code' => 100, 'status' => false, 'message' => 'An Error Occurred. Failed to create user.']);
            }
        } else {
            response(['code' => 100, 'status' => false, 'message' => 'User Email Already Exists']);
        }
    }
    private function checkEmailIfExists($email, $user_id = '') : bool
    {
        if ($user_id) {
            $result = $this->model->db->select("SELECT email FROM mx_user WHERE email = :email AND id NOT IN(:user)", [':email' => $email, ':user' => $user_id]);
        } else {
            $result = $this->model->db->select("SELECT email FROM mx_user WHERE email = :email", [':email' => $email]);
        }
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function create() : void
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('add_user')) { //checking permission
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

    public function edit($id) : void
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('edit_user')) { //checking permission
            $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
            if ($returned_id > -1) {

                $data = $this->model->getRecord($returned_id, $this->model->getTable());

                $user_id = $this->model->getRecordByFieldName('mx_login_credential', 'user_id', filter_var($returned_id, FILTER_SANITIZE_SPECIAL_CHARS));
                $group_data = $this->model->db->select('select * from mx_login_credential_group where opt_mx_login_credential_id = :user_id', [':user_id' => $user_id['id']]);
                $group_id = $group_data ? (sizeof($group_data) > 0 ? $group_data[0]['opt_mx_group_id'] : null) : null;
                $view_data = [
                    'id' => $posted_id,
                    'txt_name' => $data['txt_name'],
                    'email' => $data['email'],
                    'txt_mobile' => $data['txt_mobile'],
                    'opt_mx_group_id' => $group_id,
                    'has_extra' => 1
                ];

                $this->view->title = 'Update ' . $this->model->getTitle();
                $this->view->data = $view_data;
                $this->view->dropdowns = $this->model->getFormDropdowns();

                $this->render('edit');
            } else {
                $this->view->subtitle = "User Editing Error";
                $this->renderFull('views/templates/not_found');
            }
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function post_edit() : void
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $filters = $this->model->getInputFilters();

        try {
            $row_id = filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS);
            $id = $this->model->getRecordIdByRowValue($this->model->getTable(), $row_id);
            if ($id > -1) {
                $this->model->db->beginTransaction();

                $email_exist = $this->checkEmailIfExists($posted_data['email'], $id);
                if (!$email_exist) {
                    $data = [
                        'txt_name' => $posted_data['txt_name'],
                        'txt_mobile' => $posted_data['txt_mobile'],
                        'email' => $posted_data['email'],
                    ];

                    //Fetch user
                    $user = $this->model->db->select("SELECT * FROM mx_user WHERE id=:user_id", [':user_id' => $id]);
                    $this->model->db->update($this->model->getTable(), $data, $id);

                    //Update the login credential table
                    $login_credential_data = $this->model->db->select("SELECT * 
                             FROM mx_login_credential WHERE user_id=:user_id 
                            AND txt_domain=:domain", [':user_id' => $user[0]['id'], ':domain' => 'mx_user']);

                    $data = [
                        'txt_username' => $posted_data['email'],
                    ];

                    $result = $this->model->db->update('mx_login_credential', $data, $login_credential_data[0]['id']);

                    if ($result) {
                        $this->model->db->commit();
                        response(['code' => 201, 'status' => false, 'message' => 'User updated successfully']);
                    } else {
                        $this->model->db->rollback();
                        response(['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to update user.']);
                    }
                } else {
                    echo json_encode(900);
                }
            } else {
                $this->view->subtitle = "User Editing Error " . $row_id;
                $this->renderFull('views/templates/not_found');
            }
        } catch (Exception $ex) {
            echo json_encode(100);
            echo $ex->getMessage();
        }
    }

    public function update() : void
    {
        try {
            $filters = $this->model->getInputFilters();
            $posted_data = json_decode(file_get_contents("php://input"), true);
            $validated_data = filter_var_array($posted_data, $filters);
            $id = $validated_data['id'];
            unset($validated_data['id']);
            $result = $this->model->update($validated_data, $this->model->getTable(), $id);

            echo json_encode($result);
        } catch (Exception $ex) {
            echo json_encode(500);
        }
    }

	public function suspend($id) : void
	{
		$permission = "suspend_user";
		$data = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
		$this->suspendPage($data, $permission);
	}
	public function post_suspend() : void
	{
		$posted_data = json_decode(file_get_contents("php://input"), true);
		$this->postSuspend($posted_data);
	}

	public function activate($id) : void
	{
		$permission = "suspend_user";
		$data = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
		$this->activatePage($data, $permission);
	}
	public function post_activate() : void
	{
		$posted_data = json_decode(file_get_contents("php://input"), true);
		$this->postActivation($posted_data);
	}
    public function reset_password($id) : void
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('reset_user_password')) {
            $this->view->title = 'Reset User Password';
            $this->view->subtitle = "User Password Reset";
            $this->view->controller = "User";
            $this->view->action = "post_reset_password";
            $this->view->name = "";
            $this->view->data = ['id' => $posted_id];
            $this->render('reset_password');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }
    public function post_reset_password() : void
    {
        $posted_data = json_decode(file_get_contents("php://input"), true); //convert json object
        $data = $this->model->getRecord(filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS), 'mx_user');

        $otp = $this->model->generateRandomString(8);
        $hashed_password = Hash::create(HASH_ALGO, $otp, PASS_SALT);
        $user = $this->model->getRecord($posted_data['id'], $this->model->getTable());

        $sql = "UPDATE mx_login_credential SET password=:pwd WHERE user_id=:user";

        $stmt = $this->model->db->prepare($sql);
        $obj_data = [':pwd' => $hashed_password, ':user' => filter_var($user['id'], FILTER_SANITIZE_SPECIAL_CHARS)];
        $result = $stmt->execute($obj_data);

        $sms = new SmsSender();
        $sms->sendTemplateSMS(
            RESET_USER_PASSWORD,
            $user['txt_mobile'],
            $_SESSION['user_id'],
            null,
            null,
            ['_name', '_password', '_link'],
            [$user['txt_name'], $otp, URL]
        );

        if ($result) {
            response(['code' => 201, 'status' => true, 'message' => 'Password updated successfully']);
        } else {
            response(['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to reset user password.']);
        }
    }
    public function changePassword() : void
    {
        $user_id = $_SESSION['id'];
        $posted_data = json_decode(file_get_contents("php://input"), true); //convert json object
        $_old_password = Hash::create(HASH_ALGO, $posted_data['old_password'], PASS_SALT);
        $_new_password = $posted_data['new_password'];
        $_confirm_password = $posted_data['confirm_password'];
        $user_data = $this->model->getRecord($user_id, ($_SESSION['login_type'] == "stakeholder") ? "mx_stakeholder" : 'mx_login_credential');
        $password = $user_data['password'];

        $last_reset = date('Y-m-d H:i:s');

        if ($password == $_old_password) {
            if ($_new_password == $_confirm_password) {
                unset($user_data['id']);
                $user_data['password'] = Hash::create(HASH_ALGO, $_new_password, PASS_SALT);
                $user_data['dat_date_last_reset'] = $last_reset;
                // $db = new Database();
                // $result = $this->model->db->update($this->model->getTable(), $user_data, $user_id);

                $result = $this->model->db->update('mx_login_credential', $user_data, $user_id);
                if ($result) {
                    Session::set('returned', 201);
                    echo json_encode(201); //['Status' => 201, 'Message' => '&ltp class="text-success"&gtPassword changed successfully. Please login with your new password.&ltp/p&gt']);
                } else {
                    Session::set('returned', 101);
                    echo json_encode(101); //['Status' => 101, 'Message' => '&ltp class="text-danger"&gtPassword could not be changed.&ltp/p&gt']);
                }
            } else {
                Session::set('returned', 2000);
                echo json_encode(2000); //['Status' => 2000, 'Message' => '&ltp class="text-danger"&gtNew Password and Confirm New Password do not match.&ltp/p&gt']);
            }
        } else {
            Session::set('returned', 1000);
            echo json_encode(1000); //['Status' => 1000, 'Message' => '&ltp class="text-danger"&gtOld Password is incorrect&lt/p&gt']);
        }
    }
	public function change_user_group($id) : void
	{
		$posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
		$permission = Perm_Auth::getPermissions();
		if ($permission->verifyPermission('change_user_group')) {
			$data = $this->model->getRecord($posted_id, $this->model->getTable());

			$user_id = $this->model->getRecordByFieldName('mx_login_credential', 'user_id', filter_var($posted_id, FILTER_SANITIZE_SPECIAL_CHARS));
			$group_data = $this->model->db->select('select * from mx_login_credential_group where opt_mx_login_credential_id = :user_id', [':user_id' => $user_id['id']]);
			$group_id = $group_data ? (sizeof($group_data) > 0 ? $group_data[0]['opt_mx_group_id'] : null) : null;
			$view_data = [
				'id' => $posted_id,
				'txt_name' => $data['txt_name'],
				'email' => $data['email'],
				'txt_mobile' => $data['txt_mobile'],
				'group_id' => $group_id,
				'has_extra' => 1
			];
				$this->view->title = 'Changing Group';
				$this->view->subtitle = "User Change Group";
				$this->view->controller = "User";
				$this->view->action = "post_change_user_group";
				$this->view->name = "";
				$this->view->data = $view_data;
				$this->view->dropdowns = $this->model->getFormDropdowns();
				$this->render('change_user_group');

		} else {
			$this->_permissionDenied(__METHOD__);
		}
	}
	public function post_change_user_group() : void
	{
		$posted_data = json_decode(file_get_contents("php://input"), true);
		$this->model->db->beginTransaction();
		try {
			$posted_id = filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS);
			$user_id = $this->model->getRecordIdByValue('mx_login_credential','user_id', $posted_id);
			$this->model->db->update('mx_login_credential_group', ['opt_mx_group_id' => filter_var($posted_data['group_id'], FILTER_SANITIZE_NUMBER_INT)], $user_id, 'opt_mx_login_credential_id');
			$this->model->db->commit();
			response(['code' => 201, 'status' => false, 'message' => 'User Group changed successfully']);
		} catch (Exception $ex) {
			$this->model->db->rollBack();
			response(['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to change user group.']);
			echo $ex->getMessage();
		}
	}
}
