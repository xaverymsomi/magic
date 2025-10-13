<?php

namespace Libs;

class VerifyRequest
{
    public static $encKey = "+zSPmu9YomHBzlrjooTg5jeY9o+CQUr6CpAvGw95R74=";
    public static $encIV = "bVfcQ0EPE82E1mJzLwVLYw==";

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
