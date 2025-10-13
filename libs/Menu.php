<?php

/**
 * Description of Menu
 *
 * @author abdirahmanhassan
 */
namespace Libs;

class Menu {

    function __construct(Database $db) {
        $this->db = $db;
    }

    function getSubMenu($id) {
        $sql = "SELECT * FROM mx_" . strtolower(get_class($this)) . " WHERE int_parent=:id  ORDER BY int_position ASC";
        $result = $this->db->select($sql, array(':id' => $id));
        return $result;
    }

    function getMainMenu() {
        $sql = 'SELECT * FROM mx_' . strtolower(get_class($this)) . " WHERE int_parent IS NULL  ORDER BY int_position ASC";
        $result = $this->db->select($sql);
        return $result;
    }
}
