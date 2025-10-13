<?php

namespace Modules\User;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Libs\Model;

/**
 * Description of User_Model
 *
 * @author abdirahmanhassan
 */
class User_Model extends Model
{
    public string $table;
    private string $title = "User";
    private string $title_plural = "Users";
    private string $parent_key;
    private string $view_table;
	public string $controller;

	public function __construct()
	{
		parent::__construct();

		$this->table = "mx_" . strtolower("User");
		$this->controller = strtolower("User");
		$this->view_table = "mx_" . strtolower("User") . "_view";
		$this->parent_key = strtolower("User_id");
	}

    public function getHiddenFields(): array
    {
        return [
            'id', 'password', 'dat_date_added', 'txt_added_by', 'int_token', 'txt_position',
            'group_color', 'dat_date_last_reset', 'status_id', 'row_id', 'status_id', 'color',
            'Status', 'txt_row_value', 'txt_pin', 'Role',
        ];
    }

    public function getFormHiddenFields(): array
    {
        return array('id', 'password');
    }

    public function getControls(): array
    {
        return [
            [
                'action' => 'create',
                'color' => 'success',
                'title' => 'Add New User',
                "permission" => "add_user",
                'name' => 'New User',
                'url' => "'User'"
            ]
        ];
    }

    public function getActions(): array
    {
        return [
            [
                "action" => "Edit_User",
                "name" => "Edit",
                "icon" => "fa-edit",
                "color" => "blue",
                "url" => "User",
                "disabled" => [
                    'OR' => [
                        'opt_mx_state_id' => [4]
                    ]
                ]
            ],
            [
                "action" => "Suspend_User",
                "name" => "Suspend",
                "icon" => "fa-lock",
                "color" => "orange",
                "url" => "User",
                "disabled" => [
                    'OR' => [
                        'opt_mx_state_id' => [4]
                    ]
                ]
            ],
            [
                "action" => "Activate_User",
                "name" => "Activate",
                "icon" => "fa-unlock",
                "color" => "ccm",
                "url" => "User",
                "disabled" => [
                    'OR' => [
                        'opt_mx_state_id' => [1]
                    ]
                ]
            ]
        ];
    }

    public function getProfileButtons(): array
    {
        return [
            [
                "action" => "reset_password",
                "permission" => 'reset_user_password',
                "controller" => "User",
                "label" => "Reset Password",
                "disabled" => "initial_tab_data.Status === 'Inactive' || cur_institution == 0",
                "cssclass" => "btn btn-primary",
                "show" => "1 == 1",
                "params" => "initial_tab_data.id",
                "function" => "showProfileActionForm"
            ],
            [
                'action' => 'change_user_group',
                'permission' => 'change_user_group',
                'controller' => 'User',
                'label' => 'Change Group',
                'disabled' => "initial_tab_data.Status === 'Inactive' || cur_institution == 0",
                'cssclass' => 'btn btn-success',
                'show' => '1 == 1',
                'params' => 'initial_tab_data.id',
                'function' => 'showProfileActionForm'
            ],
        ];
    }

    public function getTabs(): array
    {
        return [];
    }

    public function getProfileHiddenColumns(): array
    {
        return ["id", 'txt_added_by', 'int_token', 'txt_row_value', 'row_id', 'status_id'];
    }

    public function getTable($view_table = false): string
    {
        if ($view_table) {
            return $this->view_table;
        }
        return $this->table;
    }

    public function getTitle($plural = false): string
    {
        if ($plural) {
            return $this->title_plural;
        }
        return $this->title;
    }

    public function getParentKey(): string
    {
        return $this->parent_key;
    }

    public function getFormDropdowns(): array
    {
        $array = [];

        $sql = $_SESSION['role'] == 3 ? "SELECT * FROM mx_group ORDER BY id ASC" : "SELECT * FROM mx_group WHERE id NOT IN(3) ORDER BY id ASC";
        $result = $this->db->select($sql);

        if ($result) {
            foreach ($result as $value) {
                $array[] = ['id' => $value['id'], 'name' => $value['txt_name']];
            }
        }

        $data['opt_mx_groups_ids'] = $array;

        return $data;
    }

    public function getAssociatedRecordActions($caller): array
    {
        return [];
    }

    public function getInputFilters(): array
    {
        $filters = [
            "id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => 1,
                    "max_range" => 2147483647
                ]
            ],
            "txt_name" => FILTER_SANITIZE_SPECIAL_CHARS,
            "opt_mx_institution_id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => 0,
                    "max_range" => 2147483647
                ]
            ]
        ];
        return $filters;
    }

    public function getTableLabels(): array
    {
        $labels = [
            'opt_mx_state_id' => [
                'query' => 'SELECT id, txt_name, txt_color FROM mx_state',
                'key' => 'id', 'value' => 'txt_name', 'color' => 'txt_color'
            ],
            'opt_mx_role_id' => [
                'query' => 'SELECT id, txt_name, txt_color FROM mx_group ',
                'key' => 'id',
                'value' => 'txt_name',
                'color' => 'txt_color'
            ],
        ];
        return parent::generateTableLabels($labels);
    }

	public function getController(): string
	{
		return $this->controller;
	}
}
