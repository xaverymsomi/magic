<?php

include '../../../inc/config.php';
require '../../../libs/Database.php';

function getUser() {
    $db = new Database();
    $sql = "select id, txt_name as 'name' from mx_user";
    $result = $db->select($sql);
    return $result;
}

$users = getUser();

print json_encode(['users' => $users]);
