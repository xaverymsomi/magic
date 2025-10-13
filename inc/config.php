<?php

const DS = '/';
define("MX17_APP_ROOT", dirname($_SERVER['DOCUMENT_ROOT']) . "");
$dotenv = Dotenv\Dotenv::createImmutable(MX17_APP_ROOT, '.env');
$dotenv->load();

define('URL', $_ENV['URL']);
// define('APP_NAME', $_ENV['APP_NAME']);
define('PORTAL_URL', $_ENV['PORTAL_URL']);
define('API_URL', $_ENV['API_URL']);

define('APP_DIR', '');
define('APP_KEY', $_ENV['APP_KEY']);
// This is for database passwords only
define('PASS_SALT', $_ENV['PASS_SALT']);
//POS PASS
define('HASH_ALGO', $_ENV['HASH_ALGO']);

define('ATTACHMENT_URL', $_ENV['ATTACHMENT_URL']);

define('MAILGUN_API_KEY', $_ENV['MAILGUN_API_KEY']);
define('MAILGUN_API_HOSTNAME', $_ENV['MAILGUN_API_HOSTNAME']);
define('MAILGUN_DOMAIN', $_ENV['MAILGUN_DOMAIN']);
