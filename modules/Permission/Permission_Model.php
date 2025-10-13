<?php
namespace Modules\Permission;

use Libs\Model;

/**
 * Description of Employee
 *
 * @author abdirahmanhassan
 */
class Permission_Model extends Model {

    public $table = "mx_permission";
    private $view_dir = "permission/";
    private $title = "Permission";

    public function getHiddenFields() {
        return ['id'];
    }

    public function getFormHiddenFields() {
        return array('id');
    }

    function getControls() {
        return [];
    }

    function getActions() {
        return [];
    }

    public function getTable($view_table = false): string
    {
        if ($view_table) {
            return $this->view_table;
        }
        return $this->table;
    }

    function getTitle() {
        return $this->title;
    }

    function getViewDir() {
        return $this->view_dir;
    }

    public function loadData(){
        $groups = [];
        $permissions = [];
        $users = [];
        
        $result1 = $this->db->select("SELECT * FROM mx_group ORDER BY id ASC");
        if ($result1) {
            foreach ($result1 as $value) {
                $groups[] = ['id' => $value['id'], 'name' => $value['txt_name']];
            }
        }
        
        $result2 = $this->db->select("SELECT * FROM mx_permission ORDER BY txt_name ASC");
        if ($result2) {
            foreach ($result2 as $value) {
                $permissions[] = ['id' => $value['id'], 'name' => $value['txt_name']];
            }
        }
        
        $result3 = $this->db->select("SELECT * FROM mx_user ORDER BY txt_name ASC");
        if ($result3) {
            foreach ($result3 as $value) {
                $users[] = ['id' => $value['txt_row_value'], 'name' => $value['txt_name']];
            }
        }
        return [
            'groups' => $groups,
            'permissions' => $permissions,
            'users' => $users,
        ];
    }
}
