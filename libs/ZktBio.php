<?php

//include '../inc/config.php';
require MX17_APP_ROOT . '/vendor/tad/lib/TADFactory.php';
require MX17_APP_ROOT . '/vendor/tad/lib/TAD.php';
require MX17_APP_ROOT . '/vendor/tad/lib/TADResponse.php';
require MX17_APP_ROOT . '/vendor/tad/lib/Providers/TADSoap.php';
require MX17_APP_ROOT . '/vendor/tad/lib/Providers/TADZKLib.php';
require MX17_APP_ROOT . '/vendor/tad/lib/Exceptions/ConnectionError.php';
require MX17_APP_ROOT . '/vendor/tad/lib/Exceptions/FilterArgumentError.php';
require MX17_APP_ROOT . '/vendor/tad/lib/Exceptions/UnrecognizedArgument.php';
require MX17_APP_ROOT . '/vendor/tad/lib/Exceptions/UnrecognizedCommand.php';
//require MX17_APP_ROOT . '/libs/Database.php';

use TADPHP\TADFactory;
use TADPHP\TAD;

date_default_timezone_set('Africa/Dar_Es_Salaam');

/**
 * Description of ZktBio
 *
 * @author abdirahmanhassan
 */
class ZktBio {

    private $tad;
    private $logs;
    private $device_info;

    function __construct($device_ip) {
        $tad_factory = new TADFactory(['ip' => $device_ip]);
        $this->tad = $tad_factory->get_instance();
        $this->device_info = $this->tad->get_free_sizes()->to_array();      
      
    }

    function getUserInfo($id) {
        return $this->tad->get_user_info(['pin' => $id])->to_array();
    }

    function setUserInfo($user_info) {
        $this->tad->set_user_info(['pin' => $user_info['id'], 'name' => $user_info['txt_name'], 'privilege' => $user_info['opt_mx_privilege_id'], 'password' => $user_info['id'], 'group' => $user_info['opt_mx_role_id']]);
    }

    function getAllUsersInfo() {
        return $this->tad->get_all_user_info()->to_array();
    }

    function deleteUserInfo($id) {
        $this->tad->delete_user(['pin' => $id]);
    }

    function getUserTemplate($id) {
        return $this->tad->get_user_template(['pin' => $id])->to_array();
    }

    function getAllUsersTemplate() {
        return $this->tad->get_user_template()->to_array();
    }

    function setUserTemplate($template) {
        $temp = [
            'pin' => $template['txt_mx_employee_id'],
            'finger_id' => $template['opt_mx_finger_id'], // First fingerprint has 0 as index.
            'size' => $template['int_size'], // Be careful, this is not string length of $template1_vx9 var.
            'valid' => $template['int_valid'],
            'template' => $template['tar_template']
        ];
        
        $this->tad->set_user_template($temp);
    }
    
    //This function delete all fingers template for this user
    //you cannot delete specific finger
    function deleteUserTemplate($id){
        $this->tad->delete_template(['pin'=> $id]);
    }

    function getUserAttendanceLog($id) {
        $this->logs = $this->tad->get_att_log(['pin' => $id])->to_array();
        return $this->logs;
    }

    function getAllUsersAttendanceLog() {
        $this->logs = $this->tad->get_att_log();
        return $this->logs;
    }

    function restartDevice() {
        $this->tad->restart();
    }

    function offDevice() {
        $this->tad->poweroff();
    }

    function disableDevice() {
        $this->tad->disable();
    }

    function enableDevice() {
        $this->tad->enable();
    }

    function getTotalLogs() {
        $logs = $this->tad->get_att_log();
        return $logs->count();
    }

    function filterAttendanceLog($from, $to) {
        if($this->logs == NULL){
            $this->getAllUsersAttendanceLog();
            return $this->logs->filter_by_date(['start' => $from, 'end' => $to])->to_array();
        }else{
            return $this->logs->filter_by_date(['start' => $from, 'end' => $to])->to_array();
        }
    }

    function getDeviceInfo() {
        return $this->device_info;
    }

    function getDeviceName() {
        return $this->tad->get_device_name();
    }

    function getDeviceSerial() {
        return $this->tad->get_serial_number()->to_array();
    }

    function getDeviceIPAdd(){
        return $this->tad->get_ip();
    }
    
    function clearDeviceLog(){
        $this->tad->delete_data(['value'=>3]);
    }
    
    function clearAllTemplates(){
        $this->tad->delete_data(['value'=>2]);
    }
    
    function clearAllDeviceData(){
        $this->tad->delete_data(['value'=>1]);
    }
    
    function getDeviceStatus() {
        return $this->tad->is_alive();
    }
    
    function getDeviceOnlineStatus($ip) {
        return $this->tad->is_device_online($ip);
    }
    
    //saves attendance log to database
    function saveLogs($id) {
        $db = new Database();
        $today = date('Y-m-d H:i:s');
        $dformat = 'Y-m-d';
        $tformat = 'H:i:s';
        $device_id = $id;
        
        $log_date = date('Y-m-d');
        $device = $db->select("SELECT * FROM mx_device WHERE id = '" . $device_id . "'");
        
        $institution_id = $device[0]['opt_mx_institution_id'];
        
        $attendances = $this->filterAttendanceLog($log_date, $log_date);
    
        if (sizeof($attendances)) {
            if (count($attendances['Row'], COUNT_RECURSIVE) > 5) {
                $attendance_logs = $attendances['Row'];  
            }else{
                 $attendance_logs = $attendances;  
            }

            foreach ($attendance_logs as $attendance) {
              
                $dateTime = strtotime($attendance['DateTime']);
                $date = date($dformat, $dateTime);
                $time = date($tformat, $dateTime);
                $staff_id = $attendance['PIN'];
                $verified_id = $attendance['Verified'];
                $att_type_id = $attendance['Status'];
                   
                $stmt = $db->prepare("INSERT INTO mx_attendance("
                        . "txt_employee_no, "
                        . "dat_date, "
                        . "tim_time, "
                        . "int_mx_verified_id, "
                        . "int_mx_attendance_type_id, "
                        . "dat_date_fetched, "
                        . "txt_mx_institution_id, "
                        . "txt_mx_device_id ) "
                        . "VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bindValue(1, $staff_id, PDO::PARAM_STR);
                $stmt->bindValue(2, $date, PDO::PARAM_STR);
                $stmt->bindValue(3, $time, PDO::PARAM_STR);
                $stmt->bindValue(4, $verified_id, PDO::PARAM_INT);
                $stmt->bindValue(5, $att_type_id, PDO::PARAM_INT);
                $stmt->bindValue(6, $today, PDO::PARAM_STR);
                $stmt->bindValue(7, $institution_id, PDO::PARAM_STR);
                $stmt->bindValue(8, $device_id, PDO::PARAM_STR);

                $result = $stmt->execute();

                //echo json_encode($result);
            }
        }
    }

    //save templates to database
    function saveTemplate($template) {
        $db = new Database();
        foreach ($template as $row) {
            $id = $this->generateRandomString();
            $staff_id = $row['PIN'];
            $finger_id = $row['FingerID'];
            $size = $row['Size'];
            $valid = $row['Valid'];
            $ftemplate = $row['Template'];

            $stmt = $db->prepare("INSERT INTO mx_template("
                    . "id, "
                    . "opt_mx_finger_id, "
                    . "tar_template, "
                    . "txt_mx_employee_id, "
                    . "int_valid, "
                    . "int_size ) "
                    . "VALUES(?, ?, ?, ?, ?, ?)");
            
            $stmt->bindValue(1, $id, PDO::PARAM_STR);
            $stmt->bindValue(2, $finger_id, PDO::PARAM_STR);
            $stmt->bindValue(3, $ftemplate, PDO::PARAM_STR);
            $stmt->bindValue(4, $staff_id, PDO::PARAM_STR);
            $stmt->bindValue(5, $valid, PDO::PARAM_INT);
            $stmt->bindValue(6, $size, PDO::PARAM_INT);

            $result = $stmt->execute();
        }
    }


    //Functions 
    function generateRandomString($length = 6) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }
}

//$rahisi = new ZktBio('172.16.10.200');
//print_r($rahisi->getUserTemplate('421976'));
//$rahisi->saveTemplate($rahisi->getUserTemplate('421976'));

//$rahisi->deleteUserInfo('123456');
//$rahisi->setUserInfo(['id' => '421976', 'txt_name' => 'Halima Mohd', 'opt_mx_privilege_id' => 0, 'opt_mx_role_id' => 0]);
//print_r($rahisi->getAllUsersInfo());
