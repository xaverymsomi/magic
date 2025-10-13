<?php
// www/routing.php
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|woff2|ttf|woff)(\?[[A-z0-9])?/', $_SERVER["REQUEST_URI"])) {
    return false;
} else {
    $_GET['url'] = $_SERVER["REQUEST_URI"];
    include __DIR__ . '/index.php';
}