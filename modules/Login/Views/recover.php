<?php
include '../inc/config.php';
$classesDir = array(
    MX17_APP_ROOT . '/models/',
    MX17_APP_ROOT . '/libs/'
);

spl_autoload_register(function ($class_name) {
    global $classesDir;
    foreach ($classesDir as $directory) {

        if (file_exists($directory . $class_name . '.php')) {
            require_once ($directory . $class_name . '.php');
            return;
        }
    }
});
$init = new Init();

$e = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

$init->recover($e);