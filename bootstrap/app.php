<?php
// If run from CLI (php prism ...) skip web routing
if (php_sapi_name() === 'cli') {
	require_once __DIR__ . '/console.php'; // use a CLI-specific bootstrap
	return;
}

$url = $_SERVER['REQUEST_URI'] ?? $_SERVER['QUERY_STRING'];

$array = explode('/', $url);
$url_array = [];

for ($i = 0; $i <= count($array); $i++) {
    if (empty($array[$i])) {
        unset($array[$i]);
    } else {
        $url_array[] = $array[$i];
    }
}

if (!empty($url_array)) {
    if (strtolower($url_array[0]) == 'api' || strtolower($url_array[0]) == 'vcpayment') {
        $namespace = 'api';
    } elseif (strtolower($url_array[0]) == 'cronjobs') {
        $namespace = 'cronjobs';
    } else {
        $namespace = 'web';
    }
} else {
    $namespace = 'web';
}

require_once $namespace . '.php';