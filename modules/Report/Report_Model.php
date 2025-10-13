<?php

namespace Modules\Report;

use Libs\Model;
use Libs\Perm_Auth;

/**
 * Description of Report
 *
 * @author Fatma Azad
 */
class Report_Model extends Model
{

    private string $table = "";
    private string $view_dir = "report/";
    private string $title = "Reports";
    public array $no_old_data = ['generate_report'];

    public function getHiddenFields() : array
    {
        return [];
    }

    public function getFormHiddenFields() : array
    {
        return [];
    }

    function getControls() : array
    {
	    return [];
    }

    function getActions() : array
    {
	    return [];
    }

    function getTable() : string
    {
        return $this->table;
    }

    function getTitle() : string
    {
        return $this->title;
    }

    function getViewDir() : string
    {
        return $this->view_dir;
    }

    function getReportTypes() : array
    {
        $permitted_section = [];
        $permission = Perm_Auth::getPermissions();
        $data = [
            ['report_type' => 'General_Report', 'permission' => 'print_general_report', 'report_title' => 'General', 'report_id' => 1, 'report_header' => 'general.html']
        ];

        foreach ($data as $key => $value) {
            if ($permission->verifyPermission($value['permission'])) {
                $permitted_section[] = $value;
            }
        }
        return $permitted_section;
    }

    function getReportFormFields($type) : array
    {
        $formFields = [];
        if ($type == "General_Report") {
            $formFields = [
                'group_by' => [],
                'filters' => [],
                'categories' => [
                    ['Id' => 0, 'Name' => 'Summary']
                ],
                'title' => 'GENERAL REPORT'];
        }
        return $formFields;
    }

    function getReportFilterValues($filter, $type, $category) : array
    {
	    return [];
    }

    public function getAuditActions($table)
    {
        $results = $this->db->select("SELECT 1 AS [Id], REPLACE(txt_action,'_',' ') AS [Name] FROM mx_audit_trail WHERE txt_table=:table GROUP BY txt_action ORDER BY txt_action ASC", [':table' => filter_var($table, FILTER_SANITIZE_SPECIAL_CHARS)]);
        if ($results) {
            $data = array_merge([['Id' => 0, 'Name' => 'All Actions']], $results);
        } else {
            $data = $results;
        }
        return $data;
    }

}
