<?php

namespace Libs;

use Core\Request;

class JWT
{
    public static function generate_jwt($headers, $payload, $secret = '4081c766a01be7c2986ffcf8e26eebf38decc1f1e195b94db9881e76bb716469='): string
    {
        $headers_encoded = self::base64url_encode(json_encode($headers));

        $payload_encoded = self::base64url_encode(json_encode($payload));

        $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
        $signature_encoded = self::base64url_encode($signature);

        return "$headers_encoded.$payload_encoded.$signature_encoded";
    }

    public static function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function refreshToken($header, $payload, $signature_provided)
    {
    }

    public static function is_jwt_valid($jwt, $secret = '4081c766a01be7c2986ffcf8e26eebf38decc1f1e195b94db9881e76bb716469=')
    {
        // split the jwt
        $tokenParts = explode('.', $jwt);
        // 	print_r($jwt);
        // 	print_r($tokenParts);
        $header = base64_decode($tokenParts[0]);
        if (!isset($tokenParts[2])) {
            return false;
        }
        $payload = base64_decode($tokenParts[1]);
        $signature_provided = $tokenParts[2];

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        $expiration = json_decode($payload)->exp;
        // $is_token_expired = ($expiration - time()) < 0 && date('H:i:s') > '18:10:00';
        $is_token_expired = ($expiration - time()) < 0;


        // build a signature based on the header and payload using the secret
        $base64_url_header = self::base64url_encode($header);
        $base64_url_payload = self::base64url_encode($payload);
        $signature = hash_hmac('SHA256', $base64_url_header . "." . $base64_url_payload, $secret, true);
        $base64_url_signature = self::base64url_encode($signature);

        // verify it matches the signature provided in the jwt
        $is_signature_valid = ($base64_url_signature === $signature_provided);


        //	$is_token_expired ||
        if ($is_token_expired || !$is_signature_valid) {
            return FALSE;
        } else {
            // refreshToken($header,$payload,$signature_provided);
            return TRUE;
        }
    }

    public static function get_token_data($jwt, $secret = '4081c766a01be7c2986ffcf8e26eebf38decc1f1e195b94db9881e76bb716469=')
    {
        $tokenParts = explode('.', $jwt);
        $payload = base64_decode($tokenParts[1]);
        return json_decode($payload);
    }

    public static function get_bearer_token()
    {
        $headers = self::get_authorization_header();

        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    public static function get_authorization_header()
    {
        $headers = null;
        // 	print_r($_SERVER);
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            // 		print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            } else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
            }
        }

        $req = Request::getBody();
        ApiLog::sysLog(json_encode(['RequestBody: ' => $req]));
        if (isset($req['authorization'])) {
            $headers = $req['authorization'];
        } elseif (isset($req['token'])) {
            $headers = $req['token'];
        }
        // 	print_r($headers);
        return $headers;
    }
}
