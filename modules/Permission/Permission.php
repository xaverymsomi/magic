<?php

namespace Modules\Permission;

use chillerlan\QRCode\Data\ECI;
use Exception;
use Libs\Controller;
use Libs\Database;
use Libs\Perm_Auth;

/**
 * Description of Employee
 *
 * @author abdirahmanhassan
 */
class Permission extends Controller
{
    public $module = 'Permission';
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Permission_Model();
    }

    public function index()
    {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_permissions')) {
            $this->view->permission_details = $this->model->loadData();
            //            echo '<pre>';
            //            print_r($this->view->permission_details);
            //            exit;
            $this->render('index');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function create()
    {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('add_permission')) { //checking permission 
            $this->view->class = get_class($this->model);
            $this->view->title = 'New ' . $this->model->getTitle();
            $this->view->fields = $this->model->getClassFields($this->model->getTable());
            $this->view->formHiddenFields = $this->model->getFormHiddenFields();
            $this->render('create');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function getUserGroups()
    {
        $post_data = json_decode(file_get_contents("php://input"), true);
        $user_id = $this->model->getRecordIdByRowValue($post_data['domain'], $post_data['id']);
        $domain = $post_data['domain'];
        //        print_r($post_data);
        //        $user = $this->model->getRecordByRowValue("mx_user", $user_id);
        $parent = $this->model->db->select("SELECT id FROM mx_login_credential WHERE txt_domain =:domain AND user_id =:user_id", [':domain' => filter_var($domain, FILTER_SANITIZE_SPECIAL_CHARS), ':user_id' => filter_var($user_id, FILTER_SANITIZE_SPECIAL_CHARS)]);
        if (sizeof($parent)) {
            $council = "";
            //            $user_value = $this->model->getRecordByFieldName('mx_login_credential', 'user_id', $parent[0]['id']);
            $query = "SELECT mx_group.txt_name AS 'group_name' FROM mx_login_credential_group 
                                JOIN mx_group ON mx_group.id = mx_login_credential_group.opt_mx_group_id 
                                WHERE mx_login_credential_group.opt_mx_login_credential_id = '" . $parent[0]['id'] . "' ORDER BY mx_group.id";
            //        echo $query;
            $user_groups = $this->model->db->select($query);
            $all_groups = $this->getGroups();
            $result = $this->processGroups($user_groups, $all_groups);
        } else {
            $all_groups = $this->getGroups();
            $result = $this->processGroups([], $all_groups);
        }
        response($result);
    }

    public function post_UserGroup()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $user = $this->model->getRecordByRowValue('mx_user', filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS));
        if (!$user) {
            response(['status' => 100, 'title' => 'Failed to save user Groups']);
        }

        $user_id = $user['id'];

        $parent = $this->model->getRecordByFieldName('mx_login_credential', 'user_id', $user_id);
        if (!$parent) {
            response(['status' => 100, 'title' => 'Failed to get credentials record']);
        }

        $parent_id = $parent['id'];
        $new_data = $posted_data['new_data'];
        //        print_r($parent_id);
        $delete_query = "DELETE FROM mx_login_credential_group WHERE opt_mx_login_credential_id = '" . $parent_id . "'";

        try {
            $delete_result = $this->executeQuery($delete_query);
            if ($delete_result && sizeof($new_data)) {

                $result = $this->saveCheckedData($new_data, $parent_id, 'mx_login_credential_group', 'opt_mx_login_credential_id, opt_mx_group_id, txt_row_value');
                $result_array = ['status' => $result, 'title' => 'User Groups Saved Successfully'];
                response($result_array);
            }
        } catch (Exception $ex) {
        }
        $result_array = ['status' => 100, 'title' => 'An error occurred while saving groups'];
        response($result_array);
    }

    function getGroupPermissions()
    {
        $group_id = json_decode(file_get_contents("php://input"), true);
        //        print_r($group_id);
        if (in_array($_SESSION['role'], ['3'])) {
            $query = "SELECT mx_permission.txt_name AS 'per_name', mx_section.txt_name AS 'section_name' FROM mx_group_permission 
                                JOIN mx_permission ON mx_permission.id = mx_group_permission.opt_mx_permission_id 
                                JOIN mx_section ON mx_section.id = mx_permission.opt_mx_section_id 
                                WHERE mx_group_permission.opt_mx_group_id = '$group_id' 
                                ORDER BY mx_permission.opt_mx_section_id";
        } else {
            $query = "SELECT mx_permission.txt_name AS 'per_name',mx_section.txt_name AS 'section_name' from mx_permission 
                        JOIN mx_section ON mx_section.id = mx_permission.opt_mx_section_id 
                        WHERE mx_permission.id IN(select opt_mx_permission_id 
                        FROM mx_group_permission
                        WHERE opt_mx_group_id IN(select opt_mx_group_id FROM mx_login_credential_group WHERE opt_mx_group_id=" . $group_id . "))";
        }
        //    $result = $db->select($query);
        //        echo $query;
        $given_permissions = $this->model->db->select($query);
        //        print_r($given_permissions);
        $all_permissions = $this->getPermissions();
        $result = $this->processPermissions($given_permissions, $all_permissions);

        response($result);
    }

    public function post_GroupPermission()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $parent_id = $posted_data['id'];
        $new_data = $posted_data['new_data'];
        $delete_query = "DELETE FROM mx_group_permission WHERE opt_mx_group_id = '$parent_id'";
        $delete_result = $this->executeQuery($delete_query);
        if ($delete_query) {
            $result = $this->saveCheckedData($new_data, $parent_id, 'mx_group_permission', 'opt_mx_group_id, opt_mx_permission_id, txt_row_value');
        }
        $result_array = ['status' => $result, 'title' => 'Group Permissions'];
        response($result_array);
    }

    function getUserPermissions()
    {
        $post_data = json_decode(file_get_contents("php://input"), true);
        $user_id = $this->model->getRecordIdByRowValue($post_data['domain'], $post_data['id']);
        $domain = $post_data['domain'];
        $query = "SELECT mx_permission.txt_name AS 'per_name', mx_section.txt_name AS 'section_name' FROM mx_login_credential_permission 
                                JOIN mx_permission ON mx_permission.id = mx_login_credential_permission.opt_mx_permission_id 
                                JOIN mx_section ON mx_section.id = mx_permission.opt_mx_section_id 
                                WHERE mx_login_credential_permission.opt_mx_login_credential_id IN(SELECT id FROM mx_login_credential WHERE user_id= '$user_id' AND txt_domain='$domain') 
                                ORDER BY mx_permission.opt_mx_section_id";
        //        echo $query;
        $given_permissions = $this->model->db->select($query);
        $all_permissions = $this->getPermissions();
        $result = $this->processPermissions($given_permissions, $all_permissions);

        response($result);
    }

    public function post_UserPermission()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $parent_id = $this->model->getRecordIdByRowValue($posted_data['domain'], $posted_data['id']);
        $domain = $posted_data['domain'];
        $parent = $this->model->db->select("SELECT id FROM mx_login_credential WHERE txt_domain =:domain AND user_id =:user_id", [':domain' => filter_var($domain, FILTER_SANITIZE_SPECIAL_CHARS), ':user_id' => filter_var($parent_id, FILTER_SANITIZE_SPECIAL_CHARS)]);
        if (sizeof($parent)) {
            $new_data = $posted_data['new_data'];
            $delete_query = "DELETE FROM mx_login_credential_permission WHERE opt_mx_login_credential_id = '" . $parent[0]['id'] . "'";
            $delete_result = $this->executeQuery($delete_query);
            if ($delete_query) {
                $result = $this->saveCheckedData($new_data, $parent[0]['id'], 'mx_login_credential_permission', 'opt_mx_login_credential_id, opt_mx_permission_id, txt_row_value');
            }
            $result_array = ['status' => 200, 'title' => 'User Permissions updated successfully'];
        } else {
            $result_array = ['status' => 100, 'title' => 'Failed to Update User Permissions'];
        }
        response($result_array);
    }

    public function post_saveGroup()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $name = $posted_data['name'];
        $save_query = "INSERT INTO mx_group (txt_name,int_added_by, txt_row_value) VALUES('$name','" . $_SESSION['user_id'] . "'";
        if ($_ENV['DB_TYPE'] == 'sqlsrv' || $_ENV['DB_TYPE'] == 'odbc') {
            $save_query .= ", NEWID())";
        } else {
            $save_query .= ", UUID())";
        }
        $result = $this->executeQuery($save_query);
        if ($result) {
            $save_result = 200;
        } else {
            $save_result = 100;
        }
        $result_array = ['status' => $save_result, 'title' => 'Group Data'];
        response($result_array);
    }

    public function post_savePermission()
    {
        $new_data = json_decode(file_get_contents("php://input"), true);
        $save_query = "INSERT INTO mx_permission (txt_display_name, txt_name, opt_mx_section_id, txt_row_value) VALUES('" . $new_data['display_name'] . "','" . $new_data['name'] . "','" . $new_data['section_id'] . "'";
        if ($_ENV['DB_TYPE'] == 'sqlsrv') {
            $save_query .= ", NEWID())";
        } else {
            $save_query .= ", UUID())";
        }
        $result = $this->executeQuery($save_query);
        if ($result) {
            $result_array = ['status' => 200, 'title' => 'Permission Saved Successfully'];
        } else {
            $result_array = ['status' => 100, 'title' => 'Failed to save Permission'];
        }

        response($result_array);
    }

    public function post_saveSection()
    {
        $new_data = json_decode(file_get_contents("php://input"), true);
        $this->model->db->beginTransaction();
        $data = [
            'txt_name' => filter_var(trim($new_data['txt_name']), FILTER_SANITIZE_SPECIAL_CHARS),
            'txt_row_value' => $this->model->getGUID('mx_section')
        ];
        try {
            $result = $this->model->create($data, 'mx_section');
            if ($result) {
                $result_array = ['status' => 200, 'title' => 'Section Saved Successfully'];
            } else {
                $result_array = ['status' => 100, 'title' => 'Failed to Save Section'];
            }
            $this->model->db->commit();
        } catch (Exception $exc) {
            $this->model->db->rollBack();
            $result_array = ['status' => 100, 'title' => 'Fatal Error: An error occurred while saving Section'];
        }
        response($result_array);
    }

    private function saveCheckedData($array, $pk_id, $table_name, $fields)
    {
        $save_result = 220;
        $result = false;
        foreach ($array as $arr) {
            $isAllowed = $arr[0];
            $fk_id = $arr[1];
            if ($isAllowed == 1) {
                $save_query = "INSERT INTO $table_name ($fields) VALUES('" . $pk_id . "', '" . $fk_id . "'";
                if ($_ENV['DB_TYPE'] == 'sqlsrv') {
                    $save_query .= ", NEWID())";
                } else {
                    $save_query .= ", UUID())";
                }
                $result = $this->executeQuery($save_query);
                if ($result) {
                    $save_result = 200;
                } else {
                    $save_result = 100;
                }
            }
        }
        
        return $save_result;
    }

    private function getGroups()
    {
        $db = new Database();
        $council = '';
        //    if ($_SESSION['role'] == 3) {
        //        $query = "SELECT mx_group.id AS 'group_id', mx_group.txt_name AS 'group_name',
        //                FROM mx_group JOIN mx_user ON (mx_group.txt_added_by = mx_user.id) ORDER BY mx_group.id";
        //    } else {
        $query = "SELECT mx_group.id AS 'group_id', mx_group.txt_name AS 'group_name' FROM mx_group  ORDER BY mx_group.id";
        //    }
        $result = $db->select($query);
        return $result;
    }

    private function executeQuery($query)
    {
        $db = new Database();
        $count = $db->prepare($query);
        return $count->execute();
    }

    private function processPermissions($given_permissions, $all_permissions)
    {
        $all_given_permissions = [];
        foreach ($all_permissions as $all_per) {
            $flag = 0;
            foreach ($given_permissions as $given_per) {
                if ($all_per['permission_name'] == $given_per['per_name']) {
                    $flag = 1;
                }
            }
            if ($flag == 1) {
                $temp = array_merge($all_per, ['check' => true]);
            } else {
                $temp = array_merge($all_per, ['check' => false]);
            }
            $all_given_permissions[] = $temp;
        }
        return $all_given_permissions;
    }

    function processGroups($user_groups, $all_groups)
    {
        $all_user_groups = [];
        foreach ($all_groups as $group) {
            $flag = 0;
            //            if ($_SESSION['council']) {
            foreach ($user_groups as $user_group) {
                if ($group['group_name'] == $user_group['group_name']) {
                    $flag = 1;
                }
            }
            //            } else {
            //                foreach ($user_groups as $user_group) {
            //                    if ($group['group_name'] == $user_group['group_name'] && $group['opt_mx_council_id'] == $user_group['council']) {
            //                        $flag = 1;
            //                    }
            //                }
            //            }

            if ($flag == 1) {
                $temp = array_merge($group, ['check' => true]);
            } else {
                $temp = array_merge($group, ['check' => false]);
            }
            $all_user_groups[] = $temp;
        }
        return $all_user_groups;
    }

    private function getPermissions()
    {
        if (in_array($_SESSION['role'], ['3'])) {

            $query = "SELECT mx_section.id AS 'section_id', mx_section.txt_name AS 'section_name', mx_permission.id AS 'permission_id', 
                mx_permission.txt_name AS 'permission_name',
                (replace(mx_permission.txt_name, '_', ' ')) AS 'permission_display_name'
                FROM mx_section 
                JOIN mx_permission ON mx_permission.opt_mx_section_id = mx_section.id 
        ORDER BY section_id";
        } else {
            $query = "SELECT mx_section.id AS 'section_id',mx_section.txt_name AS 'section_name',mx_permission.id AS 'permission_id',mx_permission.txt_name AS 'permission_name',
                    (REPLACE(mx_permission.txt_name,'_',' ')) AS 'permission_display_name'FROM mx_permission JOIN mx_section ON mx_permission.opt_mx_section_id = mx_section.id
                    WHERE mx_permission.id IN (SELECT opt_mx_permission_id FROM mx_group_permission WHERE opt_mx_group_id IN (SELECT opt_mx_group_id FROM mx_login_credential_group  WHERE opt_mx_login_credential_id = '" . $_SESSION['id'] . "'))ORDER BY section_id";
        }


        $result = $this->model->db->select($query);
        $data = [];
        if ($result) {
            foreach ($result as $row) {
                $row['permission_display_name'] = ucwords($row['permission_display_name']);
                $data[] = $row;
            }
        }
        return $data;
    }

    public function loadData()
    {
        $groups = [];
        $permissions = [];
        $users = [];
        $allPermissions = [];
        $sections = [];
        //        echo '<pre>';
        //        echo 'Nimeitwa...';
        //        exit;
        //        if ($_SESSION['council'] === '0') {
        if ($_SESSION['role'] === '3') {
            //            echo 'Mimi ni Developer' . '<br>';
            $result1 = $this->model->db->select("SELECT mx_group.* FROM mx_group ORDER BY mx_group.id ASC");
        } else {
            //            echo 'Mimi siyo Developer' . '<br>';
            // $group_id;
            $_SESSION['role'] == 3 ? $group_id = 3 : $group_id = 3 . ',1';
            $result1 = $this->model->db->select("SELECT mx_group.* FROM mx_group WHERE mx_group.id NOT IN(" . $group_id . ") ORDER BY mx_group.id ASC");
        }
        //        exit;
        //        } else {
        //            $result1 = $this->model->db->select("SELECT mx_group.*,mx_council.txt_name AS [council] FROM mx_group INNER JOIN mx_council ON mx_council.id=mx_group.opt_mx_council_id WHERE opt_mx_council_id = " . $_SESSION['council'] . " ORDER BY mx_group.id ASC");
        //        }
        if ($result1) {
            foreach ($result1 as $value) {
                $groups[] = ['id' => $value['id'], 'name' => $value['txt_name']];
            }
        }

        $result2 = $this->model->db->select("SELECT * FROM mx_permission ORDER BY txt_name ASC");
        if ($result2) {
            foreach ($result2 as $value) {
                $permissions[] = ['id' => $value['id'], 'name' => $value['txt_name']];
            }
        }
        $user_table = $this->model->db->select("SELECT DISTINCT txt_domain FROM mx_login_credential");
//		echo "<pre>"; print_r($_SESSION); exit;
        if (sizeof($user_table)) {
            foreach ($user_table as $key => $value_data) {
	            $isSpecialRole = in_array($_SESSION['role'], [1, 2, 3]) === false;
                if ($_SESSION['domain'] == $value_data['txt_domain'] && $isSpecialRole) {
                    $group_id;
                    $hasTxtName = $this->model->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $value_data['txt_domain'] . "' AND COLUMN_NAME = 'txt_name'");
                    $_SESSION['role'] == 3 ? $group_id = 3 : $group_id = 3 . ',1';
                    if (count($hasTxtName) > 0) {
                        $result3 = $this->model->db->select("SELECT txt_row_value,txt_name FROM " . $value_data['txt_domain'] . " WHERE id NOT IN('" . $_SESSION['user_id']
                            . "') AND id IN(SELECT user_id FROM mx_login_credential 
                                WHERE id IN(SELECT opt_mx_login_credential_id 
                                FROM mx_login_credential_center WHERE opt_mx_center_id=" . $_SESSION['center']
                            . ")AND mx_login_credential.id IN(SELECT id FROM mx_login_credential_group WHERE opt_mx_group_id NOT IN(" . $group_id . ")) ) ORDER BY txt_name ASC");
                    } else {
                        $result3 = $this->model->db->select("SELECT txt_row_value,CONCAT_WS(' ', txt_first_name, txt_middle_name, txt_last_name) AS txt_name FROM " . $value_data['txt_domain'] . " WHERE id NOT IN('" . $_SESSION['user_id']
                            . "') AND id IN(SELECT user_id FROM mx_login_credential 
                                WHERE id IN(SELECT opt_mx_login_credential_id 
                                FROM mx_login_credential_center WHERE opt_mx_center_id=" . $_SESSION['center']
                            . ")AND mx_login_credential.id IN(SELECT id FROM mx_login_credential_group WHERE opt_mx_group_id NOT IN(" . $group_id . ")) ) ORDER BY txt_name ASC");
                    }                    
                } else {
                    $hasTxtName = $this->model->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $value_data['txt_domain'] . "' AND COLUMN_NAME = 'txt_name'");
                    if (count($hasTxtName) > 0) {
                        $result3 = $this->model->db->select("SELECT txt_row_value,txt_name FROM " . $value_data['txt_domain'] . " ORDER BY txt_name ASC");
                    } else {
                        $result3 = $this->model->db->select("SELECT txt_row_value, CONCAT_WS(' ', txt_first_name, txt_middle_name, txt_last_name) AS txt_name FROM " . $value_data['txt_domain'] . " ORDER BY txt_name ASC");
                    }                    
                }
                if ($result3) {
                    foreach ($result3 as $value) {
                        $users[] = ['id' => $value['txt_row_value'], 'name' => $value['txt_name'], 'domain' => $value_data['txt_domain']];
                    }
                }
            }
        }
        if (in_array($_SESSION['role'], ['1', '3'])) {
            $query = "SELECT mx_section.id AS 'section_id', mx_section.txt_name AS 'section_name', mx_permission.id AS 'permission_id', 
                mx_permission.txt_name AS 'permission_name', (replace(mx_permission.txt_name, '_', ' ')) AS 'permission_display_name'
                FROM mx_section 
                JOIN mx_permission ON mx_permission.opt_mx_section_id = mx_section.id 
                 ORDER BY section_id";
        } else {
            $query = "SELECT mx_section.id AS 'section_id', mx_section.txt_name AS 'section_name', mx_permission.id AS 'permission_id', 
                        mx_permission.txt_name AS 'permission_name', (replace(mx_permission.txt_name, '_', ' ')) AS 'permission_display_name'
                        FROM mx_section 
                        JOIN mx_permission ON mx_permission.opt_mx_section_id = mx_section.id 
                        WHERE mx_permission.id IN(SELECT opt_mx_permission_id FROM mx_group_permission 
                        WHERE opt_mx_group_id IN(SELECT opt_mx_group_id FROM mx_login_credential_group
                        WHERE opt_mx_login_credential_id='" . $_SESSION['id'] . "'))
                        ORDER BY section_id";
        }
        $result4 = $this->model->db->select($query);
        if ($result4) {
            foreach ($result4 as $row) {
                $row['permission_display_name'] = ucwords($row['permission_display_name']);
                $allPermissions[] = $row;
            }
        }

        $result5 = $this->model->db->select("SELECT * FROM mx_section");
        foreach ($result5 as $value) {
            $sections[] = ['id' => $value['id'], 'name' => $value['txt_name']];
        }

        $data = [
            'groups' => $groups,
            'permissions' => $permissions,
            'users' => $users,
            'allPermissions' => $allPermissions,
            'sections' => $sections
        ];
        response($data);
    }
}
