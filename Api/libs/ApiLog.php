<?php

namespace Libs;

/**
 * Description of Log
 *
 * @author abdirahmanhassan
 */
class ApiLog
{
    public static $request_number;

    private static function logMkdirIfNotExists($dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public static function emailErr($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/email/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m-d H') . '-mx_email.log';
        return self::saveLog($file, '[EMAIL-ERROR]: ' . $msg);
    }

    private static function saveLog($file, $msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/sys/';
        self::logMkdirIfNotExists($log_dir);
        $log = '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | ' . $msg . "\n";
        $syslog_file = $log_dir . date('Y-m-d H') . '-mx_sys.log';

        if ($file == 'default') {
            file_put_contents($syslog_file, $log, FILE_APPEND | LOCK_EX);
            return 1;
        }

        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
        return 1;
    }

    static function sysLog($msg): int
    {
        return self::saveLog('default', $msg);
    }

    public static function customSysLog($tag, $msg): int
    {
        return self::saveLog('default', $tag . ' --- ' .json_encode($msg));
    }

    public static function sysErr($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/sys/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m-d H') . '-mx_error.log';
        return self::saveLog($file, $msg);
    }

    public static function savePlainLog($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/sys/';
        self::logMkdirIfNotExists($log_dir);
        $log = $msg . "\n";

        $syslog_file = $log_dir . date('Y-m-d H') . '-mx_sys.log';
        file_put_contents($syslog_file, $log, FILE_APPEND | LOCK_EX);
        return 1;
    }

    public static function smsErr($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/sms/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m-d H') . '-sms_error.log';
        return self::saveLog($file, $msg);
    }

    public static function dataLog($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/data/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m-d H') . '-mx_data.log';
        return self::saveLog($file, 'DATA-LOG: [' . json_encode($msg) . ']');
    }

    public static function dbErr($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/db/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m-d H') . '-mx_db.log';
        return self::saveLog($file, $msg);
    }

    public static function logMsg($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/sms/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m-d H') . '-mx_msg.log';

        $log = '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | ' . $msg . "\n";

        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
        return 1;
    }

    public static function auditor($data, $tag = 'AUDIT'): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/audit_trail/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m-d H') . "_system_auditor.log";

        $msg = $tag . ' : ' . json_encode($data);
        self::sysLog('[' . $msg . ']');
        return self::saveLog($file, $msg);
    }

    public static function queryLog($query_type, $query, $params = []): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');

        $log_dir = API_LOG_PATH . '/query/';
        self::logMkdirIfNotExists($log_dir);
        $log = '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | [' . strtoupper($query_type) . '] | ' . json_encode($query) . "\n";

        $parameters = !empty($params) ? json_encode($params) : 'NONE';
        $last_insert_id = !empty($params['id']) ? json_encode($params['id']) : 'NONE';

        $log .= '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | [PARAMETERS] | ' . $parameters . "\n";
        $log .= '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | [LAST_INSERT_ID] | ' . $last_insert_id . "\n";
        $log .= '******************************************************************************************************************************************' . "\n\n";

        $query_log_file = $log_dir . date('Y-m-d H') . '-mx_query.log';

        file_put_contents($query_log_file, $log, FILE_APPEND | LOCK_EX);
        return 1;
    }

    public static function dbAccessMsg($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/db/';
        self::logMkdirIfNotExists($log_dir);

        $file = $log_dir . date('Y-m-d H') . '_db_access_msg.log';
        return self::saveLog($file, $msg);
    }

    public static function dbConnection($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/db/';
        self::logMkdirIfNotExists($log_dir);

        $file = $log_dir . date('Y-m-d H') . '_db_connection.log';
        return self::saveLog($file, $msg);
    }

    public static function dbTraceLog($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/db/';
        self::logMkdirIfNotExists($log_dir);

        $file = $log_dir . date('Y-m-d H') . '_db_trace.log';
        return self::saveLog($file, $msg);
    }

    public static function debug($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = API_LOG_PATH . '/db/';
        self::logMkdirIfNotExists($log_dir);

        $file = $log_dir . date('Y-m-d H') . '_debug.log';
        return self::saveLog($file, $msg);
    }

    public static function custom_log($dir, $file, $msg): int
    {
        $log_dir = API_LOG_PATH . '/custom/' . $dir . '/';
        self::logMkdirIfNotExists($log_dir);
        $log = '[' . date('Y-m-d H:i:s') . '] - ' . $msg . "\n";
        $log_file = $log_dir . date('Y-m-d H') . $file . '.log';

        file_put_contents($log_file, $log, FILE_APPEND | LOCK_EX);
        return 1;
    }

    public static function custom_log_payment($dir, $file, $msg, $end = false): int
    {
        $log_dir = API_LOG_PATH . '/' . $dir . '/';

        self::logMkdirIfNotExists($log_dir);

        // Convert $msg to string if it's an array or object
        if (is_array($msg) || is_object($msg)) {
            $msg = json_encode($msg);
        }

        $log = '[' . date('Y-m-d h:i:s') . '] - ' . ApiLog::$request_number . ' | ' . $msg . "\n";
        if ($end) {
            $log .= "*************************************************************************************************************************************************\n";
        }

        $log_file = $log_dir . date('Y-m-d H') . '_' . $file . '.log';

        file_put_contents($log_file, $log, FILE_APPEND | LOCK_EX);

        return 1;
    }
}
