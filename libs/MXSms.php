<?php

namespace Libs;

use Exception;
use PDO;
use FastHub;
use AptSMS;


/**
 * Description of MXSMS
 *
 * @author abdirahmanhassan
 */
class MXSms {

    private $settings;
    private $sent_result;
    private $user;
    private $center;
    private $reason;
    private $mobile;
    private $inspector;

    function __construct() {
        // include MX17_APP_ROOT . '/inc/sys_pref.php';
        $this->init();
    }

    function init() {
        $this->settings = $this->getConfig();
    }

    function sendTemplateSMS($reason, $mobile, $user = null, $center_id = null, $inspector = null, $labels = null, $values = null, $language = null) {
        $this->reason = $this->getReference($reason);
        $this->user = $user;
        $this->inspector = $inspector;
        $mobile_check = $this->validateMobileNumber($mobile);
        $this->mobile = $mobile_check['mobile'];
        $this->center = $center_id;

        if ($language == null) {
            $message = $this->getTemplate($reason);
        } else {
            $message = $this->getTemplateWithLanguage($reason, $language);
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
        $this->logSMS($total);

        return true;
    }

    private function sendSMS($sender_id, $sms_body, $mobile) {
        $msg = PHP_EOL . 'Sender: '  . $sender_id . PHP_EOL;
        $msg .= 'SMS: '  . $sms_body . PHP_EOL;
        $msg .= 'Mobile: '  . $mobile . PHP_EOL;
        $msg .= '***********************************************************************************************************************' . PHP_EOL;
        Log::logMsg($msg);

        print("");
        if (isset($this->settings['txt_provider']) && $this->settings['txt_provider'] == 'FastHub') {
            $sms = new FastHub($this->settings['txt_username'], $this->settings['password'], $sender_id, $this->settings['txt_host']);
            try {
                $sms->addMessage($sms_body, $this->mobile);
                $fasthubResult = $sms->send();
                $result = json_decode($fasthubResult, true);
            } catch (Exception $ex) {
                Log::sysErr('An error occurred');
            }
        } elseif (isset($this->settings['txt_username']) && $this->settings['txt_username'] == 'imart') {
            $sms = new iMartSMS($this->settings['txt_host'], $this->settings['password'], MX_SMS_CAMPAIGN, $sender_id, $sms_body, $this->mobile);
            $result = $sms->Submit();
        } else {
            $sms = new AptSMS($this->settings['txt_host'], $this->settings['txt_username'], $this->settings['password'], $sms_body, $sms_body, $mobile);
            $result = $sms->Submit();
        }

        return $result;
    }

    function sendApplicationsSMS($reason, $mobile, $labels = null, $values = null, $language = null): bool
    {
        $this->reason = $this->getReference($reason);
        $mobile_check = $this->validateMobileNumber($mobile);
        $this->mobile = $mobile_check['mobile'];

        if (!$mobile_check['status']) {
            //SMS NOT APPLICABLE
            return false;
        }

        if ($language == null) {
            $message = $this->getTemplate($reason);
        } else {
            $message = $this->getTemplateWithLanguage($reason, $language);
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

    private function getConfig() {
        $db = new Database();
        $sql = "SELECT * from mx_sms WHERE opt_mx_sms_enabled = 1";
        $result = $db->select($sql);
        unset($db);
        return $result[0];
    }

    private function logSMS($total) {
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
//            print_r($this->sent_result);
            $returned = explode(',', $this->sent_result);
            $status = $returned[0];
            $delivery_id = $returned[2];
        }

        $date = date('Y-m-d H:i:s');

        $db = new Database();
        $stmt = $db->prepare("INSERT INTO mx_sms_log (txt_mobile_no, txt_reference, 
            dat_date, txt_sms_status, int_delivery_id, opt_mx_center_id, opt_mx_user_id, int_part) 
           VALUES(?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bindValue(1, $this->mobile, PDO::PARAM_STR);
        $stmt->bindValue(2, $this->reason, PDO::PARAM_STR);
        $stmt->bindValue(3, $date, PDO::PARAM_STR);
        $stmt->bindValue(4, $status, PDO::PARAM_STR);
        $stmt->bindValue(5, $delivery_id, PDO::PARAM_INT);
        $stmt->bindValue(6, $this->center, PDO::PARAM_STR);
//        if (is_int($this->inspector)) {
//            $stmt->bindValue(7, $this->inspector, PDO::PARAM_STR);
//        } else {
//        $stmt->bindValue(7, null, PDO::PARAM_STR);
//        }
        $stmt->bindValue(7, $this->user, PDO::PARAM_STR);

        $stmt->bindValue(8, $total, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (Exception $e) {

            Log::sysErr($e->getMessage());
            echo $e->getTraceAsString();
        }
        return false;
    }

    private function getTemplate($reason_id) {
//        $query = "SELECT * FROM mx_sms_template WHERE opt_mx_sms_reason_id = $reason_id AND opt_mx_council_id = $council_id";
        $query = "SELECT * FROM mx_sms_template WHERE opt_mx_source_id = $reason_id";
        $db = new Database();
        $content = $db->select($query);
        unset($db);
        return $content[0];
    }

    private function getTemplateWithLanguage($reason_id, $language) {
        $query = "SELECT * FROM mx_sms_template WHERE opt_mx_source_id = $reason_id ";
        $db = new Database();
        $content = $db->select($query);
        unset($db);
        return $content[0];
    }

    private function getReference($source) {
//        $query = "SELECT * FROM mx_sms_reason WHERE id = $source";
        $query = "SELECT * FROM mx_source WHERE id = $source";
        $db = new Database();
        $content = $db->select($query);
        unset($db);
        if (sizeof($content)) {
            return $content[0]['txt_name'];
        }
    }

    function getSenderId($council_id) {
        $query = "SELECT * FROM mx_council WHERE id = $council_id";
        $db = new Database();
        $content = $db->select($query);
        unset($db);
        return $content[0]['txt_sms_sender'];
    }

    function validateMobileNumber($mobile): array
    {
        return MXPhoneNumber::validateTzPhoneNumber($mobile);
    }

}
