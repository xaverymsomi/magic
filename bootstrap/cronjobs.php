<?php
require_once '../vendor/autoload.php';

include dirname(__DIR__) . '/inc/config.php';
include dirname(__DIR__) . '/inc/sys_pref.php';
try {
    require dirname(__DIR__) .'/cronjobs/notifier.php';
}catch (Exception $e){
    echo $e->getMessage();
}
