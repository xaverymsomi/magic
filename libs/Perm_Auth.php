<?php
namespace Libs;

use PDO;

/**
 * @permission authentication
 * @author Fatma Sharif
 */
class Perm_Auth {

    protected static $permissions;
    private static $db;

    //Initiate an empty array for the permissions
    private array $permissionList;

    public function __construct() {
        $this->permissionList = array();
        $this->setDatabase();
    }

    //Alternatively use your own way of setting your Database connection.
    private function setDatabase() {
        self::$db = new Database();
    }

    //Create populate Role Object
    public static function getPermissions($id = null)
    {
        if (empty($id)) {
            if (!array_key_exists('id', $_SESSION)) {
                Log::sysLog('No session ID found. Killing all sessions and cookies.');
                kill();
                exit;
            } else {
                $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
        } else {
            $user_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        $sections = []; //Create new role object
        $perm = new Perm_Auth(); //Create new role object
        //Prepate statement and execute it
        $group_string = $perm->getUserGroups($user_id);

        $stm = self::$db->prepare("SELECT DISTINCT mx_permission.* FROM mx_permission 
                                    JOIN mx_group_permission ON mx_group_permission.opt_mx_permission_id = mx_permission.id 
                                    WHERE mx_group_permission.opt_mx_group_id IN ($group_string)
                                UNION 
                                    SELECT DISTINCT mx_permission.* FROM mx_permission 
                                    JOIN mx_login_credential_permission ON mx_login_credential_permission.opt_mx_permission_id = mx_permission.id 
                                    WHERE mx_login_credential_permission.opt_mx_login_credential_id = :user_id");
        $stm->execute(array(":user_id" => $user_id));

        //Loop through the results
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $perm->permissionList[$row["txt_name"]] = true;
        }
        return $perm;
    }

    //Create populate Role Object
    public static function getPermittedSections($user_id) {
        $sections = []; //Create new role object
        //Prepate statement and execute it
//        if ($_SESSION['login_type'] === 'agent' || $_SESSION['login_type'] === 'staff_agent') {
//            $sections[] = ['section_name' => 'Bookings'];
//        } else {
            $group_string = self::getUserGroups($user_id);
            $stm = self::$db->prepare("SELECT DISTINCT mx_section.txt_name AS 'section_name' FROM mx_section 
                                        JOIN mx_permission ON mx_permission.opt_mx_section_id = mx_section.id 
                                        JOIN mx_group_permission ON mx_group_permission.opt_mx_permission_id = mx_permission.id 
                                        WHERE mx_group_permission.opt_mx_group_id IN ($group_string) 
                                UNION 
                                    SELECT DISTINCT mx_section.txt_name AS 'section_name' FROM mx_login_credential_permission 
                                        JOIN mx_permission ON mx_permission.id = mx_login_credential_permission.opt_mx_permission_id 
                                        JOIN mx_section ON mx_section.id = mx_permission.opt_mx_section_id 
                                        WHERE mx_login_credential_permission.opt_mx_login_credential_id = :user_id ");
            $stm->execute(array(":user_id" => $user_id));

            //Loop through the results
            $sections = $stm->fetchAll(PDO::FETCH_ASSOC);
//        }
        return $sections;
    }

    //Check if the specific role has a given permission
    public function verifyPermission($permission) {
        return isset($this->permissionList[$permission]);
    }

    private static function getUserGroups($user_id) {
        $group_string = '';
        $stm = self::$db->prepare("SELECT * FROM mx_login_credential_group WHERE mx_login_credential_group.opt_mx_login_credential_id = :user_id");
        $stm->execute(array(":user_id" => $user_id));

        //Loop through the results
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $group_string .= $row["opt_mx_group_id"] . ',';
        }

        return rtrim($group_string, ',');
    }

    function verifySubMenuPermissions($submenus, $user_id) {
        $group_id = $this->getUserGroups($user_id);
        $sql = "SELECT DISTINCT mx_permission.* FROM mx_permission 
                    JOIN mx_group_permission ON mx_group_permission.opt_mx_permission_id = mx_permission.id 
                    WHERE mx_group_permission.opt_mx_group_id IN (" . $group_id . ")
                UNION 
                    SELECT DISTINCT mx_permission.* FROM mx_permission 
                    JOIN mx_login_credential_permission ON mx_login_credential_permission.opt_mx_permission_id = mx_permission.id 
                    WHERE mx_login_credential_permission.opt_mx_login_credential_id = '" . $user_id . "'";
        $permissions = self::$db->query($sql)->fetchAll();
        $permission_values = [];
        $submenuvalues = [];
        foreach ($permissions as $permission) {
//            print_r($permission['txt_name']);
            $permission_value = explode('_', trim($permission['txt_name']));
            $perm = 'view_' . $permission_value[1];
            $permission_values[] = $perm;
        }
        foreach ($submenus as $submenu) {
            $menu = explode(' ', trim($submenu['txt_name']));
            $sub = 'view_' . strtolower($menu[0]);
            if (in_array($sub, $permission_values)) {
                $submenuvalues[] = $submenu;
            }
        }

//        $temp = [
//            'submenus' => $submenuvalues,
//            'permissions' => $permission_values
//        ];
//
//        print_r(json_encode($temp));
        
        return $submenuvalues;
    }

}
