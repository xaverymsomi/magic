<?php

namespace Core;

use Core\database\QueryBuilder;
use Libs\ApiLib;
use Libs\ApiLog;
use Libs\Log;
use Libs\MXMail;
use Libs\MXPhoneNumber;
use Libs\SmsSender;

class Controller extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function generateRandomString($length = 8): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public static function hash($algo, $data, $salt): string
    {
        $context = hash_init($algo, HASH_HMAC, $salt);
        hash_update($context, $data);

        return hash_final($context);
    }



    function getRecord($id, $table)
    {
        $sql = "SELECT * FROM " . $table . " WHERE id = :id";
        $result = $this->select($sql, array(':id' => $id));
        if (sizeof($result)) {
            if (array_key_exists('rowguid', $result[0])) {
                unset($result[0]['rowguid']);
            }
            return $result[0];
        }
        return [];
    }

    public function getRecordIdByRowValue($table, $value): int
    {
        $result = $this->select("SELECT id FROM " . $table . " WHERE txt_row_value = :value", [':value' => $value]);
        if (count($result)) {
            return $result[0]['id'];
        }
        return -1;
    }

    function getRecordByRowValue($table, $id)
    {
        $sql = "SELECT * FROM " . $table . " WHERE txt_row_value = :id";
        $result = $this->select($sql, array(':id' => $id));
        if (sizeof($result)) {
            if (array_key_exists('rowguid', $result[0])) {
                unset($result[0]['rowguid']);
            }
            return $result[0];
        }
        return [];
    }

    public function verifyEmail(string $table, string $email, string $column = 'txt_email'): bool
    {
        $sanitized_email = filter_var(strtolower($email), FILTER_SANITIZE_EMAIL);
        $sql = $this->select('SELECT * FROM ' . $table . ' WHERE ' . $column . ' = :email', [':email' => $sanitized_email]);
        if (count($sql) > 0) {
            ApiLog::sysLog(json_encode(['class' => debug_backtrace()[1]['class'], 'function' => debug_backtrace()[1]['function'], 'message' => 'Email already exists', 'submitted_email' => $email, 'sanitized_email' => $sanitized_email]));
            return true;
        }
        return false;
    }

    public function verifyMobile(string $table, string $mobile, string $column = 'txt_phone'): bool
    {
        $sanitized_mobile = filter_var($mobile, FILTER_SANITIZE_SPECIAL_CHARS);
        $sql = $this->select('SELECT * FROM ' . $table . ' WHERE ' . $column . ' = :mobile', [':mobile' => $sanitized_mobile]);
        if (count($sql) > 0) {
            ApiLog::sysLog(json_encode(['class' => debug_backtrace()[1]['class'], 'function' => debug_backtrace()[1]['function'], 'message' => 'Phone number already exists', 'submitted_mobile' => $mobile, 'sanitized_mobile' => $sanitized_mobile]));
            return true;
        }
        return false;
    }

    public function validateMobileNumber($mobile): bool
    {
        $sanitized_mobile = filter_var($mobile, FILTER_SANITIZE_SPECIAL_CHARS);

        $check_mobile_validity = MXPhoneNumber::validateTzPhoneNumber($sanitized_mobile);
        if (!$check_mobile_validity['status']) {
            ApiLog::sysLog(json_encode(['class' => debug_backtrace()[1]['class'], 'function' => debug_backtrace()[1]['function'], 'message' => 'Invalid phone number', 'submitted_mobile' => $mobile, 'sanitized_mobile' => $sanitized_mobile, 'validated_mobile' => $check_mobile_validity]));
            $this->pdo->rollBack();
            return false;
        }
        return true;
    }

    public function saveLoginCredentials($user_id, $username, $domain = 'mx_user'): array
    {
        // prepare login credentials data
        $password = self::generateRandomNo(4);

        $login_credential = [
            'user_id' => $user_id,
            'txt_username' => $username,
            'password' => filter_var(ApiLib::createHashedPassword(HASH_ALGO, $password, PASS_SALT), FILTER_SANITIZE_SPECIAL_CHARS),
            'opt_mx_status_id' => filter_var(ACTIVE, FILTER_SANITIZE_NUMBER_INT),
            'txt_domain' => $domain,
            'txt_row_value' => self::getGUID('mx_login_credential')
        ];

        ApiLog::sysLog('LOGIN-CREDENTIAL-DATA: [' . json_encode($login_credential));

        $login_credential_save = $this->save('mx_login_credential', $login_credential, 'LOGIN_CREDENTIAL_MODEL');
        if (!$login_credential_save) {
            return ['status' => false, 'code' => 100, 'message' => 'Failed to save login credentials'];
        }

        $login_credential_id = parent::last_id();

        ApiLog::sysLog('LOGIN-CREDENTIALS-SAVED: [' . json_encode($login_credential_save) . ']');
        return ['status' => true, 'data' => ['password' => $password, 'login_credential_id' => $login_credential_id]];
    }

    public static function generateRandomNo($length = 6): string
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

    public function getGUID($table): string
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
        if (!empty($table)) {
            $existing_row_value = $this->getRecordByFieldName($table, 'txt_row_value', $new_guid);

            // Recursively generate a new GUID if a conflict is found
            if (!empty($existing_row_value)) {
                return $this->getGUID($table);
            }
        }

        // Return the newly generated GUID
        return $new_guid;
    }

    public function getRecordByFieldName($table, $field_name, $value, $single_record = false)
    {
        $selector = $single_record ? 'TOP 1 *' : '*';
        $sql = 'SELECT ' . $selector . ' FROM ' . $table . ' WHERE ' . $field_name . ' = :value';

        $result = $this->select($sql, [':value' => $value]);
        if (sizeof($result)) {
            return $result[0];
        }
        return [];
    }

    public function saveNotificationData($data): bool
    {
        $source_id = $data['source_id'] ?? null;
        $labels = $data['labels'] ?? null;
        $values = $data['values'] ?? null;
        $mobile = $data['mobile'] ?? null;
        $email = $data['email'] ?? null;
        $login_credential_id = $data['login_credential_id'] ?? null;
        $notification_title = $data['notification_title'] ?? null;
        $notification_message = $data['notification_message'] ?? null;
        $notification_type = $data['notification_type'] ?? null;

        if (!empty($mobile) && !empty($source_id) && !empty($labels) && !empty($values)) {
            $sms_queue_data = [
                'txt_recipient' => filter_var($mobile, FILTER_SANITIZE_SPECIAL_CHARS),
                'opt_mx_source_id' => $source_id,
                'txt_type' => 'SMS',
                'txt_labels' => json_encode($labels),
                'txt_values' => json_encode($values)
            ];

            ApiLog::sysLog('SMS-QUEUE-DATA: ' . json_encode($sms_queue_data));

            $sms_queue = $this->save('mx_queue', $sms_queue_data, 'QUEUE-DATA');
            if (!$sms_queue) {
                ApiLog::sysLog(json_encode(['class' => debug_backtrace()[1]['class'], 'function' => debug_backtrace()[1]['function'], 'message' => 'Failed to save SMS queue']));
            }

            ApiLog::sysLog('SMS-QUEUE: ' . json_encode($sms_queue));

            $sms = new SmsSender();
            $sms->sendTemplateSMS($sms_queue_data['opt_mx_source_id'], $mobile, $labels, $values, 1);
        }

        if (!empty($email) && !empty($source_id) && !empty($labels) && !empty($values)) {
            $email_queue_data = [
                'txt_recipient' => filter_var($email, FILTER_SANITIZE_SPECIAL_CHARS),
                'opt_mx_source_id' => $source_id,
                'txt_type' => 'EMAIL',
                'txt_labels' => json_encode($labels),
                'txt_values' => json_encode($values)
            ];

            ApiLog::sysLog('EMAIL-QUEUE-DATA: ' . json_encode($email_queue_data));

            $email_queue = $this->save('mx_queue', $email_queue_data, 'QUEUE-DATA');
            if (!$email_queue) {
                ApiLog::sysLog(json_encode(['class' => debug_backtrace()[1]['class'], 'function' => debug_backtrace()[1]['function'], 'message' => 'Failed to save email queue']));
            }

            ApiLog::sysLog('EMAIL-QUEUE: ' . json_encode($email_queue));

//            $mail = new MXMail();
//            $mail->sendEmail($email_queue_data['opt_mx_source_id'], $email, null, $labels, $values);
        }

        if (!empty($login_credential_id) && !empty($notification_title) && !empty($notification_message) && !empty($notification_type)) {
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
                'txt_row_value' => self::getGUID('mx_notification')
            ];

            $notification_queue = $this->save('mx_notification', $notification_data, 'NOTIFICATION-DATA');
            if (!$notification_queue) {
                ApiLog::sysLog(json_encode(['class' => debug_backtrace()[1]['class'], 'function' => debug_backtrace()[1]['function'], 'message' => 'Failed to save notification']));
            }
        }
        return true;
    }

    protected function convertToArray($foreign_employees): array
    {
        ApiLog::sysLog('RAW-DATA: [' . $foreign_employees . ']');

        // Step 2: Replace single quotes with double quotes, and keys without quotes need double quotes
        $corrected_json = preg_replace('/([a-zA-Z0-9_]+)(?=:)/', '"$1"', $foreign_employees); // Add double quotes around keys
        $corrected_json = str_replace("'", "\"", $corrected_json); // Add double quotes to string values if they are single-quoted
        $corrected_json = preg_replace('/:\s*([a-zA-Z0-9_]+)/', ': "$1"', $corrected_json); // Add double quotes to values that are not numbers or booleans

        ApiLog::sysLog('CORRECTED-JSON: [' . $corrected_json . ']');

        // Decode HTML entities
        $decoded_string = html_entity_decode($corrected_json, ENT_QUOTES);

        // Replace `&#10;` (newlines) and ensure the string is valid JSON
        $decoded_string = str_replace('&#10;', '', $decoded_string);  // Remove newlines if necessary
        $decoded_string = str_replace('&#34;', '"', $decoded_string); // Ensure double quotes around keys and values

        ApiLog::sysLog('DECODED-JSON: [' . $corrected_json . ']');

        $corrected_string_2 = preg_replace('/([a-zA-Z0-9_]+):\s*([a-zA-Z0-9_]+)/', '"$1": "$2"', $decoded_string);

        ApiLog::sysLog('2ND-CORRECTION: [' . $corrected_string_2 . ']');

        // Decode the string into a PHP array
        $array = json_decode($corrected_string_2, true);
        return (array)$array;
    }

}