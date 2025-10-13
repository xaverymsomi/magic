<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Backup {

    function __construct() {
//        session_start();
    }

    public function createDbBackup($dbname) {
        $filename = $dbname . '-backup-' . date('dmy-hi', time());
        $file_location = './db_backups/' . $filename . '.sql';
        $query = "BACKUP DATABASE $dbname TO DISK = '$file_location'";
        try {
        echo $query;
            $sth = $this->prepare($query);
            $sth->execute();
            return ['status' => 200, 'file' => $filename . ".sql"];
        } catch (Exception $ex) {
            return ['status' => 200, 'file' => '', 'message' => $ex->getMessage()];
        }
//        $table_query = "SHOW TABLES";
//        $table_result = $this->getRowsFromDB($table_query);
//        $tables = $table_result['result'];
//        $db_query = "SELECT DATABASE()";
//        $dbname_result = $this->selectQuery($db_query);
//        $dbname = $dbname_result[0][0];
//
//        $_query = $this->processingTables($tables);
//
//        date_default_timezone_set('Africa/Dar_es_Salaam');
//        $filename = $dbname . '-backup-' . date('dmy-hi', time());
//        $handle = fopen('./db_backups/' . $filename . '.sql', 'w+');
//        fwrite($handle, $_query);
//        fclose($handle);
//        return $this->saveBackup($filename . ".sql", $row_id);
    }

    function processingTables($tables) {
        $_query = "";
        foreach ($tables as $_table) {
            $table = $_table[0];
            $select_query = "SELECT * FROM " . $table . "";
            $select_result = $this->getRowsFromDB($select_query);
            $results = $select_result['result']; //array of values key => value form
            $num_fields = $select_result['num_cols'];
            $_query .= "DROP TABLE " . $table . ";";
            $show_query = "SHOW CREATE TABLE " . $table . "";
            $show_result = $this->getTableInfoFromDB($show_query);
            $_query .= "\n\n" . $show_result . ";\n\n";

            $_query .= $this->processingTableRows($results, $table, $num_fields);

            $_query .= "\n\n\n";
        }
        return $_query;
    }

    function processingTableRows($results, $table, $num_fields) {
        $_query = "";
        foreach ($results as $result) {
            if (sizeof($result)) {
                $_query .= "INSERT INTO " . $table . " VALUES";
                $_query .= "(";
                for ($j = 0; $j < $num_fields; $j++) {
                    $result[$j] = addslashes($result[$j]);
                    $result[$j] = str_replace("\n", "\\n", $result[$j]);
                    if (isset($result[$j])) {
                        $_query .= '"' . $result[$j] . '"';
                    } else {
                        $_query .= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $_query .= ',';
                    }
                }
                $_query .= ");\n";
            }
        }
        return $_query;
    }

    function saveBackup($name, $row_id) {
        $user_id = $_SESSION['id'];
        $file = './db_backups/' . $name;
        $size = $this->formatSizeUnits(filesize($file));
        $date = date('Y-m-d');
        $insert_query = "INSERT INTO mx_backup(txt_name,txt_size,dat_date_created,txt_created_by,txt_row_value) VALUES('$name', '$size', '$date', '$user_id', '$row_id')";
        return $this->executeQuery($insert_query);
    }

    function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }

    function getRowsFromDB($sql) {
        $db = new Database();
        $query = $db->query($sql);
        $fetch = $query->fetchAll();
        $num_cols = $query->columnCount();
        $num_rows = $query->rowCount();
        $result = ['result' => $fetch, 'num_rows' => $num_rows, 'num_cols' => $num_cols];
        return $result;
    }

    function getRowFromDB($sql) {
        $db = new Database();
        $query = $db->query($sql);
        $fetch = $query->fetch();
        $num_rows = $query->rowCount();
        $result = ['result' => $fetch, 'num_rows' => $num_rows];
        return $result;
    }

    function getTableInfoFromDB($sql) {
        $db = new Database();
        $query = $db->query($sql);
        $fetch = $query->fetch();
        return $fetch[1];
    }

    function insertToDB($sql) {
        $db = new Database();
        $stmt = $db->prepare($sql);
        return $stmt->execute();
    }

    //for delete and insert queries
    function executeQuery($query) {
        $db = new Database();
        $count = $db->prepare($query);
        return $count->execute();
    }

    //for select queries
    function selectQuery($query) {
        $db = new Database();
        $stm = $db->query($query);
        $fetch = $stm->fetchAll();
        return $fetch;
    }

}