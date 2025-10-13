<?php

//require_once '../vendor/autoload.php';

require_once __DIR__ . '/../vendor/autoload.php';

use Libs\Log;
use Libs\Mabrex;

ob_start();
session_start();

include dirname(__DIR__) . '/inc/config.php';
include dirname(__DIR__) . '/inc/sys_pref.php';
include dirname(__DIR__) . '/inc/helpers.php';

require dirname(__DIR__) . '/utility/ErrorHandler.php';
date_default_timezone_set('Africa/Dar_es_Salaam');
set_error_handler("mxPublicError");
register_shutdown_function("mxFatalErrorHandler");

Log::$request_number = hrtime(true);
(new Mabrex())->init();