<?php

namespace Libs;

/**
 * Description of MXSMS
 *
 * @author abdirahmanhassan
 */

use Core\database\DB;
use Core\database\Connection as Database;
use Exception;

class SmsSender
{

    private $settings;
    private $sent_result;
    private $user;
    private $center;
    private $reason;
    private $mobile;
    private $inspector;

    function __construct()
    {
        $this->init();
    }

    function init()
    {
        $this->settings = $this->getConfig();
    }

    private function getConfig()
    {
        $sql = "SELECT * from mx_sms WHERE opt_mx_sms_enabled = 1";
        $result = (new DB())::select($sql);
        return $result[0];
    }

    function sendTemplateSMS($reason, $mobile, $labels = null, $values = null, $language = 1)
    {

        $this->mobile = $this->validateMobileNumber($mobile);

        $message = $this->getTemplate($reason, $language);
        if (!$message) {
            return ['message' => "No sms Template for reason Id " . $reason];
        }

        $sender_id = MX_SMS_SENDER_ID;

        if ($values != null && $labels != null && count($values) > 0 && count($labels) > 0) {
            $sms_body = str_replace($labels, $values, $message['tar_sms_content']);
        } else {
            $sms_body = $message['tar_sms_content'];
        }
        $result = $this->sendSMS($sender_id, $sms_body, $mobile);

        //checking how many parts sms has
        $chunks = explode("||||", wordwrap($sms_body, 155, "||||"));
        $total = count($chunks);
        $this->sent_result = $result;
        return $this->logSMS($total);
    }


    function validateMobileNumber($mobile)
    {
        return PhoneNumber::validateTzPhoneNumber($mobile);
    }

    private function getTemplate($reason_id, $language)
    {
        //        $query = "SELECT * FROM mx_sms_template WHERE opt_mx_sms_reason_id = $reason_id AND opt_mx_council_id = $council_id";
        $query = "SELECT * FROM mx_sms_template WHERE opt_mx_sms_language_id =:lang AND opt_mx_source_id IN(SELECT id FROM mx_source WHERE id=:source)";
        // $db = new Database();
        $content = DB::select($query, [':source' => $reason_id, ':lang' => $language]);
//        echo json_encode($content);exit;
        // unset($db);
        if (!$content) {
            return [];
        }
        return $content[0];
    }

    private function sendSMS($sender_id, $sms_body, $mobile)
    {
        $msg = PHP_EOL . 'Sender: '  . $sender_id . PHP_EOL;
        $msg .= 'SMS: '  . $sms_body . PHP_EOL;
        $msg .= 'Mobile: '  . $mobile . PHP_EOL;
        $msg .= '***********************************************************************************************************************' . PHP_EOL;
        ApiLog::logMsg($msg);

        $result = null;
        if (isset($this->settings['txt_provider']) && $this->settings['txt_provider'] == 'FastHub') {
            $sms = new FastHub($this->settings['txt_username'], $this->settings['password'], $sender_id, $this->settings['txt_host']);
            try {
                $sms->addMessage($sms_body, $this->mobile);
                $fasthubResult = $sms->send();
                $result = json_decode($fasthubResult, true);
            } catch (Exception $ex) {
                ApiLog::sysLog($ex->getMessage());
            }
        } elseif (isset($this->settings['txt_username']) && $this->settings['txt_username'] == 'imart') {
            $sms = new iMartSmsSender($this->settings['txt_host'], $this->settings['password'], MX_SMS_CAMPAIGN, $sender_id, $sms_body, $this->mobile);
            $result = $sms->Submit();
        } else {
            $sms = new AptSMS($this->settings['txt_host'], $this->settings['txt_username'], $this->settings['password'], $sms_body, $sms_body, $mobile);
            $result = $sms->Submit();
        }
        return $result;
    }

    private function logSMS($total): bool
    {
        if (isset($this->settings['txt_provider']) && $this->settings['txt_provider'] == 'FastHub') {
            if ($this->sent_result['isSuccessful']) {
                $status = 'OK';
            } else {
                $status = 'ERR';
            }
            $delivery_id = $this->sent_result['reference_id'];
        } elseif ($this->settings['txt_provider'] == 'iMart') {
            $returned = explode('/', $this->sent_result);
            if (array_key_exists(1, $returned)) {
                $status = 'OK';
                $delivery_id = $returned[1];
            } else {
                $status = 'ERR';
                $delivery_id = NULL;
            }
        } else {
            $returned = explode(',', $this->sent_result);
            $status = $returned[0];
            $delivery_id = $returned[2];
        }

        $date = date('Y-m-d H:i:s');

        $db = new DB();
        $stmt = $db->prepare("INSERT INTO mx_sms_log (txt_mobile_no, txt_reference, 
            dat_date, txt_sms_status, int_delivery_id, opt_mx_center_id, opt_mx_user_id, int_part) 
           VALUES(:mobile,:reason,:date,:status,:delivery_id,:center,:user,:total)");

        $data = [
            ':mobile' => filter_var($this->mobile, FILTER_SANITIZE_SPECIAL_CHARS),
            ':reason' => filter_var($this->reason, FILTER_SANITIZE_SPECIAL_CHARS),
            ':date' => filter_var($date, FILTER_SANITIZE_SPECIAL_CHARS),
            ':status' => filter_var($status, FILTER_SANITIZE_SPECIAL_CHARS),
            ':delivery_id' => filter_var($delivery_id, FILTER_SANITIZE_SPECIAL_CHARS),
            ':center' => filter_var($this->center, FILTER_SANITIZE_SPECIAL_CHARS),
            ':user' => filter_var($this->user, FILTER_SANITIZE_SPECIAL_CHARS),
            ':total' => filter_var($total, FILTER_SANITIZE_SPECIAL_CHARS),
        ];

        try {
            return $stmt->execute($data);
        } catch (Exception $e) {
            $log = new ApiLog();
            $log->sysErr($e->getMessage());
            // echo $e->getMessage();
            return false;
        }
    }
}
