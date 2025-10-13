<?php
ob_start();
//header('Content-Type: application/json');
date_default_timezone_set('Africa/Dar_es_Salaam');

use Core\Router;
use Core\Request;
use Libs\ApiLog;
use Libs\ApiLib;

require dirname(__DIR__) . "/vendor/autoload.php";
require dirname(__DIR__) . "/config.php";
require dirname(__DIR__) . "/config/sys_pref.php";

function apiMkdirIfNotExists($dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function api_user_log($user_details): string
{
    $id = !empty($user_details) ? $user_details->cred_id : 0;

    if ($id == 0) {
        return 'GSTUSR';
    }

    $user = (new \Core\Controller())->getRecordByFieldName('mx_login_credential', 'txt_row_value', filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS));
    return $user['txt_username'];
}

ApiLog::$request_number = hrtime(true);
ApiLog::savePlainLog("*************************************************************************************************************************************************\n");
ApiLog::sysLog('API-REQUEST-STARTED');

try {
    Router::load(API_PUBLIC_PATH . '/routes.php')->direct(Request::uri(), Request::method());
} catch (Throwable $e) {
    ApiLog::sysLog("FATAL-ERROR: ['message' => " . $e->getMessage() . ", 'trace' => " . json_encode($e->getTrace()) . "]");
    ApiLib::handleResponse('INTERNAL SERVER ERROR', [], 500);
}

ApiLog::sysLog('API-REQUEST-ENDED');
