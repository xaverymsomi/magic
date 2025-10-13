<?php
//require_once '../vendor/autoload.php';
//
//include '../inc/config.php';
//include '../inc/sys_pref.php';
//include '../inc/helpers.php';
//$DB = new \Libs\Database();
use Libs\Database;
use Libs\MXSms;
use Libs\MXMailGun;
try {

    $DB = new Database();
    $result = $DB->select("SELECT * FROM mx_queue WHERE txt_type = 'SMS'");
    logData('result', $result);
    foreach ($result as $notification) {

        if ($notification['txt_type'] == 'SMS') {
            $sms = new MXSms();
            $reason = $notification['opt_mx_source_id'];
            $mobile = $notification['txt_recipient'];
            $labels = json_decode($notification['txt_labels'], true);
            $values = json_decode($notification['txt_values'], true);
            $sms->sendApplicationsSMS($reason, $mobile, $labels, $values);
            logData('SMS', [$reason, $mobile, $labels, $values]);

        }
        else {
            $mail = new MXMailGun();
            $source = $notification['opt_mx_source_id'];
            $email = $notification['txt_recipient'];
            $labels = json_decode($notification['txt_labels'], true);
            $values = json_decode($notification['txt_values'], true);

            $mail->sendEmail($source, $email, null, $labels, $values);
            logData('EMAIL', [$source, $email, null, $labels, $values]);
        }

        $DB->update('mx_queue', ['int_is_processed' => 1], $notification['id']);

    }

}catch (Exception $e){
    echo $e->getMessage();
}

function logData($tag, $data) {
    date_default_timezone_set('Africa/Dar_es_Salaam');
    $file = MX17_APP_ROOT.'/logs/cronjobs_logs/'.date('Y-m-d') . "_projects_request.txt";
    $log_date = date('Y-m-d H:i:s');
    $log = "[$log_date] - $tag:". json_encode($data)." \n\n";
    file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
}

