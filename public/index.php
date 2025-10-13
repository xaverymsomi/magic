<?php
$requiredPhpVersion = '8.2.0';

if (version_compare(PHP_VERSION, $requiredPhpVersion, '<')) {
	http_response_code(500); // Optional: Set appropriate HTTP status
	readfile(__DIR__ . '/php_version_error.html');
	exit;
}

// Continue with your application logic

require_once '../bootstrap/app.php';