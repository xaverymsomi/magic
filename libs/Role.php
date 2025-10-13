<?php

class Role {

    protected static $permissions;
    private static $db;

    //Initiate an empty array for the permissions
    protected function __construct() {
        $this->permissionList = array();
        $this->setDatabase();
    }

    //Alternatively use your own way of setting your Database connection.
    private function setDatabase() {
        self::$db = new Database();
    }

    //Create populate Role Object
    public static function getRole($role_id) {
        $role = new Role(); //Create new role object
        //Prepate statement and execute it
        $stm = self::$db->prepare("SELECT mx_permission.txt_name FROM mx_group_permission "
                                . "JOIN mx_permission ON mx_permission.id = mx_group_permission.permission_id "
                                . "WHERE mx_group_permission.group_id = :group_id");
        $stm->execute(array(":group_id" => $role_id));

        //Loop through the results
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $role->permissionList[$row["txt_name"]] = true;
        }
        return $role;
    }

    //Check if the specific role has a given permission
    public function verifyPermission($permission) {
        return isset($this->permissionList[$permission]);
    }
    
}
