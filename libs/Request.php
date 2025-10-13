<?php

namespace Libs;
class Request
{

    public static function uri() : string
    {
        return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    }

    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function getBody()
    {
        if ($_SERVER['CONTENT_TYPE'] == 'application/json' || $_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') {
            $posted_data = json_decode(file_get_contents("php://input"), true);
        } else {
            $posted_data = self::getPostedData();
        }

        return $posted_data;
    }

    private static function getPostedData() : array
    {
        $data = $_POST;
        $files = $_FILES;
        return array_merge($data, $files);
    }
}