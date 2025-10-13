<?php
namespace Modules\Menu;

use Libs\Model;

/**
 * Description of InstitutionRule
 *
 * @author Fatma Sharif
 */
class Menu_Model extends Model {

    public string $table = "mx_menu";
    private string $view_dir = "menu/";
    private string $title = "Menu";
    public array $no_old_data = ['saveMenu'];
	private $view_table;

	public function getHiddenFields() : array
    {
        return ['id'];
    }

    public function getFormHiddenFields() : array
    {
        return ['id'];
    }

    function getControls() : array
    {
        return [];
    }

    function getActions() : array
    {
        return [
            ["action" => "Edit_Incident", "name" => "Edit", "icon" => "fa-edit", "color" => "blue", "url" => "incident"],
        ];
    }

    public function getTable($view_table = false): string
    {
        if ($view_table) {
            return $this->view_table;
        }
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

    public function getRecord($id, $table) {
	    return parent::getRecord($id, $table);
    }

    function getAllRecordsPerInstitution($table, $institution) : array
    {
        $sort_column = 'id';
        $sort_order = 'ASC';
        $tableName = $table;

        $requestData = [
            'search' => '',
            'order_column' => $sort_column,
            'order_dir' => $sort_order,
            'start' => 0,
            'length' => 10,
            'location' => '',
            'current' => 0
        ];

        if (isset($_REQUEST['search'])) {
            $requestData['search'] = $_REQUEST['search'];
            $requestData['order_column'] = $_REQUEST['order'] == 'id' ? $sort_column : $_REQUEST['order'];
            $requestData['order_dir'] = ($_REQUEST['order'] == 'id' && $table == 'mx_transaction') ? $sort_order : $_REQUEST['dir'];
            $requestData['start'] = $_REQUEST['start'] == 'undefined' ? 0 : $_REQUEST['start'];
            $requestData['length'] = $_REQUEST['length'];
            $requestData['location'] = $_REQUEST['loc'];
            $requestData['current'] = $_REQUEST['current'];
        }

        $col_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $tableName . "' AND TABLE_SCHEMA = '" . DB_NAME . "'";

        // Get all columns
        $columns = array();
        $col_result = $this->db->select($col_sql);
        foreach ($col_result as $col) {
            $columns[] = $col['COLUMN_NAME'];
        }

        // Start fetching data from the table
        // Total records in the table
        $count_sql = "SELECT COUNT(id) AS id FROM " . $tableName;
        $count_result = $this->db->select($count_sql);
        $total_records = $count_result[0]['id'];

        // Start cinstructing the query
        $sql = "SELECT mx_incident.* FROM " . $tableName . " join mx_subscriber on (mx_subscriber.id = mx_incident.opt_mx_subscriber_id) WHERE 1 = 1 and mx_subscriber.opt_mx_institution_id = {$institution} ";
        if (!empty($requestData['search']) && strlen(trim($requestData['search'])) > 0) {
            $sql .= " AND (" . $columns[0] . " LIKE '%" . $requestData['search'] . "%' ";
            for ($col = 1; $col < sizeof($columns); $col++) {
                $sql .= "OR " . $columns[$col] . " LIKE '%" . $requestData['search'] . "%' ";
            }
            $sql .= ")";
        }

        $result = $this->db->select($sql);
        $filtered_count = sizeof($result);

        //$sql .= " ORDER BY " . $requestData['order_column'] . " " . $requestData['order_dir'] . " LIMIT " . $requestData['start'] * $requestData['length'] . ", " . $requestData['length'] . " ";
        $result = $this->db->select($sql);
        $returned_count = sizeof($result);
        $total_pages = ($filtered_count == 0) ? 1 : ceil($filtered_count / $requestData['length']);

        // Create a string containing the list of columns to for sorting purpose
        // Get the visible columns to be sown
        $classname = $table;
        $object_name = ucfirst(str_replace("mx_", "", $classname)) . '_Model';
        $object = new $object_name();
        $hidden_columns = $object->getHiddenFields();
        $sort_column_label = "ID"; // the display label in the sort column button
        // Create an array of columns and labels available for setting
        $colums_string = "[";
        foreach ($columns as $value) {
            if (!(in_array($value, $hidden_columns)) && $value != 'tim_transaction_time') {
                $label = $this->cleanTableColumnName($value);
                $colums_string .= "{'column':'" . $value . "','label':'" . $label . "'},";
                if ($requestData['order_column'] == $value) {
                    $sort_column_label = $label;
                }
            }
        }
        rtrim($colums_string, ",");
        $colums_string .= "]";

        return array(
            $result, [
                'recordsTotal' => $filtered_count,
                'recordsFiltered' => intval($filtered_count),
                'recordsReturned' => $returned_count,
                'currentPage' => $requestData['start'] + 1,
                'totalPages' => $total_pages,
                'pageSize' => $requestData['length'],
                'columns' => $colums_string,
                'column_label' => $sort_column_label
            ],
            $requestData
        );
    }

    function getFilteredRecordsPerInstitution($table, $filter, $filter_value, $institution) : array
    {
        $tableName = $table;
        $sort_column = 'id';
        $sort_order = 'ASC';

        $requestData = [
            'search' => '',
            'order_column' => $sort_column,
            'order_dir' => $sort_order,
            'start' => 0,
            'length' => 10,
            'location' => '',
            'current' => 0
        ];
        if (isset($_REQUEST['search'])) {
            $requestData['search'] = $_REQUEST['search'];
            $requestData['order_column'] = $_REQUEST['order'] == 'id' ? $sort_column : $_REQUEST['order'];
            $requestData['order_dir'] = ($_REQUEST['order'] == 'id' && $table == 'mx_transaction') ? $sort_order : $_REQUEST['dir'];
            $requestData['start'] = $_REQUEST['start'] == 'undefined' ? 0 : $_REQUEST['start'];
            $requestData['length'] = $_REQUEST['length'];
            $requestData['location'] = $_REQUEST['loc'];
            $requestData['current'] = $_REQUEST['current'];
        }

        $col_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '"
                . $tableName . "' AND TABLE_SCHEMA = '" . $_ENV['DB_NAME'] . "'";

        // Get all columns
        $columns = array();
        $col_result = $this->db->select($col_sql);
        foreach ($col_result as $col) {
            $columns[] = $col['COLUMN_NAME'];
        }

        // Start fetching data from the table
        // Total records in the table
        $count_sql = "SELECT COUNT(id) AS id FROM " . $tableName;
        $count_result = $this->db->select($count_sql);
        $total_records = $count_result[0]['id'];

        // Start constructing the query
        $filter_string = "";
        $params = [];

        for ($i = 0; $i < count($filter); $i++) {
            if ($i > 0) {
                $filter_string .= ' AND ';
            }

            $filter_string .= $filter[$i] . '=:' . $filter[$i];
            $params[':' . $filter[$i]] = $filter_value[$i];
        }


        if ($filter_string != "") {
            $sql = "SELECT mx_incident.* from mx_incident join mx_subscriber on (mx_subscriber.id = mx_incident.opt_mx_subscriber_id) WHERE mx_subscriber.opt_mx_institution_id = {$institution} AND $filter_string";
        } else {
            $sql = "SELECT mx_incident.* from mx_incident join mx_subscriber on (mx_subscriber.id = mx_incident.opt_mx_subscriber_id) WHERE mx_subscriber.opt_mx_institution_id = {$institution} ";
        }

        if (!empty($requestData['search']) && strlen(trim($requestData['search'])) > 0) {
            $sql .= " AND (" . $columns[0] . " LIKE '%" . $requestData['search'] . "%' ";
            for ($col = 1; $col < sizeof($columns); $col++) {
                $sql .= "OR " . $columns[$col] . " LIKE '%" . $requestData['search'] . "%' ";
            }
            $sql .= ")";
        }

        $result = $this->db->select($sql, $params);
        $filtered_count = sizeof($result);
        if ($tableName == 'mx_transaction' && $requestData['order_column'] == 'dat_transaction_date') {
            $sql .= " ORDER BY " . $requestData['order_column'] . " " . $requestData['order_dir'] . ", tim_transaction_time " . $requestData['order_dir'] . " LIMIT " . $requestData['start'] * $requestData['length'] . ", " . $requestData['length'] . " ";
        } else {
            $sql .= " ORDER BY " . $requestData['order_column'] . " " . $requestData['order_dir'] . " LIMIT " . $requestData['start'] * $requestData['length'] . ", " . $requestData['length'] . " ";
        }
        //$sql .= " ORDER BY " . $requestData['order_column'] . " " . $requestData['order_dir'] . " LIMIT " . $requestData['start'] * $requestData['length'] . ", " . $requestData['length'] . " ";
        $result = $this->db->select($sql, $params);
        $returned_count = sizeof($result);
        $total_pages = ($filtered_count == 0) ? 1 : ceil($filtered_count / $requestData['length']);

        // Create a string containing the list of columns to for sorting purpose
        // Get the visible columns to be sown

        $classname = $table;

        $object_name = ucfirst(str_replace("my", "", str_replace("mx_", "", $classname))) . '_Model';
        $object = new $object_name();
        $hidden_columns = $object->getHiddenFields();
        $sort_column_label = "ID"; // the display label in the sort column button
        // Create an array of columns and labels available for setting
        $colums_string = "[";
        foreach ($columns as $value) {
            if (!(in_array($value, $hidden_columns)) && $value != 'tim_transaction_time') {
                $label = $this->cleanTableColumnName($value);
                $colums_string .= "{'column':'" . $value . "','label':'" . $label . "'},";
                if ($requestData['order_column'] == $value) {
                    $sort_column_label = $label;
                }
            }
        }
        rtrim($colums_string, ",");
        $colums_string .= "]";

        return array(
            $result, [
                'recordsTotal' => $filtered_count,
                'recordsFiltered' => intval($filtered_count),
                'recordsReturned' => $returned_count,
                'currentPage' => $requestData['start'] + 1,
                'totalPages' => $total_pages,
                'pageSize' => $requestData['length'],
                'columns' => $colums_string,
                'column_label' => $sort_column_label
            ],
            $requestData
        );
    }

    public function getMenus() : array
    {
        $query = "SELECT * from mx_menu WHERE int_parent IS NULL ORDER BY int_parent";
        $result = $this->db->select($query);
        $data = [];
        if ($result) {
            foreach ($result as $row) {
                $row['txt_name'] = ucwords($row['txt_name']);
                $sql = "SELECT * from mx_menu WHERE int_parent = " . $row['id'] . " ORDER BY int_position";

                $row['children'] = $this->db->select($sql);
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getFormDropdowns($id = null) : array
    {
        $permissions = [];
        $result1 = $this->db->select("SELECT * from mx_menu where int_parent is null  ORDER BY id ASC");
        if ($result1) {
            foreach ($result1 as $value) {
                $parent_ids[] = ['id' => $value['id'], 'name' => $value['txt_name']];
            }
        }
        if ($id) {
            $result2 = $this->db->select("SELECT * from mx_menu where int_parent is null  ORDER BY id ASC");
            if ($result2) {
                foreach ($result2 as $value) {
                    $permissions[] = ['id' => $value['id'], 'name' => $value['txt_name']];
                }
            }
        }
        $result3 = $this->db->select("SELECT * from mx_menu WHERE int_parent IS NULL ORDER BY int_parent");
        $all_menus = [];
        if ($result3) {
            foreach ($result3 as $row) {
                $row['txt_name'] = ucwords($row['txt_name']);
                $sql = "SELECT * from mx_menu WHERE int_parent = " . $row['id'] . " ORDER BY int_position";

                $row['children'] = $this->db->select($sql);
                $all_menus[] = $row;
            }
        }

        $result4 = $this->db->select("SELECT * from mx_menu where int_parent is Null");
        $all_parents = [];
        if ($result4) {
            foreach ($result4 as $row) {
                $all_parents[] = $row;
            }
        }
	    return [
	        'int_parent_ids' => $parent_ids,
	        'all_menus' => $all_menus,
	        'all_parents' => $all_parents
	    ];
    }

    function cleanTableColumnName($name) : string
    {
        $label = $name;
        if ($label != 'email') {
            $label = substr($name, 4);
        }
        $label = str_replace("mx_", "", $label);
        $label = str_replace("_id", "", $label);
	    return ucwords(str_replace("_", " ", $label));
    }

}
