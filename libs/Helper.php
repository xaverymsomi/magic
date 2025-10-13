<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Helper
 *
 * @author abdirahmanhassan
 */
class Helper {
       //retrieve data from database
    public static function loadData($table) {
        echo $table;
        $database = new Database();
        $sql = "SELECT * FROM " . strtolower($table) . " ORDER BY id ASC";
        $result = $database->select($sql);

        foreach ($result as $value) {
            $array[$value['id']] = $value['txt_name'];
        }
        unset($database);
        return $array;
    }
}
