<?php

use Libs\Database;
use Libs\Log;

function mkdirIfNotExists($dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function user_log(): string
{
    return getSession();
}

function getSession(): string
{
    $id = isset($_SESSION['id']) && !empty($_SESSION['id']) ? $_SESSION['id'] : 0;

    if ($id == 0) {
        return 'GSTUSR';
    }

    $user = (new \Libs\Model())->getRecordByFieldName('mx_login_credential', 'id', filter_var($id, FILTER_SANITIZE_NUMBER_INT));

    return $user['txt_username'];
}

function pluck($key, $value, $array)
{
    $data = [];
    if (count($array) > 0) {
        foreach ($array as $item) {
            $data[$item[$key]] = $item[$value];
        }
    }
    return $data;
}

/*
 * @var $aliasesWithColumns
 * Expects an array of aliases with columns present in the reduced array.
 * **/
function reduceArray(array $aliasesWithColumns, array $reducedArray)
{
    $data = [];
    foreach ($reducedArray as $array) {
        $item = [];
        foreach ($aliasesWithColumns as $alias => $column) {
            $item[$alias] = $array[$column];
        }
        $data[] = $item;
    }
    return $data;
}

// A helper to enable easy check if data returned is empty or not.
function selector(Database $db, string $sql, array $params = [])
{
    $result = $db->select($sql, $params);
    if (sizeof($result)) {
        return $result;
    }

    return [];
}

function lastInsertId(Database $db, $data = [], $table = null)
{
    if ($data == []) {
        return $db->lastInsertId();
    }

    $where = 'WHERE ';
    $params = [];
    $i = 0;
    foreach ($data as $item => $value) {
        if (!$value) {
            $where .= " $item IS NULL";
        } else {
            $where .= " $item = :$item ";
        }
        if ($i < (count($data) - 1)) {
            $where .= " AND ";
        }
        $i++;
    }
    foreach ($data as $item => $value) {
        if ($value) {
            $params[$item] = $value;
        }
    }
    $table = filter_var($table, FILTER_SANITIZE_SPECIAL_CHARS);

    $sql = "SELECT id FROM $table $where";
    $result = $db->select($sql, $params);

    if ($result) {
        return $result[0]['id'];
    } else {
        return 0;
    }
}

function searcher(string $search_key, $searched_value, array $array)
{
    foreach ($array as $key => $val) {
        if ($val[$search_key] == $searched_value) {
            return $val;
        }
    }
    return [];
}

function dd($msg)
{
    echo json_encode($msg);
    exit();
}

function response($msg, $background = false)
{
    $message = json_encode($msg);
    Log::sysLog($message);
    print $message;
    if (!$background) {
        exit();
    }
    // Start output buffering if it's not already started.
    if (ob_get_level() === 0) {
        ob_start();
    }
    // Get the size of the output buffer.
    $size = ob_get_length();
    // If there is an active output buffer and its size is greater than 0, flush the output.
    if ($size > 0) {
        // Disable compression (in case content length is compressed).
        header("Content-Encoding: none");
        // Set the content length of the response.
        header("Content-Length: {$size}");
        header("Connection: close");
        // Flush all output.
        ob_end_flush();
        flush();
    } else {
        // No buffer to flush, clean any output buffering if started accidentally.
        ob_end_clean();
    }
    // Check if PHP is running under FastCGI and finish the request if possible.
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request(); // Required for PHP-FPM (PHP > 5.3.3)
    }
    // Close current session (if it exists).
    if (session_id()) {
        session_write_close();
    }
}

function getClassName($class)
{
    $path = explode('\\', $class);
    return array_pop($path);
}

function getLabels($table, $key, $value)
{
    $array = (new Database())->select("SELECT * FROM $table");
    $results = [];
    foreach ($array as $items) {
        $results[$items[$key]] = $items[$value];
    }
    return $results;
}

function logData($tag, $data)
{
    date_default_timezone_set('Africa/Dar_es_Salaam');
    $file = MX17_APP_ROOT . "/" . date('Y-m') . '_syslog.txt';
    $log = date('d-m-Y H:i:s') . ' - ' . "$tag: " . json_encode($data) . " \n\n";
    file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
}

function trans($key)
{
    $language = $_SESSION['lang'];
    $lang_file = MX17_APP_ROOT . "/locale/lang.$language.php";
    if (file_exists($lang_file)) {
        $translations = require $lang_file;
        if (array_key_exists($key, $translations))
            return $translations[$key];
    }

    return $key;
}

function sendRequest($url, $type = "GET", $data = [], $headers = [])
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $type,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function kill() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    if (!headers_sent()) {
        header('HTTP/2 301 Moved Permanently');
        header('Location: ' . '/Login');
        exit;
    }
}