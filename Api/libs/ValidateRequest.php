<?php

namespace Libs;

class ValidateRequest
{
    public static $encKey = "+gnZadvvsghEm0JIeh6qkMLTvBnSEuGZGiiqTogUeIw=";
    public static $encIV = "EF0szYCqKZaC92pRbfqBIQ==";

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