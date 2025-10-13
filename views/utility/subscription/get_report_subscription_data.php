<?php

include '../../../inc/config.php';
require '../../../libs/Database.php';

$user_id = $_GET['user_id'];

function getUserSubscriptions($id) {
    $db = new Database();
    $sql = "SELECT opt_mx_report_type_id AS 'report_type', opt_mx_frequency_id AS 'frequency' 
        FROM mx_report_subscriber 
        WHERE txt_mx_user_id = '" . $id . "' AND opt_mx_active_id = 1";
    $result = $db->select($sql);
    return $result;
}

function getReportFrequencies() {
    $db = new Database();
    $sql = "SELECT id, txt_name as 'frequency' FROM mx_frequency";
    $result = $db->select($sql);
    return $result;
}

function getReportTypes() {
    $db = new Database();
    $sql = "SELECT id, txt_name as 'type' FROM mx_report_type";
    $result = $db->select($sql);
    return $result;
}

$userSubscriptions = getUserSubscriptions($user_id);
$userSubscriptions_data = array();

$frequencies = getReportFrequencies();
$frequencies_data = array();

$report_types = getReportTypes();
$reportTypes_data = array();

if ($userSubscriptions) {
    foreach ($userSubscriptions as $subcription) {
        $userSubscriptions_data[] = ['report_type' => $subcription['report_type'], 'frequency' => $subcription['frequency']];
    }
}

if ($report_types) {
    foreach ($report_types as $type) {
        $type_frequencies = array();
        foreach ($frequencies as $frequency) {
            $state = 0;
            foreach ($userSubscriptions_data as $subscription){
                if ($type['id'] == $subscription['report_type'] && $frequency['id'] == $subscription['frequency']){
                    $state = 1;
                    //break;
                }
            }
            $type_frequencies[] = ['id' => $frequency['id'], 'frequency' => $frequency['frequency'], 'state' => $state];
        }
        $reportTypes_data[] = ['id' => $type['id'], 'f_type' => $type['type'], 't_frequencies' => $type_frequencies];
    }
}

if ($frequencies) {
    foreach ($frequencies as $frequency) {       
        $frequencies_data[] = ['id' => $frequency['id'], 'frequency' => $frequency['frequency']];
    }
}


print json_encode(
        [
            'frequencies' => $frequencies_data,
            'report_types' => $reportTypes_data
        ]
    );
