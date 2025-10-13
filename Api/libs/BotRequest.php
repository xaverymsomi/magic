<?php

namespace Libs;

class BotRequest
{
    public static $encKey = "s6mJ7T2vhgVSHTzRjXDgvlJNAndcTVNTUHD3m82kewk=";
    public static $encIV = "y+KUvozntky6ZGY6PBSkEA==";

    public static function encryptFile($data)
    {
        $key = base64_decode(self::$encKey);
        $iv = base64_decode(self::$encIV);
        $encrypter = 'aes-256-cbc';
        $encrypted = openssl_encrypt($data, $encrypter, $key, 0, $iv);
        return $encrypted;
    }

    public static function decryptFile($data)
    {
        $key = base64_decode(self::$encKey);
        $iv = base64_decode(self::$encIV);
        $encrypter = 'aes-256-cbc';
        $decrypted = openssl_decrypt($data, $encrypter, $key, 0, $iv);
        return $decrypted;
    }
}