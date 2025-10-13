<?php


namespace Modules\Translation;

use Libs\Model;
use Libs\Perm_Auth;

class Translation_Model extends Model {

    public $table = "";
    private $view_dir = "ticket/";
    private $title = "Tickets";
    private $parent_key = "ticket_id";
    private $view_table = "";

    private $default_translation = 'en';

    private $translations = [
        'en' => MX17_APP_ROOT . '/locale/lang.en.php',
        'fr' => MX17_APP_ROOT . '/locale/lang.fr.json',
        'sw' => MX17_APP_ROOT . '/locale/lang.sw.json',
    ];

    public function getTranslationData() {
        $default_translations = require $this->translations[$this->default_translation];

        dd($default_translations);
    }

    public function getHiddenFields() {
        return ['id','row_id','Project Code','Status','Contact Person','Phone','Email',
            'Ticket Type','Ticket Priority','Updated Date','Updated Time',
            'color','Request Reference Number'
        ];
    }

    function getProfileButtons()
    {
        return [
            ["action" => "assign_user", "controller" => "Ticket", "label" => "Assign User", "disabled" => "", "cssclass" => "btn btn-primary", "show" => "",
                "params" => "initial_tab_data.row_id","function" => "showProfileActionForm"],
            ["action" => "mark_ticket_critical", "controller" => "Ticket", "label" => "Mark Critical", "disabled" => "", "cssclass" => "btn btn-danger", "show" => "",
                "params" => "initial_tab_data.row_id","function" => "showProfileActionForm"],
            ["action" => "escalate_ticket", "controller" => "Ticket", "label" => "Escalate Ticket", "disabled" => "", "cssclass" => "btn btn-danger", "show" => "",
                "params" => "initial_tab_data.row_id","function" => "showProfileActionForm"],
            ["action" => "close_ticket", "controller" => "Ticket", "label" => "Close Ticket", "disabled" => "", "cssclass" => "btn btn-danger", "show" => "",
                "params" => "initial_tab_data.row_id","function" => "showProfileActionForm"],
            ["action" => "reopen_ticket", "controller" => "Ticket", "label" => "Reopen Ticket", "disabled" => "", "cssclass" => "btn btn-warning", "show" => "",
                "params" => "initial_tab_data.row_id","function" => "showProfileActionForm"],
            ["action" => "complete_ticket", "controller" => "Ticket", "label" => "Complete Ticket", "disabled" => "", "cssclass" => "btn btn-success", "show" => "",
                "params" => "initial_tab_data.row_id","function" => "showProfileActionForm"],
            ["action" => "create_ticket_note", "controller" => "Ticket", "label" => "Add Note", "disabled" => "", "cssclass" => "btn btn-primary", "show" => "",
                "params" => "initial_tab_data.row_id","function" => "showProfileActionForm"],
            ["action" => "change_ticket_priority", "controller" => "Ticket", "label" => "Change Ticket Priority", "disabled" => "", "cssclass" => "btn btn-primary", "show" => "",
                "params" => "initial_tab_data.row_id","function" => "showProfileActionForm"],
            ["action" => "view_ticket_history", "controller" => "Ticket", "label" => "View Ticket History", "disabled" => "", "cssclass" => "btn btn-success", "show" => "1==1",
                "params" => "initial_tab_data.row_id","function" => "showProfileActionForm"],
        ];
    }

    public function getFormHiddenFields()
    {
        return [];
    }

    function getControls()
    {
        return [];
    }

    function getActions()
    {
        return [
            [
                "action" => "Edit_Ticket", "name" => "Edit", "icon" => "fa-edit", "color" => "caf", "url" => "Ticket",
                'disabled' => [
                    'OR' => [
                        'opt_mx_ticket_status_id' => [4,1]
                    ]
                ]
            ],
            ["action" => "Suspend_Ticket", "name" => "Suspend", "icon" => "fa-lock", "color" => "orange", "url" => "Ticket"],
            ["action" => "Activate_Ticket", "name" => "Activate", "icon" => "fa-unlock", "color" => "ccm", "url" => "Ticket"]

        ];
    }

    function getAssociatedRecordActions($caller) {
        return [];
    }

    function getTabs()
    {
       return ['Users'];
    }

    function getProfileHiddenColumns()
    {
        return [
            "id", "row_id","inspector_type_id","status_id"
        ];
    }

    function getAssociatedRecordHiddenColumns()
    {
        return ['opt_mx_state_id',
            'inspector_id','opt_mx_area_id',
            'area_id','checkpoint_id','ticket_id','opt_mx_ticket_user_status_id'];
    }

    function getTable($view_table = false)
    {
        if ($view_table) {
            return $this->view_table;
        }
        return $this->table;
    }

    function getTitle()
    {
        return $this->title;
    }

    function getViewDir()
    {
        return $this->view_dir;
    }

    function getParentKey()
    {
        return $this->parent_key;
    }

    public function getFormDropdowns()
    {
        $data = [];
        return $data;

    }

    public function getTableLabels() {
        $data = [];
        $query2 = $this->db->select("SELECT id, txt_color FROM mx_state");

        if ($query2) {
            foreach ($query2 as $record) {
                $id = $record['id'];
                $value = $record['txt_color'];
                $data['opt_mx_state_id'][$id] = $value;
            }
        }

        return $data;
    }

    public function generateTicketNo($initial = 'TCK', $length = 10, $invoice_number = 999, $id) {
        return $initial.str_pad($invoice_number + $id, $length, "0", STR_PAD_LEFT);
    }

}