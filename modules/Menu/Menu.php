<?php

namespace Modules\Menu;

use Exception;
use Libs\Controller;
use Libs\Perm_Auth;

class Menu extends Controller
{
    public $module = 'Menu';
    public $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new Menu_Model();
    }

    public function index() : void
    {
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('view_menu')) {
            $this->view->title = "All " . $this->model->getTitle() . "s";
            $this->view->dropdowns = $this->model->getFormDropdowns();
            $this->render('home');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    function last_position($isParent, $parent_id = 0)
    {
        if ($isParent) {
            $query = "SELECT TOP 1 int_position as last_position FROM mx_menu WHERE int_parent=$parent_id  ORDER BY int_position DESC";
        } else {
            $query = "SELECT TOP 1 int_position as last_position FROM mx_menu WHERE int_parent is null ORDER BY int_position DESC";
        }
        $result = $this->model->db->select($query);
        $data = 0;
        if (!empty($result['last_position'])) {
            $data = $result['last_position'];
        } else {
            $data = 1;
        }
        return $data + 1;
    }

    public function saveMenu() : void
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);

        $user = $_SESSION['id'];
        $function = $posted_data['func_name'];
        $data_to_post = $posted_data['new_data'];
        $new_data['txt_link'] = filter_var($data_to_post['txt_link'], FILTER_SANITIZE_SPECIAL_CHARS);
        $new_data['txt_name'] = filter_var($data_to_post['txt_name'], FILTER_SANITIZE_SPECIAL_CHARS);
        $new_data['txt_title'] = filter_var($data_to_post['txt_title'], FILTER_SANITIZE_SPECIAL_CHARS);
        $result_array = [];

        $icon = NULL;
        $parent = NULL;
        $link = "#";
        if (!empty($data_to_post['txt_icon'])) {
            $icon = filter_var($data_to_post['txt_icon'], FILTER_SANITIZE_SPECIAL_CHARS);
        }

        if (!empty($new_data['txt_link'])) {
            $link = $new_data['txt_link'];
        }

        if (empty($data_to_post['int_parent'])) {
            // Add Section
            $section_data = [
                'txt_name' => $new_data['txt_name'],
                'txt_row_value' => $this->model->getGUID('mx_section')
            ];
            $this->model->create($section_data, 'mx_section');
            $new_data['int_position'] = filter_var($data_to_post['int_position'], FILTER_SANITIZE_NUMBER_INT);
            $parents = $this->model->db->select("SELECT * FROM mx_menu WHERE int_parent IS NULL AND int_position >= :position ORDER BY int_position ASC", [':position' => filter_var($data_to_post['int_position'], FILTER_SANITIZE_NUMBER_INT)]);
            if (sizeof($parents)) {
                $position = filter_var($data_to_post['int_position'], FILTER_SANITIZE_NUMBER_INT);
                foreach ($parents as $key => $value) {
                    $position++;
                    if ($value['int_position'] >= $data_to_post['int_position']) {
                    }
                }
            }
            $utility = $this->model->db->select("SELECT * FROM mx_menu WHERE txt_name =:name", [':name' => filter_var('Utility', FILTER_SANITIZE_SPECIAL_CHARS)]);
            if ($utility) {
                $all_parents = $this->model->db->select("SELECT * FROM mx_menu WHERE int_parent IS NULL");
                if ($utility[0]['int_position'] <= $new_data['int_position']) {
                    $query = "UPDATE mx_menu SET int_position = :position WHERE id =:id";
                    $query_data = [
                        ':position' => filter_var(sizeof($all_parents) + 1, FILTER_SANITIZE_NUMBER_INT),
                        ':id' => filter_var($utility[0]['id'], FILTER_SANITIZE_NUMBER_INT)
                    ];
                    $stmt = $this->model->db->prepare($query);
                    $stmt->execute($query_data);
                    $new_data['int_position'] = sizeof($all_parents);
                }
            }
            $save_query = "INSERT INTO mx_menu (txt_name,txt_icon,int_position,txt_link,txt_title, txt_row_value) VALUES('" . $new_data['txt_name'] . "','" . $icon . "'," . $new_data['int_position'] . ",'" . $link . "','" . $new_data['txt_title'] . "'";
            if ($_ENV['DB_TYPE'] == 'sqlsrv') {
                $save_query .= ", NEWID())";
            } else {
                $save_query .= ", UUID())";
            }
        } else {
            $last_position = $this->model->db->select("SELECT TOP 1 int_position as last_position FROM mx_menu WHERE int_parent=:parent  ORDER BY int_position DESC", [':parent' => filter_var($data_to_post['int_parent'], FILTER_SANITIZE_NUMBER_INT)]);
            if (sizeof($last_position)) {
                $new_data['int_position'] = filter_var($last_position[0]['last_position'] + 1, FILTER_SANITIZE_NUMBER_INT);
            } else {
                $new_data['int_position'] = 1;
            }
            $parent = $data_to_post['int_parent'];
            $save_query = "INSERT INTO mx_menu (txt_name,txt_icon,int_parent,int_position,txt_link,txt_title, txt_row_value) VALUES('" . $new_data['txt_name'] . "','" . $icon . "'," . $parent . "," . $new_data['int_position'] . ",'" . $link . "','" . $new_data['txt_title'] . "'";

            if ($_ENV['DB_TYPE'] == 'sqlsrv') {
                $save_query .= ", NEWID())";
            } else {
                $save_query .= ", UUID())";
            }
        }
        $count = $this->model->db->prepare($save_query);
        $result = $count->execute();

        if ($result) {
            $save_result = 200;
        } else {
            $save_result = 100;
        }

        $result_array = ['status' => $save_result, 'title' => 'Section Data'];

        echo json_encode($result_array);
    }

    public function edit($id) : void
    {
        $posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('edit_menu')) { //checking permission 
            $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
            if ($returned_id > -1) {

                $data = $this->model->getRecord($returned_id, $this->model->getTable());

                $view_data = [
                    'id' => $posted_id,
                    'txt_name' => $data['txt_name'],
                    'txt_link' => $data['txt_link'],
                    'txt_title' => $data['txt_title'],
                    'txt_icon' => $data['txt_icon'],
                    'int_parent' => $data['int_parent'],
                    'int_position' => $data['int_position']
                ];

                $this->view->title = 'Update ' . $this->model->getTitle();
                $this->view->data = $view_data;
                $this->view->dropdowns = $this->model->getFormDropdowns($returned_id);
                $this->render('edit');
            } else {
                $this->view->subtitle = "Menu Editing Error";
                $this->renderFull('views/templates/not_found');
            }
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function post_edit()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true); //convert json object
        $this->model->db->beginTransaction();
        try {
            $row_id = filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS);
            $id = $this->model->getRecordIdByRowValue($this->model->getTable(), $row_id);
            $menu = $this->model->getRecord($id, 'mx_menu');
            if ($menu) {
                if (empty($menu['int_parent'])) {
                    $values = [
                        ':name' => filter_var(trim($posted_data['txt_name']), FILTER_SANITIZE_SPECIAL_CHARS),
                        ':older_name' => filter_var(trim($menu['txt_name']), FILTER_SANITIZE_SPECIAL_CHARS)
                    ];
                    $stmt = $this->model->db->prepare("UPDATE mx_section SET txt_name=:name WHERE txt_name =:older_name");
                    $stmt->execute($values);
                }
            }
            if ($id > -1) {
                $data = [
                    'txt_name' => $posted_data['txt_name'],
                    'txt_link' => $posted_data['txt_link'],
                    'txt_title' => $posted_data['txt_title'],
                    'txt_icon' => $posted_data['txt_icon'],
                    'int_parent' => $posted_data['int_parent'],
                    'int_position' => $posted_data['int_position']
                ];
                // update 
                $this->updatePosition($row_id, $posted_data['int_position'], $posted_data['int_parent']);

                $this->model->update($data, $this->model->getTable(), $id);
                $this->model->db->commit();
                echo json_encode(201);
            } else {
                $this->view->subtitle = "Menu Editing Error " . $row_id;
                $this->renderFull('views/templates/not_found');
            }
        } catch (Exception $ex) {
            echo json_encode(100);
            echo $ex->getMessage();
        }
    }

    public function updatePosition($id, $currentLocation, $parentId)
    {
        $result = $this->model->db->select('SELECT * FROM mx_menu WHERE txt_row_value = :id', [':id' => $id]);

        $previous_position = [
            ':parent' => $result[0]['int_parent'],
            ':position' => $result[0]['int_position']
        ];

        $next_position = [
            ':parent' => $parentId,
            ':position' => $currentLocation
        ];
        if ($currentLocation == $previous_position[':position']) {
            // 
            $query = "UPDATE mx_menu SET int_position = :prev_position WHERE int_parent=:previous_parent and int_position = :new_position";
            $stmt = $this->model->db->prepare($query);
            $res = $stmt->execute([
                ':prev_position' => $previous_position[':position'], ':previous_parent' => $previous_position[':parent'],
                ':new_position' => $next_position[':position']
            ]);
        } else {
            $query = "UPDATE mx_menu SET int_position = int_position + 1 WHERE int_parent=:next_parent and int_position >= :new_position";
            $stmt = $this->model->db->prepare($query);
            $res = $stmt->execute([
                ':next_parent' => $next_position[':position'],
                ':new_position' => $next_position[':position']
            ]);
            if ($result[0]['txt_name'] == 'Utility') {
                $count = 1;
                // Reorder Menu
                $menus = $this->model->db->select("SELECT * FROM mx_menu WHERE txt_name NOT IN('Utility') AND int_parent IS NULL ORDER BY int_position ASC");
                //            print_r($menus);
                foreach ($menus as $key => $value) {
                    //                print_r($value);
                    $query = "UPDATE mx_menu SET int_position = " . $count . " WHERE id=:id";
                    $stmt = $this->model->db->prepare($query);
                    $stmt->execute([':id' => $value['id']]);
                    $count += 1;
                }
            } else {
                // Reorder Menu
                $menus = $this->model->db->select('SELECT * FROM mx_menu WHERE int_parent IS NULL AND int_position >= :new_position', [':new_position' => $next_position[':position']]);
                //            print_r($menus);
                foreach ($menus as $key => $value) {
                    //                print_r($value);
                    $query = "UPDATE mx_menu SET int_position = int_position + 1 WHERE id=:id";
                    $stmt = $this->model->db->prepare($query);
                    $stmt->execute([':id' => $value['id']]);
                }
            }
        }
    }

    public function get_user_menus()
    {
        $perm = new Perm_Auth();
        $user_id = $_GET['user_id'];
        $main_menus = $this->getMainMenu();
        $data = [];
        $menus = [];
        $sections = $perm->getPermittedSections($user_id);
        //print_r($sections);
        foreach ($main_menus as $menu) {
            foreach ($sections as $section) {
                if ($menu['txt_name'] == $section['section_name']) {
                    $menus[] = $menu;
                    break;
                }
            }
        }
        //print_r($menus);
        //iterate through menus array
        foreach ($menus as $menu) {
            $submenus = $this->getSubMenu($menu['id']);
            if ($menu['txt_name'] == 'Utility') {
                $submenus = $perm->verifySubMenuPermissions($submenus, $user_id);
                //        print_r(json_encode($submenus));
            }

            $subdata = [];
            if (sizeof($submenus)) {
                foreach ($submenus as $sub) {
                    $subdata[] = ['name' => trans($sub['txt_name']), 'link' => APP_DIR . $sub['txt_link'], 'title' => $sub['txt_title'], 'icon' => $sub['txt_icon']];
                }
            }
            //ChromePhp::log($menu['txt_name']);
            if ($menu['txt_link'] == "#") {
                $data[] = [
                    'id' => $menu['id'], 'name' => trans($menu['txt_name']),
                    'link' => $menu['txt_link'], 'title' => $menu['txt_title'],
                    'icon' => $menu['txt_icon'], 'submenus' => $subdata
                ];
            } else {
                $data[] = [
                    'id' => $menu['id'], 'name' => trans($menu['txt_name']),
                    'link' => APP_DIR . $menu['txt_link'], 'title' => $menu['txt_title'],
                    'icon' => $menu['txt_icon'], 'submenus' => $subdata
                ];
            }
        }

        print json_encode(['data' => $data]);
    }

    function getMainMenu()
    {
        $sql = 'SELECT * FROM mx_menu WHERE int_parent IS NULL  ORDER BY int_position';
        return $this->model->db->select($sql);
    }

    function getSubMenu($id)
    {
        $sql = "SELECT * FROM mx_menu WHERE int_parent=:id  ORDER BY int_position ASC";
        return $this->model->db->select($sql, array(':id' => $id));
    }
}
