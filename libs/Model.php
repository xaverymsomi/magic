<?php

/**
 * Description of Model
 *
 * @author abdirahmanhassan
 */

namespace Libs;

use Exception;
use PDO;


class Model
{

    public $sql;
    public $db;
    private string $error_ = './controllers/Error.php';
    private $condition;
    private $columns;
    private bool $institution;

    function __construct()
    {
        $this->db = new Database();
        $this->condition = '';
        $this->columns = [];
        $this->sql = '';
    }

    public static function generateRandomString($length = 8) : string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    function getAllRecordsFiltered($table)
    {
        $columns = $this->getColumns();
        $sql = "SELECT $columns FROM " . $table;
        if ($this->condition != '') {
            $sql .= ' WHERE ' . $this->condition;
        }
        $result = $this->db->select($sql);
        $this->clear();
        return $result;
    }

    private function getColumns() : string
    {
        $columns = '';
        if (count($this->columns) > 0) {
            for ($i = 0; $i < count($this->columns); $i++) {
                $columns .= $this->columns[$i];
                if ($i < count($this->columns) - 1) {
                    $columns .= ',';
                }
            }
        } else {
            $columns = '*';
        }
        return $columns;
    }

    private function clear() : void
    {
        $this->condition = '';
        $this->columns = [];
        $this->sql = '';
    }

    function getRecordFiltered($table)
    {
        $columns = $this->getColumns();
        $sql = "SELECT $columns FROM " . $table;
        if ($this->condition != '') {
            $sql .= ' WHERE ' . $this->condition;
        }
        $result = $this->db->select($sql);
        $this->clear();
        return $result[0];
    }

    function columns($columns) : static
    {
        $this->columns = $columns;
        return $this;
    }

    function where($column, $value, $condition = '=', $join = '') : static
    {
        $this->condition .= " $column $condition $value $join ";
        return $this;
    }

    function prune($records, $columns_map) : array
    {
        $data = [];
        if ($records) {
            foreach ($records as $value) {
                $record = [];
                foreach ($columns_map as $key => $column) {
                    $record[$key] = $value[$column];
                }
                $data[] = $record;
            }
        }
        return $data;
    }

    function getAllRecords($table, $model_class = null) : array|string
    {
        try {
            $sort_column = 'id';
            $sort_order = 'DESC';
            $tableName = $table;

            if ($tableName == 'mx_transaction') {
                $sort_column = 'dat_transaction_date';
                $sort_order = 'DESC';
            }

            if ($tableName == 'mx_request') {
                $sort_column = 'dat_requested_date';
                $sort_order = 'DESC';
            }

	        $requestData = $this->getRequestedData($sort_column, $sort_order, $table);

	        if ($tableName == 'mx_subscriber') {
                if (isset($_REQUEST['startDate'])) {
                    $requestData['startDate'] = $_REQUEST['startDate'];
                    $requestData['endDate'] = $_REQUEST['endDate'];
                }
            }

            $col_sql = $this->write_table_column_select_statement($tableName);

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
            $sql = "SELECT * FROM " . $tableName . " WHERE 1 = 1"; // OG

            if (!empty($requestData['search']) && strlen(trim($requestData['search'])) > 0) {
                if (in_array($_ENV['DB_TYPE'], ['sqlsrv', 'odbc'])) {
                    $sql .= " AND ([" . $columns[0] . "] LIKE '%" . $requestData['search'] . "%' ";
                } else {
                    $sql .= " AND (" . $columns[0] . " LIKE '%" . $requestData['search'] . "%' ";
                }

                for ($col = 1; $col < sizeof($columns); $col++) {
                    if (in_array($_ENV['DB_TYPE'], ['sqlsrv', 'odbc'])) {
                        $sql .= "OR [" . $columns[$col] . "] LIKE '%" . $requestData['search'] . "%' ";
                    } elseif ($_ENV['DB_TYPE'] == 'mysql') {
                        $sql .= "OR " . $columns[$col] . " LIKE '%" . $requestData['search'] . "%' ";
                    }
                }
                $sql .= ")";
                ChromePhp::log($sql);
            }

            if (isset($_REQUEST['filterable']) && isset($_REQUEST['filter']) && $_REQUEST['filter'] != '' && $_REQUEST['filterable'] != '') {
                $sql .= " AND {$requestData['filterable']} = '{$requestData['filter']}' ";
            }

            $sql_count = "SELECT COUNT(id) AS 'count' FROM " . $tableName . " WHERE 1 = 1";

            if (!empty($requestData['search']) && strlen(trim($requestData['search'])) > 0) {
                $first_col = 0;
                foreach ($columns as $column) {
                    if ( str_starts_with($column, "dat_") || str_starts_with($column, "tim_") ) {
                        $first_col++;
                    } else {
                        break;
                    }
                }

                if (in_array($_ENV['DB_TYPE'], ['sqlsrv', 'odbc'])) {
                    $sql_count .= " AND ([" . $columns[$first_col] . "] LIKE '%" . $requestData['search'] . "%' ";
                } else {
                    $sql_count .= " AND (" . $columns[$first_col] . " LIKE '%" . $requestData['search'] . "%' ";
                }
			for ($col = $first_col + 1; $col < sizeof($columns); $col++) {
                    if ( !str_starts_with($columns[$col], "dat_") && !str_starts_with($columns[$col], "tim_") ) {
	                    $sql_count = $this->getInArrayDb($columns[$col], $requestData['search'], $sql_count);
                    }
                }
                $sql_count .= ")";
            }

            if (isset($_REQUEST['filterable']) && isset($_REQUEST['filter']) && $_REQUEST['filter'] != '' && $_REQUEST['filterable'] != '') {
                $sql_count .= " AND {$requestData['filterable']} = '{$requestData['filter']}' ";
            }

            $result_count = $this->db->select($sql_count);
            $filtered_count = $result_count[0]['count'];

            $sql .= $this->write_rows_range_select_statement($requestData, $requestData);

            $result = $this->db->select($sql);
            $returned_count = sizeof($result);
            $total_pages = ($filtered_count == 0) ? 1 : ceil($filtered_count / $requestData['length']);

            $object_name = get_called_class();
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
                    'recordsTotal' => $total_records,
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
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function write_table_column_select_statement($tableName) : string
    {
        $sql = '';
        switch ($_ENV['DB_TYPE']) {
            case 'mysql':
                $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $tableName . "' AND TABLE_SCHEMA = '" . $_ENV['DB_NAME'] . "'";
                break;
        case 'odbc':
        case 'sqlsrv':
                $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $tableName . "'";
                break;
        default:
                break;
        }
        return $sql;
    }

    private function write_rows_range_select_statement($data, $table) : string
    {
        $sql = '';
        if ($table == 'mx_transaction' && $data['order_column'] == 'dat_transaction_date') {
            $sql .= " ORDER BY " . $data['order_column'] . " " . $data['order_dir'] . ", tim_transaction_time " . $data['order_dir'];
        } else {
            $sql .= " ORDER BY " . $data['order_column'] . " " . $data['order_dir'];
        }

        switch ($_ENV['DB_TYPE']) {
            case 'mysql':
                $sql .= " LIMIT " . $data['start'] * $data['length'] . ", " . $data['length'];
                break;
            case 'odbc':
            case 'sqlsrv':
                $sql .= " OFFSET " . $data['start'] * $data['length'] . " ROWS FETCH NEXT " . $data['length'] . " ROWS ONLY";
                break;
            default:
                break;
        }

        return $sql;
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

    function getRecord($id, $table)
    {
        $sql = "SELECT * FROM " . $table . " WHERE id = :id";
        $result = $this->db->select($sql, array(':id' => $id));
        if (sizeof($result)) {
            if (array_key_exists('rowguid', $result[0])) {
                unset($result[0]['rowguid']);
            }
            return $result[0];
        }
        return [];
    }

    function getRecordByRowValue($table, $id)
    {
        $sql = "SELECT * FROM " . $table . " WHERE txt_row_value = :id";
        $result = $this->db->select($sql, array(':id' => $id));
        if (sizeof($result)) {
            if (array_key_exists('rowguid', $result[0])) {
                unset($result[0]['rowguid']);
            }

            return $result[0];
        }
        return [];
    }

    function getRecordByFieldName($table, $field_name, $value, $single_record = false)
    {
        $selector = $single_record ? 'TOP 1 *' : '*';
        $sql = 'SELECT ' . $selector . ' FROM ' . $table . ' WHERE ' . $field_name . ' = :value';

        $result = $this->db->select($sql, [':value' => $value]);
        if (sizeof($result)) {
            return $result[0];
        }
        return [];
    }

    function getRecordByFieldNames($table, $field_names)
    {
        $result = $this->getRecordsByFieldNames($table, $field_names);
        if ($result) {
            return $result[0];
        } else {
            return [];
        }
    }

    function getRecordsByFieldNames($table, $field_names)
    {
        $conditions = "";
        $params = [];
        $count = 0;
        foreach ($field_names as $field_name => $value) {
            $params[":$field_name"] = $value;
            if ($count == count($field_names) - 1) {
                $conditions .= "$field_name = :$field_name ";
            } else {
                $conditions .= "$field_name = :$field_name AND ";
            }
            $count++;
        }
        $conditions = rtrim($conditions, "AND");

        $sql = "SELECT * FROM " . $table . " WHERE $conditions";
        $result = $this->db->select($sql, $params);
        if (sizeof($result)) {
            return $result;
        }
        return [];
    }

    function getAllRecordByFieldName($table, $field_name, $value)
    {
        $sql = 'SELECT * FROM ' . $table . ' WHERE ' . $field_name . ' = :value';
        $result = $this->db->select($sql, array(':value' => $value));
        if (sizeof($result)) {
            return $result;
        }
        return [];
    }

    function getRecordFieldByFieldName($table, $field_name, $value, $field_returned)
    {
        $sql = "SELECT * FROM " . $table . " WHERE $field_name = :value";
        $result = $this->db->select($sql, array(':value' => $value));
        if (sizeof($result)) {
            return $result[0][$field_returned];
        }
        return [];
    }

    public function generateTableLabels(array $labels): array
    {
        $data = [];
        foreach ($labels as $key => $label) {
            $query = $this->db->select($label['query']);
            if (!$query) continue;
            foreach ($query as $record) {
                $id = $record[$label['key']];
                $value = $record[$label['value']];
                $color = isset($label['color']) ? $record[$label['color']] : "";
                $type = $label['type'] ?? "text";
                $data[$key][$id] = [
                    'value' => $value, 'type' => $type, 'color' => $color
                ];
            }
        }
        return $data;
    }

    function getFilteredRecords($table, $filter, $filter_value, $filter_operators = []) : array
    {
        $tableName = $table;
        $sort_column = 'id';
        $sort_order = 'DESC';
        if ($table == 'mx_transaction') {
            $sort_column = 'dat_transaction_date';
            $sort_order = 'DESC';
        }

	    $requestData = $this->getRequestedData($sort_column, $sort_order, $table);

	    $col_sql = $this->write_table_column_select_statement($tableName);

        // Get all columns
        $columns = array();
        $col_result = $this->db->select($col_sql);
        foreach ($col_result as $col) {
            $columns[] = $col['COLUMN_NAME'];
        }

        $count_sql = "SELECT COUNT(id) AS id FROM " . $tableName;
        $count_result = $this->db->select($count_sql);
        $total_records = $count_result[0]['id'];

        $filter_string = "";
        $params = [];

        for ($i = 0; $i < count($filter); $i++) {

            if ($i > 0) {
                if (count($filter_operators) > 0 && ($i - 1) < count($filter_operators)) {
                    $filter_string .= " {$filter_operators[$i - 1]} ";
                } else {
                    $filter_string .= ' AND ';
                }
            }

            if ($tableName == "mx_transaction" && $filter[$i] == "opt_mx_service_id") {
                $filter_string .= $filter[$i] . ' IN (SELECT id FROM mx_service WHERE opt_mx_service_category_id = ' . $filter_value[$i] . ')';
            } elseif ($tableName == "mx_transaction_list_view" && $filter[$i] == "txt_description") {
                $filter_string .= $filter[$i] . " LIKE '%" . $filter_value[$i] . "%'";
            } else {
                if (in_array($_ENV['DB_TYPE'], ['sqlsrv', 'odbc'])) {
                    $filter_string .= "[" . $filter[$i] . "]" . '=:' . str_replace(' ', '_', $filter[$i]);
                    $params[':' . str_replace(' ', '_', $filter[$i])] = $filter_value[$i];
                } else {
                    $filter_string .= $filter[$i] . '=:' . $filter[$i];
                    $params[':' . $filter[$i]] = $filter_value[$i];
                }
            }
        }

        $sql = "SELECT * FROM " . $tableName . " WHERE $filter_string";
        if ($table == "mx_class_service_limit") {
            $sql = "SELECT $tableName.* FROM " . $tableName . "  JOIN mx_council_class on mx_council_class.id = mx_class_service_limit.opt_mx_council_class_id WHERE mx_council_class.$filter_string";
        }
        if (!empty($requestData['search']) && strlen(trim($requestData['search'])) > 0) {
            $sql .= " AND (" . $columns[0] . " LIKE '%" . $requestData['search'] . "%' ";
            for ($col = 1; $col < sizeof($columns); $col++) {
                if (in_array($_ENV['DB_TYPE'], ['sqlsrv', 'odbc'])) {
                    $sql .= "OR [" . $columns[$col] . "] LIKE '%" . $requestData['search'] . "%' ";
                } else {
                    $sql .= "OR " . $columns[$col] . " LIKE '%" . $requestData['search'] . "%' ";
                }
            }
            $sql .= ")";
        }

        if (isset($_REQUEST['filterable']) && isset($_REQUEST['filter']) && $_REQUEST['filter'] != '' && $_REQUEST['filterable'] != '') {
            $sql .= " AND {$requestData['filterable']} = '{$requestData['filter']}' ";
        }

        $sql_count = "SELECT COUNT(id) AS 'count' FROM " . $tableName . " WHERE $filter_string";
        if (!empty($requestData['search']) && strlen(trim($requestData['search'])) > 0) {
            $first_col = 0;
            foreach ($columns as $column) {
                if ( str_starts_with($column, "dat_") || str_starts_with($column, "tim_") ) {
                    $first_col++;
                } else {
                    break;
                }
            }
            $sql_count .= " AND (" . $columns[$first_col] . " LIKE '%" . $requestData['search'] . "%' ";
            for ($col = $first_col + 1; $col < sizeof($columns); $col++) {
                if ( !str_starts_with($columns[$col], "dat_") && !str_starts_with($columns[$col], "tim_") ) {
                    $sql_count = $this->getInArrayDb($columns[$col], $requestData['search'], $sql_count);
                }
            }
            $sql_count .= ")";
        }

        if (isset($_REQUEST['filterable']) && isset($_REQUEST['filter']) && $_REQUEST['filter'] != '' && $_REQUEST['filterable'] != '') {
            $sql_count .= " AND {$requestData['filterable']} = '{$requestData['filter']}' ";
        }

        $result_count = $this->db->select($sql_count, $params);
        $filtered_count = $result_count[0]['count'];

        $sql .= $this->write_rows_range_select_statement($requestData, $requestData);

        $result = $this->db->select($sql, $params);
        $returned_count = sizeof($result);
        $total_pages = ($filtered_count == 0) ? 1 : ceil($filtered_count / $requestData['length']);

        $object_name = get_called_class();
        $object = new $object_name();
        $hidden_columns = $object->getHiddenFields();
        $sort_column_label = "ID";

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

    public function create($post_data, $table, $require_validation = true) : int
    {
        $tableName = $table;
        $_length = sizeof($this->getClassFields($tableName)['properties']);
        $data = array_slice($post_data, 0, $_length);
        $valid_data = $data;

        if ($require_validation) {
            $validate = new Validation();
            $valid_data = $validate->validateForm($data, $this->getClassFields($tableName)['required']);
        }

        if ($valid_data) {
            $result = $this->db->save($tableName, $valid_data, get_class($this));
            if ($result) {
                Session::set('returned', 200);
                return 200;
            } else {
                Session::set('returned', 100);
                return 100;
            }
        } else {
            Session::set('returned', 3000);
            return 3000;
        }
    }

    function getClassFields($table) : array
    {
        $tableName = $table;

        $sql = $this->write_class_fields_columns_selector_statement($tableName);
        $result = $this->db->select($sql);
        $properties = array();
        $required = array();

        $this->extract_form_fields_properties($result, $properties, $required);

        return ["properties" => $properties, "required" => $required];
    }

    private function write_class_fields_columns_selector_statement($table) : string
    {
        $sql = '';
        switch ($_ENV['DB_TYPE']) {
            case 'mysql':
                $sql .= "SHOW COLUMNS FROM " . $table;
                break;
            case 'odbc':
            case 'sqlsrv':
                $sql .= "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $table . "'";
                break;
            default:
                break;
        }

        return $sql;
    }

    private function extract_form_fields_properties($data, &$properties, &$required) : void
    {
        switch ($_ENV['DB_TYPE']) {
            case 'mysql':
                foreach ($data as $field) {
                    $properties[] = $field['Field'];
                    if ($field['Null'] == "NO" && $field['Extra'] != "auto_increment") {
                        $required[] = $field['Field'];
                    }
                }
                break;
            case 'odbc':
            case 'sqlsrv':
                foreach ($data as $field) {
                    $properties[] = $field['COLUMN_NAME'];
                    if ($field['IS_NULLABLE'] == "NO") {
                        $required[] = $field['COLUMN_NAME'];
                    }
                }
                break;
            default:
                break;
        }
    }

    public function update($post_data, $table, $where, $where_key = 'id') : int
    {
        $_length = sizeof($this->getClassFields($table)['properties']);
        $data = array_slice($post_data, 0, $_length);
        $validate = new Validation();
        $valid_data = $validate->validateForm($data, $this->getClassFields($table)['required']);

        if ($valid_data) {
            if (gettype($where) == 'array') {
                $result = $this->db->updateCustom($table, (array)$valid_data, $where);
            } else {
                $result = $this->db->update($table, (array)$valid_data, $where, $where_key);
            }

            if ($result) {
                Session::set('returned', 201);
                return 201;
            } else {
                Session::set('returned', 101);
                return 101;
            }
        } else {
            Session::set('returned', 3000);
            return 3000;
        }
    }

    //Added $orderBy to support manual ordering of columns : Can be improved further.

    function getTHeaders($table) : array
    {
        $tablename = $table;

        $fields = $this->getClassFields($tablename);
        return $fields['properties'];
    }

    function syslogin($table, $email, $password)
    {
        $user_id = "";
        $sql = "SELECT * FROM " . $table . " WHERE email=:email AND password= :password AND opt_mx_status_id=1";
        $result = $this->db->select($sql, array(':email' => $email, ':password' => $password));
        if (sizeof($result) > 0) {
            $name = $this->db->select(" SELECT * FROM " . $result[0]['txt_domain'] . " WHERE id =:id ", [':id' => $result[0]['user_id']]);
            if ($name) {
                foreach ($name[0] as $key => $value) {
                    if ($key !== 'id') {
                        $result[0][$key] = $value;
                    }
                }
                $sql = "SELECT * FROM mx_login_credential_group WHERE opt_mx_login_credential_id= :id";
                $user_id = $result[0]['user_id'];
                $group = $this->db->select($sql, array(':id' => $result[0]['id']));

                $sql_branch = "SELECT mx_user_areas.*,mx_area.txt_name FROM mx_user_areas
                                JOIN mx_area ON mx_area.id = mx_user_areas.opt_mx_area_id 
                                WHERE mx_user_areas.user_id = :id 
                                  AND mx_user_areas.opt_mx_state_id = :status";
                $branch = $this->db->select($sql_branch, array(':id' => $user_id, ':status' => 1));
                $sql_branch = "SELECT mx_user_areas.*,mx_area.txt_name FROM mx_user_areas
                                JOIN mx_area ON mx_area.id = mx_user_areas.opt_mx_area_id 
                                WHERE mx_user_areas.user_id = :id 
                                  AND mx_user_areas.opt_mx_state_id = :status";
                $branch = $this->db->select($sql_branch, array(':id' => $user_id, ':status' => 1));

                if (sizeof($branch) == 0) {
                    return [];
                }
                if (sizeof($branch) == 0) {
                    return [];
                }

                //            $scheme = $this->db->select("SELECT opt_mx_scheme_id FROM mx_institution_scheme WHERE opt_mx_council_id = $council_id AND opt_mx_status_id = 1");

                if (sizeof($group) > 0) {
                    $theme = [['txt_name' => 'bcx', 'txt_primary_colour' => '000000', 'txt_secondary_colour' => 'ff0000']];
                    $results = [$group[0], $result[0], $theme[0]];

                    $results = [$group[0], $result[0], $theme[0], $branch[0]];
	                return [$group[0], $result[0], $theme[0], $branch[0]];
                } else {
                    return $result;
                }
            } else {
                return [];
            }
        } else {
            return $result;
        }
    }

    //Added $orderBy to support manual ordering of columns : Can be improved further.
    public function getProfileData($id, $table, $orderBy = null, $sortType = 'ASC')
    {
        if (str_contains($table, '_view')) { // For PHP 7.4: if(strpos($string, '_view') !== false); For 8.0: if (str_contains($table, '_view'))
            $view_suffix = '';
        } else {
            $view_suffix = '_view';
        }

        $sql = 'SELECT * FROM ' . $table . $view_suffix . ' WHERE id = :id';

        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy $sortType ";
        }

        $result = $this->db->select($sql, [':id' => $id]);
        if (sizeof($result)) {
            return $result[0];
        }
        return [];
    }

    public function getProfileDataAlternative($id, $table, $id_param = 'id', $orderBy = null, $sortType = 'ASC')
    {
        $sql = 'SELECT * FROM ' . $table . ' WHERE ' . $id_param . ' = :id';
        if ($orderBy !== null) {
            $sql .= " ORDER BY $orderBy $sortType ";
        }

        $result = $this->db->select($sql, [':id' => $id]);
        if (sizeof($result)) {
            return $result[0];
        }
        return [];
    }

    public function getAssociatedRecords($id, $table, $parent, $hiddens = [])
    {
        $sql = "SELECT ";
        $columns = $this->getTableColumns($table . '_view', $hiddens);

        switch ($_ENV['DB_TYPE']) {
            case 'mysql':
                foreach ($columns as $value) {
                    $sql .= "`" . $value . "`,";
                }
                $sql = rtrim($sql, ",");
                $sql .= " FROM " . $table . "_view WHERE " . $parent . " = :id ORDER BY id DESC";
                if ($table == 'mx_transaction') {
                    $sql .= " LIMIT 10";
                }
                break;
            case 'odbc':
            case 'sqlsrv':
                if (in_array($table, ['mx_transaction', 'mx_sms_log', 'mx_receipt'])) {
                    $sql .= " TOP 10 ";
                }

                foreach ($columns as $value) {
                    $sql .= "[" . $value . "],";
                }
                $sql = rtrim($sql, ",");

                if ($table == "mx_disbursement_account") {
                    $sql .= " FROM " . $table . "_view WHERE " . $parent . " = :id AND State ='Active' ORDER BY id DESC";
                } elseif ($table == "mx_commission_account") {
                    $sql .= " FROM " . $table . "_view ORDER BY id DESC";
                } else {
                    $sql .= " FROM " . $table . "_view WHERE " . $parent . " = :id ORDER BY id DESC";
                }
                break;
            default:
                break;
        }

        if ($table == 'mx_commission_account') {
            $result = $this->db->select($sql);
        } else {
            $result = $this->db->select($sql, [':id' => $id]);
        }
        return $result;
    }

    public function getTableColumns($table, $hiddens = []) : array
    {
        $sql = '';
        $data = [];
        switch ($_ENV['DB_TYPE']) {
            case 'mysql':
                $sql = "SELECT COLUMN_NAME 'column' FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table AND TABLE_SCHEMA = '" . $_ENV['DB_NAME'] . "'";
                break;
            case 'odbc':
            case 'sqlsrv':
                $sql = "SELECT COLUMN_NAME 'column' FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table";
                break;
            default:
                break;
        }
        $result = $this->db->select($sql, [':table' => $table]);
        if (sizeof($result)) {
            foreach ($result as $value) {
                if (!in_array($value['column'], $hiddens)) {
                    $data[] = $value['column'];
                }
            }
        }

        return $data;
    }

    public function getRecordIdByValue($table, $column, $value)
    {
        $binder = [':value' => $value];
        $result = $this->db->select("SELECT id FROM $table WHERE $column = :value", $binder);
        if (count($result)) {
            return $result[0]['id'];
        }
        return -1;
    }

    public function getRowValueById($table, $id)
    {
        $result = $this->db->select("SELECT txt_row_value FROM " . $table . " WHERE id = :id", [':id' => $id]);
        if (count($result)) {
            return $result[0]['txt_row_value'];
        }
        return '';
    }

    public function getlastInsertId($table, $row_value)
    {
        $result = $this->db->select("SELECT id FROM " . $table . " WHERE txt_row_value = :row_value", [':row_value' => $row_value]);
        if (count($result)) {
            return $result[0]['id'];
        }
        return '';
    }

    public function getGUID_OG() : string
    {
        $guid = '';
        $namespace = rand(11111, 99999);
        $uid = uniqid('', true);
        $data = $namespace;
        $data .= date("Y-m-d H:i:s");
        $data .= microtime(true);
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = substr($hash, 0, 8) . '-' .
            substr($hash, 8, 4) . '-' .
            substr($hash, 12, 4) . '-' .
            substr($hash, 16, 4) . '-' .
            substr($hash, 20, 12);
        return $guid;
    }

    public function find($sql, $array = array(), $fetchMode = PDO::FETCH_ASSOC): int
    {
        $sth = $this->db->prepare($sql);

        foreach ($array as $key => $value) {
            $sth->bindValue("$key", $value);
        }
        $sth->execute();
        $sth->fetchAll($fetchMode);

        return $sth->rowCount();
    }

    public function getMaximumIdNumber($table, $lowerboud, $upperboud)
    {
        $number = $lowerboud;
        $result = [];
        switch ($_ENV['DB_TYPE']) {
            case 'mysql':
                $result = $this->db->select("SELECT IFNULL(MAX(id), 0) AS 'maximum' FROM " . $table . " WHERE id > " . $lowerboud . " AND id < " . $upperboud);
                break;
            case 'odbc':
            case 'sqlsrv':
                $result = $this->db->select("SELECT ISNULL(MAX(id), 0) AS 'maximum' FROM " . $table . " WHERE id > " . $lowerboud . " AND id < " . $upperboud);
                break;
            default:
                break;
        }

        if (count($result) > 0 && $result[0]['maximum'] > 0) {
            $number = $result[0]['maximum'];
        }
        return $number;
    }

    public function saveNotificationData($data): bool
    {
        $source_id = $data['source_id'];
        $labels = $data['labels'];
        $values = $data['values'];
        $mobile = $data['mobile'] ?? null;
        $email = $data['email'] ?? null;
        $login_credential_id = $data['login_credential_id'];
        $notification_title = $data['notification_title'] ?? null;
        $notification_message = $data['notification_message'];
        $notification_type = $data['notification_type'];

        $sms_queue_data = [
            'txt_recipient' => filter_var($mobile, FILTER_SANITIZE_SPECIAL_CHARS),
            'opt_mx_source_id' => $source_id,
            'txt_type' => 'SMS',
            'txt_labels' => json_encode($labels),
            'txt_values' => json_encode($values)
        ];

        Log::sysLog('SMS-QUEUE-DATA: ' . json_encode($sms_queue_data));

        $sms_queue = $this->db->save('mx_queue', $sms_queue_data, 'QUEUE-DATA');
        if (!$sms_queue) {
            Log::sysLog(json_encode(['class' => debug_backtrace()[1]['class'], 'function' => debug_backtrace()[1]['function'], 'message' => 'Failed to save SMS queue']));
            return false;
        }

        Log::sysLog('SMS-QUEUE: ' . json_encode($sms_queue));

        $sms = new MXSms();
        $sms->sendTemplateSMS($sms_queue_data['opt_mx_source_id'], $mobile, null, null, null, $labels, $values, 1);

        $email_queue_data = [
            'txt_recipient' => filter_var($email, FILTER_SANITIZE_SPECIAL_CHARS),
            'opt_mx_source_id' => $source_id,
            'txt_type' => 'EMAIL',
            'txt_labels' => json_encode($labels),
            'txt_values' => json_encode($values)
        ];

        Log::sysLog('EMAIL-QUEUE-DATA: ' . json_encode($email_queue_data));

        $email_queue = $this->db->save('mx_queue', $email_queue_data, 'QUEUE-DATA');
        if (!$email_queue) {
            Log::sysLog(json_encode(['class' => debug_backtrace()[1]['class'], 'function' => debug_backtrace()[1]['function'], 'message' => 'Failed to save email queue']));
            return false;
        }

        Log::sysLog('EMAIL-QUEUE: ' . json_encode($email_queue));

        $mail = new MXMail();
        $mail->sendEmail($email_queue_data['opt_mx_source_id'], $email, null, $labels, $values);

        $notification_data = [
            'txt_title' => $notification_title,
            'tar_message' => $notification_message,
            'opt_mx_notification_type_id' => $notification_type,
            'opt_mx_login_credential_id' => filter_var($login_credential_id, FILTER_SANITIZE_SPECIAL_CHARS),
            'dat_from_date' => date('Y-m-d H:i:s'),
            'dat_to_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'dat_added_date' => date('Y-m-d H:i:s'),
            'int_added_by' => $login_credential_id,
            'opt_mx_state_id' => filter_var(ACTIVE, FILTER_SANITIZE_NUMBER_INT),
        ];

        $notification_queue = $this->db->save('mx_notification', $notification_data, 'NOTIFICATION-DATA');
        if (!$notification_queue) {
            Log::sysLog(json_encode(['class' => debug_backtrace()[1]['class'], 'function' => debug_backtrace()[1]['function'], 'message' => 'Failed to save notification']));
            return false;
        }
        return true;
    }

    public function getGUID(?string $table = null): string
    {
        // Generate a unique ID with high entropy
        $uid = uniqid('', true);

        // Combine a random number, server details, and timestamps for more entropy
        $namespace = rand(11111, 99999);
        $data = $namespace;

        // Add unique time-based data
        $data .= $_SERVER['REQUEST_TIME'];  // Request timestamp
        $data .= $_SERVER['REMOTE_ADDR'];   // User's IP address
        $data .= $_SERVER['REMOTE_PORT'];   // User's connection port
        $data .= date("Y-m-d H:i:s");       // Current date and time
        $data .= microtime(true);           // High precision timestamp

        // Hash the combined data for a unique result
        $hash = strtoupper(hash('ripemd128', $uid . md5($data)));

        // Format the hash into a GUID-like structure
        $new_guid = substr($hash, 0, 8) . '-' .
            substr($hash, 8, 4) . '-' .
            substr($hash, 12, 4) . '-' .
            substr($hash, 16, 4) . '-' .
            substr($hash, 20, 12);

        // Check for uniqueness if a table is provided
        if ($table) {
            if (self::getRecordIdByRowValue($table, $new_guid) < 0) {
                return self::getGUID($table);
            }
        }
        return $new_guid;
    }

    public function getRecordIdByRowValue($table, $value)
    {
        $result = $this->db->select("SELECT id FROM " . $table . " WHERE txt_row_value = :value", [':value' => $value]);
        if (count($result)) {
            return $result[0]['id'];
        }
        return -1;
    }

    private function isCurrentInstitutionDCU(): bool
    {
        if (isset($_SESSION['institution_id']) && !empty($_SESSION['institution_id']) && $_SESSION['institution_id'] !== 0) {
            return false;
        }
        return true;
    }

    private function buildArrayFilterString($condition, $open_bracket, $close_bracket, $join_operator)
    {
        $query = "";
        if (count($condition) == 4) {
            $join_operator = "$condition[3]";
        }

        $param = ':' . $condition[0] . $this->generateRandomNo();

        switch (count($condition)) {
            case 2:
                $query .= " $condition[0] = $param ";
                $value = $condition[1];
                break;
            case 3:
            case 4:
                $query .= " $condition[0] $condition[1] $param ";
                $value = $condition[2];
                break;
            default:
                //Illegal condition making
                return false;
        }
        return ['filter_string' => " $open_bracket $join_operator $query $close_bracket ", 'param' => $param, 'param_value' => $value];
    }

    public static function generateRandomNo($length = 6)
    {
        $characters = '1234567890';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $character = $characters[rand(0, strlen($characters) - 1)];
            if ($i == 0 && $character == 0) {
                $charactersStart = '123456789';
                $character = $charactersStart[rand(0, strlen($charactersStart) - 1)];
            }
            $randomString .= $character;
        }

        return $randomString;
    }

    private function buildFilterString($condition)
    {
        $count = 0;
        $query = '';
        $params = [];
        foreach ($condition as $column => $value) {
            if ($count > 0) {
                $query .= ' AND ';
            }
            $param = ':' . $column . $this->generateRandomNo();
            $params[$param] = $value;
            $query .= " $column = $param ";
            $count++;
        }

        return ['filter_string' => " $query ", 'params' => $params];
    }

	/**
	 * @param string $sort_column
	 * @param string $sort_order
	 * @param $table
	 * @return array
	 */
	private function getRequestedData(string $sort_column, string $sort_order, $table) : array
	{
		$requestData = [ 'search' => '', 'order_column' => $sort_column, 'order_dir' => $sort_order, 'start' => 0, 'length' => 25, 'location' => '', 'current' => 0, 'filter' => '', 'filterable' => '', ];

		if( isset($_REQUEST['search']) ) {
			$requestData['search'] = filter_var($_REQUEST['search'], FILTER_SANITIZE_SPECIAL_CHARS);
			$requestData['order_column'] = $_REQUEST['order'] == 'id' ? $sort_column : $_REQUEST['order'];
			$requestData['order_dir'] = ($_REQUEST['order'] == 'id' && $table == 'mx_transaction') ? $sort_order : $_REQUEST['dir'];
			$requestData['start'] = $_REQUEST['start'] == 'undefined' ? 0 : $_REQUEST['start'];
			$requestData['length'] = $_REQUEST['length'];
			$requestData['location'] = $_REQUEST['loc'];
			$requestData['current'] = $_REQUEST['current'];
		}

		if( isset($_REQUEST['filterable']) && isset($_REQUEST['filter']) && $_REQUEST['filter'] != '' && $_REQUEST['filterable'] != '' ) {
			$requestData['filterable'] = filter_var($_REQUEST['filterable'], FILTER_SANITIZE_SPECIAL_CHARS);
			$requestData['filter'] = filter_var($_REQUEST['filter'], FILTER_SANITIZE_SPECIAL_CHARS);
		}
		return $requestData;
	}

	/**
	 * @param $columns
	 * @param $search
	 * @param string $sql_count
	 * @return string
	 */
	private function getInArrayDb($columns, $search, string $sql_count) : string
	{
		if( in_array($_ENV['DB_TYPE'], [ 'sqlsrv', 'odbc' ]) ) {
			$sql_count .= " OR [" . $columns . "] LIKE '%" . $search . "%' ";
		} else {
			$sql_count .= " OR " . $columns . " LIKE '%" . $search . "%' ";
		}
		return $sql_count;
	}
}
