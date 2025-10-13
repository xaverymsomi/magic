<?php

namespace Modules\Notification;

use Libs\Model;
use Libs\Perm_Auth;

class Notification_Model extends Model
{
    public string $title = "Notification";
    public string $title_plural = "Notifications";
    public string $parent_key = "notification_id";
    public string $view_table = "mx_notification_view";
    private string $table = "mx_notification";

    public function getTable($view_table = false): string
    {
        if ($view_table) return $this->view_table;

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

    public function getHiddenFields(): array
    {
        return [
            'int_added_by', 'txt_logo', 'opt_mx_user_id', 'color', 'txt_row_value', 'notification_type_color',
            'dat_added_date', 'txt_sms_sender', 'State', 'opt_mx_application_id', 'Added By', 'Added Date', 'Notification Type'
        ];
    }


    public function getControls(): array
    {
        return [
            [
                'action' => 'create', 'color' => 'success', 'title' => 'Add New Notification', "permission" => "add_notifications",
                'name' => 'New Notification', 'url' => "'Notification'"
            ],
        ];
    }

    public function getActions(): array
    {
        return [
            [
                "action" => "Edit_Notification", "name" => "Edit", "icon" => "fa-edit", "color" => "blue", "url" => "Notification",
                'disabled' => [
                    'OR' => [
                        'opt_mx_state_id' => [INACTIVE]
                    ]
                ]
            ],
            [
                "action" => "Suspend_Notification", "name" => "Suspend", "icon" => "fa-lock", "color" => "orange", "url" => "Notification",
                'disabled' => [
                    'OR' => [
                        'opt_mx_state_id' => [INACTIVE]
                    ]
                ]
            ],
            [
                "action" => "Activate_Notification", "name" => "Activate", "icon" => "fa-unlock", "color" => "ccm", "url" => "Notification",
                'disabled' => [
                    'OR' => [
                        'opt_mx_state_id' => [ACTIVE]
                    ]
                ]
            ]
        ];
    }

    public function getProfileButtons($id): array
    {
        $permitted_section = [];
        $permission = Perm_Auth::getPermissions();

        if ($id == 0) {
            $data = [
                [
                    "action" => "assign_inspector",
                    "permission" => "assign_inspector",
                    "controller" => "'Institution'",
                    "label" => "Assign Inspector",
                    "disabled" => "", // initial_tab_data.Status === 'Inactive'
                    "show" => "1==1", // initial_tab_data.opt_mx_ticket_status_id != 4
                    "cssclass" => "btn btn-primary",
                    "params" => "initial_tab_data.row_id",
                    "function" => "showProfileActionForm"
                ],
            ];
        } else if ($id > 0) {
            $data = [];
        }
        foreach ($data as $key => $value) {
            if ($permission->verifyPermission($value['permission'])) {
                $permitted_section[] = $value;
            }
        }
        return $permitted_section;
    }

    public function getTabs(): array
    {
        return [];
    }

    public function getFormDropdowns(): array
    {
        $result4 = $this->db->select("SELECT * FROM mx_notification_type ORDER BY txt_name ASC");
        if ($result4) {
            foreach ($result4 as $value) {
                $notification_type[] = ['id' => $value['id'], 'name' => $value['txt_name']];
            }
        }
        $result5 = $this->db->select("SELECT * FROM mx_application ORDER BY txt_application_reference ASC");
        if ($result5) {
            foreach ($result5 as $value) {
                $applications[] = ['id' => $value['id'], 'name' => $value['txt_application_reference']];
            }
        }
        return [
            'opt_mx_notification_type_ids' => $notification_type,
            'opt_mx_application_ids' => $applications,
        ];
    }

    public function getTableLabels(): array
    {
        $labels = [
            'opt_mx_state_id' => [
                'query' => "SELECT id, txt_name, txt_color FROM mx_state",
                'key' => "id", 'value' => "txt_name", 'color' => 'txt_color'
            ],
            'opt_mx_notification_type_id' => [
                'query' => "SELECT id, txt_name, txt_color FROM mx_notification_type",
                'key' => "id", 'value' => "txt_name", 'color' => 'txt_color'
            ],
        ];
        return parent::generateTableLabels($labels);
    }

    public function getProfileHiddenColumns(): array
    {
        return ['id', 'row_id'];
    }

    public function getTableFormatters(): array
    {
        return [];
    }
}