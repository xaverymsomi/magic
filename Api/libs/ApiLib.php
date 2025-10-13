<?php

/**
 * Description of MNOPaymentLib
 *
 * @author abdirahmanhassan
 */

namespace Libs;

date_default_timezone_set('Africa/Dar_es_Salaam');

use Core\database\DB;
use Core\Request;
use Exception;

class ApiLib
{
    public static function fileValidator($posted_data, $required_params): array
    {
        $data = [];
        foreach ($required_params as $param => $rules) {
            $item = null;
            if (isset($rules['required']) && $rules['required']) {
                if (!isset($posted_data[$param])) {
                    return ['status' => false, 'message' => "$param is required."];
                }
            }
            if (isset($posted_data[$param]) && !empty($posted_data[$param])) {
                if ($posted_data[$param]['size'] > 10240000) {
                    return ['status' => false, 'message' => $posted_data[$param]['name'] . " is greater than 10mb."];
                }
                $item = $posted_data[$param];
            }
            $data[$param] = $item;
        }
        return ['status' => true, 'data' => $data];
    }

    public static function getGUID(?string $table = null): string
    {
        // Generate a unique ID with high entropy
        $uid = uniqid('', true);

        // Combine a random number, server details, and timestamps for more entropy
        $namespace = rand(11111, 99999);
        $data = $namespace;

        // Add unique time-based data
        $data .= $_SERVER['REQUEST_TIME'];  // Request timestamp
        $data .= $_SERVER['REMOTE_ADDR'];   // User's IP address
        $data .= $_SERVER['REMOTE_PORT'];   // User's connection port
        $data .= date("Y-m-d H:i:s");       // Current date and time
        $data .= microtime(true);           // High precision timestamp

        // Hash the combined data for a unique result
        $hash = strtoupper(hash('ripemd128', $uid . md5($data)));

        // Format the hash into a GUID-like structure
        $new_guid = substr($hash, 0, 8) . '-' .
            substr($hash, 8, 4) . '-' .
            substr($hash, 12, 4) . '-' .
            substr($hash, 16, 4) . '-' .
            substr($hash, 20, 12);

        // Check for uniqueness if a table is provided
        if ($table) {
            if (self::doesRowValueExist($table, $new_guid)) {
                return self::getGUID($table);
            }
        }
        return $new_guid;
    }

    private static function doesRowValueExist($table, $row_value): bool
    {
        $result = DB::select('SELECT txt_row_value FROM ' . $table . ' WHERE txt_row_value=:row_id', [':row_id' => $row_value]);
        if (sizeof($result)) {
            return true;
        }
        return false;
    }

    public static function createHashedPassword($algo, $data, $salt): string
    {
        $context = hash_init($algo, HASH_HMAC, $salt);
        hash_update($context, $data);
        return hash_final($context);
    }

    public static function generateRandomNo($length = 6): string
    {
        $characters = '1234567890';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $character = $characters[rand(0, strlen($characters) - 1)];
            if ($i == 0 && $character == 0) {
                $charactersStart = '123456789';
                $character = $charactersStart[rand(0, strlen($charactersStart) - 1)];
            }
            $randomString .= $character;
        }
        return $randomString;
    }

    public static function uploadFile(array $file, $path, $allowed_files = ['jpg', 'jpeg', 'png', 'pdf']): array
    {
        try {
            apiMkdirIfNotExists($path);
            $key = $file["key"];

            if ($file['size'] < 102400000) {
                $temp = explode(".", $file["name"]);
                $temp_name = round(microtime(true));
                $file_name = $key . '_' . str_replace(' ', '_', $temp_name) . ApiLib::generateRandomNo(4) . '.' . end($temp);
                $newfilename = $path . $file_name;

                if (isset($_FILES[$key]) && !empty($_FILES[$key]) && isset($_FILES[$key]['tmp_name']) && !empty($_FILES[$key]['tmp_name'])) {
                    $tmpName = $_FILES[$key]['tmp_name'];
                } else {
                    $tmpName = $file['tmp_name'];
                }

                if (file_exists($tmpName)) {
                    chmod($tmpName, 0755);
                } else {
                    ApiLib::handleResponse('File does not exists: ' . $tmpName, [], 100, __METHOD__);
                }

                if (move_uploaded_file($file['tmp_name'], $newfilename)) {
                    $data = $file_name;
                } else {
                    return ['status' => false, 'message' => "Could not upload file " . $file['name'][0]];
                }
            } else {
                return ['status' => false, 'message' => "File " . $file['name'][0] . ' Size is greater than 10MB'];
            }
            return ['status' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['status' => false, 'message' => "Could not upload file " . $file['name'] . ': ' . $e->getMessage()];
        }
    }

    public static function requestControl($url, $payload)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => API_URL . '/' . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public static function sendSmsNotification($data, $lang = 1)
    {
        $sms = new SmsSender();
        return $sms->sendTemplateSMS($data['reason'], $data['mobile'], $data['labels'], $data['values'], $lang);
    }

//    private static function isArrayAndChildrenNotEmpty($array): bool
//    {
//        if (empty($array) || is_array($array)) {
//            ApiLog::sysLog('The array itself is empty or not an array');
//            return false;
//        }
//
//        foreach ($array as $child) {
//            if (empty($child)) {
//                ApiLog::sysLog('At least one child is empty: ' . json_encode($child));
//                return false;
//            }
//        }
//        return true;
//    }

    private static function isArrayAndChildrenNotEmpty($array): bool
    {
        // First, check if the passed variable is indeed an array
        if (!is_array($array)) {
            ApiLog::sysLog('The provided input is not an array: ' . json_encode($array));
            return false;
        }

        // If the array is empty, return false
        if (empty($array)) {
            ApiLog::sysLog('The array itself is empty');
            return false;
        }

        // Check if any child element in the array is empty
        foreach ($array as $child) {
            if (empty($child)) {
                ApiLog::sysLog('At least one child is empty: ' . json_encode($child));
                return false;
            }
        }

        return true;
    }

    public static function bodyData(array $required_fields, bool $is_file = false, $passed_data = null)
    {
        $data = $passed_data ?? Request::getBody();

        if ($is_file && isset($data['files'])) {
            ApiLog::sysLog('FILES-UPLOADED: [' . json_encode($data['files']) . ']');
        } else {
            ApiLog::sysLog('DATA-UPLOADED: [' . json_encode($data) . ']');
        }

        $children_not_empty = isset($data['files']) ? ApiLib::isArrayAndChildrenNotEmpty($data['files']) : false;

        $validator = ApiLib::validator(
            $is_file && $children_not_empty ? $data['files'] : $data,
            $required_fields,
            $is_file
        );

        if (!$validator['status']) {
            ApiLib::handleResponse($validator['message'], [], 100, __METHOD__);
        }

        return $validator['data'];
    }

    public static function validator($posted_data, $required_params, $files = false): array
    {
        $data = [];
        foreach ($required_params as $param => $rules) {
            $item = null;

            if (isset($rules['required']) && $rules['required']) {
                if (!isset($posted_data[$param]) || is_null($posted_data[$param]) || $posted_data[$param] === '') {
                    return ['status' => false, 'message' => "$param is required."];
                }
            }

            if (!empty($posted_data[$param])) {
                if ($files) {
                    if (is_array($posted_data[$param]['size'])) {
                        foreach ($posted_data[$param]['size'] as $key => $value) {
                            if ($value > 10240000) {
                                return ['status' => false, 'message' => $posted_data[$param]['name'][$key] . " is greater than 10mb."];
                            }
                        }
                    } else {
                        if ($posted_data[$param]['size'] > 10240000) {
                            return ['status' => false, 'message' => $posted_data[$param]['name'] . " is greater than 10mb."];
                        }
                    }
                    $item = $posted_data[$param];
                } elseif (is_array($posted_data[$param])) {
                    foreach ($posted_data[$param] as $index => $object) {
                        foreach ($rules['items'] as $keys => $value) {
                            if (isset($value['required']) && $value['required']) {
                                if (!isset($posted_data[$param][$index][$keys])) {
                                    return ['status' => false, 'message' => "$keys is required for " . $param . "."];
                                }
                            }

                        }
                    }
                    $item = $posted_data[$param];
                } else {
                    if ($param != 'offers') {
                        $item = filter_var($posted_data[$param], $rules['filter']);
                    } else {
                        $item = $posted_data[$param];
                    }
                    if (!$item) {
                        return ['status' => false, 'message' => "$param has illegal data type."];
                    }
                }
            }

            $data[$param] = $item;
        }
        return ['status' => true, 'data' => $data];
    }

    public static function handleResponse(string $msg, ?array $data = null, ?int $code = 200, ?string $class = null, ?string $extra = null): void
    {
        $response = [];

        $code = ($code == null) || ($code == '') ? 500 : $code;

        switch ($code) {
            case 100:
                $message = $msg;
                break;
            case 401:
                $message = 'UNAUTHORIZED ACCESS';
                break;
            case 403:
                $message = 'FORBIDDEN ACCESS';
                break;
            case 404:
                $message = 'PAGE NOT FOUND';
                break;
            case 500:
                $message = 'Something went wrong. Please contact your Administrator with the Request ID: ' . ApiLog::$request_number;
                break;
            default:
                $message = $msg;
                break;
        }

        ApiLog::sysLog('ACTUAL-MESSAGE: ' . $msg);

        if (!empty($class)) {
            ApiLog::sysLog('EXCEPTION: ' . $class);
        }

        if (!empty($extra)) {
            ApiLog::sysLog($extra);
        }

        $response["code"] = $code;
        $response["message"] = $message;
        $response["data"] = $data;

//        http_response_code($code);

        ApiLog::sysLog('RESPONSE: [' . json_encode($response) . ']');
        echo json_encode(['response' => $response]);
        exit;
    }

    public function getLastInsertId($table, $row_value, $db)
    {
        $result = $db->select('SELECT id FROM ' . $table . ' WHERE txt_row_value = :row_value', [':row_value' => $row_value]);
        if (count($result)) {
            return $result[0]['id'];
        }
        return '';
    }

    public function getRecord($id, $table)
    {
        $sql = 'SELECT * FROM ' . $table . ' WHERE id = :id';
        $result = DB::select($sql, array(':id' => $id));
        if (sizeof($result)) {
            if (array_key_exists('rowguid', $result[0])) {
                unset($result[0]['rowguid']);
            }
            return $result[0];
        }
        return [];
    }

    public function verifyUser($data): bool
    {
        $login_id = filter_var($data['user_id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $result = DB::select('SELECT * FROM mx_login_credential WHERE txt_row_value=:id', [':id' => $login_id]);
        if (sizeof($result)) {
            $user = DB::select(
                'SELECT * FROM ' . $result[0]['txt_domain'] . ' WHERE id = :id',
                [':id' => $result[0]['user_id']]
            );
            if (sizeof($user)) {
                return true;
            }
        }
        return false;
    }
}
