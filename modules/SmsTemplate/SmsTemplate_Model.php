<?php
namespace Modules\SmsTemplate;

use Libs\Model;

class SmsTemplate_Model extends Model {

    public $table = "mx_sms_template";
    private $view_dir = "/smstemplate/";
    private $title = "Sms Setttings";

    public function getHiddenFields() {
        return ['txt_row_value'];
    }

    public function getFormHiddenFields() {
        return [];
    }

    function getControls() {
        return [
            ['action' => 'Add_Sms_template', 'color' => 'success', 'title' => 'Add Sms Template',
                'name' => 'New Sms Template', 'url' => "'SmsTemplate'"],
            ['action' => 'Edit_Sms_Setup', 'color' => 'primary', 'title' => 'Edit Sms Setup',
                'name' => 'Edit Sms Setup', 'url' => "'SmsTemplate'"]
        ];
    }

    function getActions() {
        return [
            ["action" => "Edit_Sms_Content", "name" => "Edit", "icon" => "fa-edit", "color" => "blue", "url" => "SmsTemplate"]
        ];
    }

    function getProfileButtons() {
        return [];
    }

    function getTabs() {
        return [];
    }

    function getProfileHiddenColumns() {
        return ["id"];
    }

    function getAssociatedRecordHiddenColumns() {
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

    public function getFormDropdowns($caller = 0) {
        $reasons = [];
        $institutions = [];
        $languages = [];
        $sql = 'SELECT mx_source.*, COUNT(mx_sms_template.opt_mx_source_id) total 
                FROM mx_source LEFT OUTER JOIN mx_sms_template ON mx_sms_template.opt_mx_source_id = mx_source.id 
                GROUP BY mx_source.id, mx_source.txt_name, mx_source.txt_row_value ORDER BY mx_source.txt_name';
        $result = $this->db->select($sql);

        if ($result) {
            switch ($caller) {
                case 0:
                    foreach ($result as $value) {
                        if ($value['total'] == 0) {
                            $reasons[] = ['id' => $value['id'], 'name' => $value['txt_name']];
                        }
                    }
                    break;
                default:
                    foreach ($result as $value) {
                        if ($value['total'] == 0 || $value['id'] == $caller) {
                            $reasons[] = ['id' => $value['id'], 'name' => $value['txt_name']];
                        }
                    }
                    break;
            }
        }

//        $result1 = $this->db->select("SELECT * FROM mx_institution ORDER BY id ASC");
//        if ($result1) {
//            foreach ($result1 as $value) {
//                $institutions[] = ['id' => $value['id'], 'name' => $value['txt_name']];
//            }
//        }

        $result2 = $this->db->select("SELECT * FROM mx_sms_language ORDER BY id ASC");
        if ($result2) {
            foreach ($result2 as $value) {
                $languages[] = ['id' => $value['id'], 'name' => $value['txt_name']];
            }
        }



        $data = [
            'opt_mx_source_ids' => $reasons,
            'opt_mx_institution_ids' => $institutions,
            'opt_mx_sms_language_ids' => $languages
        ];

        return $data;
    }

    public function getInputFilters() {
        $filters = [
            "id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => 1,
                    "max_range" => 2147483647
                ]
            ],
            "tar_sms_content" => FILTER_SANITIZE_SPECIAL_CHARS,
            "opt_mx_institution_id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => $this->getMinInstitutionId(),
                    "max_range" => $this->getMaxInstitutionId()
                ]
            ],
            "opt_mx_sms_reason_id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => $this->getMinSmsReasonId(),
                    "max_range" => $this->getMaxSmsReasonId()
                ]
            ],
            "opt_mx_sms_language_id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => $this->getMinLanguageId(),
                    "max_range" => $this->getMaxLanguageId()
                ]
            ]
        ];

        return $filters;
    }

    private function getMinSmsReasonId() {
        return $this->db->select("SELECT MIN(id) id FROM mx_sms_reason")[0]['id'];
    }

    private function getMaxSmsReasonId() {
        return $this->db->select("SELECT MAX(id) id FROM mx_sms_reason")[0]['id'];
    }

    private function getMinInstitutionId() {
        return $this->db->select("SELECT MIN(id) id FROM mx_institution")[0]['id'];
    }

    private function getMaxInstitutionId() {
        return $this->db->select("SELECT MAX(id) id FROM mx_institution")[0]['id'];
    }

    private function getMinLanguageId() {
        return $this->db->select("SELECT MIN(id) id FROM mx_sms_language")[0]['id'];
    }

    private function getMaxLanguageId() {
        return $this->db->select("SELECT MAX(id) id FROM mx_sms_language")[0]['id'];
    }

}
