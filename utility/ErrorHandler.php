<?php

use Libs\Log;
use Modules\Error\Error;

function mxPublicError($errno, $errstr, $errfl, $errline) {
    Log::sysLog('CODE-ERROR:' . json_encode(debug_backtrace()));
    $request_number = Log::$request_number;

    $result = [
        'code' => 500, 'status' => false,
        'message' => 'An error occurred. Please contact Administrator with Request ID "' . $request_number . '"'
    ];

    if ($_ENV['ENV'] == 'dev') {
        $result['trace'] = debug_backtrace();
        $result['details'] =  [$errno, $errstr, $errfl, $errline];
    }
    echo json_encode($result);
    die();
}

function mxFatalErrorHandler() {
    $error = error_get_last();

    if($error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        Log::sysLog('FATAL-ERROR:' . json_encode([ $errno, $errstr, $errfile, $errline]));

        $request_number = Log::$request_number;

        $result = [
            'code' => 500, 'status' => false,
            'message' => 'An error occurred. Please contact Administrator with Request ID "' . $request_number . '"'
        ];

        if ($_ENV['ENV'] == 'dev') {
            $result['trace'] = $error;
        }
        echo json_encode($result);
    }
}
