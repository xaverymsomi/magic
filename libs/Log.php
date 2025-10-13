<?php

/**
 * Description of Log
 *
 * @author abdirahmanhassan
 */

namespace Libs;

class Log
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
        $file = $log_dir . date('Y-m') . '-mx_email.log';
        return self::saveLog($file, '[EMAIL-ERROR]: ' . $msg);
    }

    private static function saveLog($file, $msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/sys/';
        self::logMkdirIfNotExists($log_dir);
        $log = '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | ' . $msg . "\n";
        $syslog_file = $log_dir . date('Y-m') . '-mx_sys.log';

        if ($file == 'default') {
            file_put_contents($syslog_file, $log, FILE_APPEND | LOCK_EX);
            return 1;
        }

        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
        file_put_contents($syslog_file, $log, FILE_APPEND | LOCK_EX);

        return 1;
    }

    public static function sysLog($msg): int
    {
        return self::saveLog('default', $msg);
    }

    public static function customSysLog($tag, $msg): int
    {
        return self::saveLog('default', $tag . ' --- ' . json_encode($msg));
    }

    public static function sysErr($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/sys/';
        self::logMkdirIfNotExists($log_dir);

        return self::sysLog('[ERROR]: ' . $msg);
    }

    public static function savePlainLog($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/sys/';
        self::logMkdirIfNotExists($log_dir);
        $log = $msg . "\n";
        $syslog_file = $log_dir . date('Y-m') . '-mx_sys.log';
        file_put_contents($syslog_file, $log, FILE_APPEND | LOCK_EX);

        return 1;
    }

    public static function dataLog($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/data/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m') . '-mx_data.log';

        return self::saveLog($file, 'DATA-LOG: [' . json_encode($msg) . ']');
    }

    public static function smsErr($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/sms/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m') . '-mx_sms.log';

        return self::saveLog($file, '[SMS-ERROR]: ' . $msg);
    }

    public static function dbErr($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/db/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m') . '-mx_db.log';

        return self::saveLog($file, $msg);
    }

    public static function logMsg($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/sms/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m') . '-mx_msg.log';

        $log = '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | ' . $msg . "\n";

        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
        return 1;
    }

    public static function logEmail($msg): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/email/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m') . '-mx_email.log';

        $log = '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | ' . $msg . "\n";

        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
        return 1;
    }

    public static function auditor($tag, $data): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $log_dir = MX17_APP_ROOT . '/logs/audit_trail/';
        self::logMkdirIfNotExists($log_dir);
        $file = $log_dir . date('Y-m') . "_system_auditor.log";

        $msg = $tag . ':' . json_encode($data);
        return self::saveLog($file, $msg);
    }

    public static function queryLog($query_type, $query, $params = []): int
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');

        $log_dir = MX17_APP_ROOT . '/logs/query/';
        self::logMkdirIfNotExists($log_dir);
        $log = '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | [' . strtoupper($query_type) . '] | ' . json_encode($query) . "\n";

        $parameters = !empty($params) ? json_encode($params) : 'NONE';
        $last_insert_id = !empty($params['id']) ? json_encode($params['id']) : 'NONE';

        $log .= '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | [PARAMETERS] | ' . $parameters . "\n";
        $log .= '[' . date('Y-m-d H:i:s') . '] | ' . self::$request_number . ' | [LAST_INSERT_ID] | ' . $last_insert_id . "\n";
        $log .= '******************************************************************************************************************************************' . "\n\n";

        $query_log_file = $log_dir . date('Y-m') . '-mx_query.log';

        file_put_contents($query_log_file, $log, FILE_APPEND | LOCK_EX);

        return 1;
    }

    public static function custom_log($dir, $file, $msg): int
    {
        $log_dir = MX17_APP_ROOT . '/logs/custom/' . $dir . '/';
        self::logMkdirIfNotExists($log_dir);
        $log = '[' . date('Y-m-d h:i:s') . '] - ' . $msg . "\n";
        $log_file = $log_dir . date('Y-m') . $file . '.log';

        file_put_contents($log_file, $log, FILE_APPEND | LOCK_EX);

        return 1;
    }
}
