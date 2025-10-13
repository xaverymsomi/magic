<?php

namespace Core;

use Exception;
use Libs\ApiLog;
use SimpleXMLElement;

class Request
{
    public static function uri(): string
    {
        return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    }

    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function getBody(): array
    {
        $data = file_get_contents("php://input");
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';

        ApiLog::sysLog('API-CONTENT-TYPE: [' . $content_type . ']');
        ApiLog::sysLog('API-RAW-INPUT: [' . json_encode($data, JSON_NUMERIC_CHECK) . ']');
        ApiLog::sysLog('API-RAW-POST: [' . json_encode($_POST, JSON_NUMERIC_CHECK) . ']');
        ApiLog::sysLog('API-RAW-FILES: [' . json_encode($_FILES, JSON_NUMERIC_CHECK) . ']');

        // Default to an empty array if decoding fails
        $parsedData = json_decode($data, true);
        if (!is_array($parsedData)) {
            $parsedData = [];
        }

        // Recursively merge json input, $_POST, and $_FILES
        $posted_data = array_merge_recursive($parsedData, $_POST, $_FILES);

        ApiLog::sysLog('API-PARSED-DATA: [' . json_encode($posted_data, JSON_NUMERIC_CHECK) . ']');

        return $posted_data;
    }

    public static function malicious_inputs($data): void
    {
        // Check for malicious content in the inputs
        $malicious_patterns = [
            '/\b(https?|ftp|file):\/\//i', // URLs
            '/[\r\n]/', // Newlines
            '/[<>]/', // HTML tags or special characters
            '/\b(php|eval|javascript):/i', // Code execution attempts
            '/\b(base64_encode|base64_decode)/i', // Base64 encoding/decoding
            '/\b(gzinflate|gzuncompress)/i', // Compression functions
            '/\b(system|exec|shell_exec|passthru|proc_open|popen|pcntl_exec)/i', // Command execution functions
            '/\b(eval|create_function)/i', // Eval and create_function usage
            '/\b(assert|preg_replace)/i', // Other dangerous functions
            '/\b(iframe|<script|on\w+=)/i', // XSS attempts
            '/\b(sql_(connect|query)|mysql_(connect|query)|mysqli_(connect|query)|pg_(connect|query)|sqlite_(open|query)|sqlite3_(open|query))/i', // Database function calls
            '/\b(unlink|fwrite|fopen|file_put_contents)/i', // File manipulation functions
            '/\b(mail|header)/i', // Email header injection
            '/\b(\$_(GET|POST|COOKIE|REQUEST|FILES|SERVER))/i', // Superglobal variables
            '/\b(\$_(SESSION|ENV|GLOBALS))/i', // Potentially sensitive superglobal variables
            // Add more patterns as needed
        ];

        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $data)) {
                ApiLog::sysLog('Malicious data input: [' . json_encode($data) . ']');
            }
        }
    }

    private static function parseXml($data)
    {
        $posted_data = [];
        $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $data);

        try {
            $xml = new SimpleXMLElement($response);
            if ($body = $xml->xpath('//SBody')) {
                $body = $body[0];
                $posted_data = json_decode(json_encode((array)$body), true);
            } else {
                $posted_data = json_decode(json_encode($xml), true);
            }
        } catch (Exception $e) {
            ApiLog::sysLog("XML Parsing Error: " . $e->getMessage());
            $posted_data = [];
        }

        return $posted_data;
    }


    private static function getPostedData(): array
    {
        $data = $_POST;
        $files = $_FILES;
        return array_merge($data, ['files' => $files]);
    }
}
