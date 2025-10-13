<?php
namespace Modules\Dashboard;

use Libs\Model;

class Dashboard_Model extends Model{

    private $view_dir = "/dashboard/";
    private $title = "Dashboard";

    public function getTitle($plural = false): string
    {
        if ($plural) {
            return $this->title_plural;
        }
        return $this->title;
    }

    function getViewDir() {
        return $this->view_dir;
    }

    function getControls()
    {
        return [
            ['action' => 'New_Ticket', 'color' => 'success', 'title' => 'Create New Business',
                'name' => 'New Business', 'url' => "'Dashboard'"]
        ];
    }

    public function getFormDropdowns()
    {
        $data = [];

        $result = $this->db->select("SELECT txt_row_value, txt_name FROM mx_stakeholder  ORDER BY id asc");

        if ($result) {
            foreach ($result as $key => $value) {
                $data['opt_mx_stakeholder_ids'][] = ['id' => $value['txt_row_value'], 'name' => $value['txt_name']];
            }
        }

        if ($result) {
            foreach ($result as $key =>  $value) {
                $data['opt_mx_area_ids'][] = ['id' => $value['txt_row_value'], 'name' => $value['txt_name']];
            }
        }

        return $data;
    }
    
}
