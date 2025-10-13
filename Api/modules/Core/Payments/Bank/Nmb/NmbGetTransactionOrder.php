<?php

namespace Modules\Core\Payments\Bank\Nmb;

use Core\database\QueryBuilder as DB;
use Libs\ApiLib;
use Libs\ApiLog;

class NmbGetTransactionOrder
{
    private $dir = 'NMB/';
    private $path = 'payment_order';
    private $db;
    private mixed $request_data;
    private mixed $payment_nmb_data;

    public function __construct($payment_nmb_data, $db)
    {
        $this->db = $db;
        $this->payment_nmb_data = $payment_nmb_data;
    }

    public function index(): array
    {
        $this->logData(['received Get Order data' => $this->payment_nmb_data]);

        $required_data = [
            'txt_order_id' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'txt_control_number' => ['required' => false, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'txt_reference_number' => ['required' => false, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'txt_success_indicator' => ['required' => false, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'dbl_amount' => ['required' => false, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'txt_currency' => ['required' => false, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'int_used' => ['required' => false, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
        ];

        $validator = ApiLib::validator($this->payment_nmb_data, $required_data);
        if (!$validator['status']) {
            ApiLib::handleResponse("Missing key: " . $validator['message'],null, 100);
        }

        $this->request_data = $validator['data'];

        $order = $this->processCheckOrder();

        if (!$order['status']) {
            return ['status' => false, 'code' => 100, 'message' => 'Failed to get Order Data. Please Try Again'];
        }
        return ['status' => true, 'code' => 200, 'message' => $order['message'], 'data' => $order['data']];

    }


    public function processCheckOrder(): array
    {
        $result = $this->sendRequest();
        if (!$result['status']) {
            return ['status' => false, 'code' => 100, 'message' => $result['message']];
        }
        return ['status' => true, 'code' => 200, 'message' => 'Payment retrieved Successful', 'data' => $result['data']];
    }
    public function sendRequest(): array
    {
        $orderId = $this->request_data['txt_order_id'];

        $merchantId = NMB_MERCHANT_ID;
        $password = NMB_PASSWORD;
        $url = NMB_URL . "/order/$orderId";
        $authHeader = "Basic " . base64_encode("merchant.$merchantId:$password");
;
        $headers = [
            "Authorization: $authHeader",
            "Content-Type: text/plain"
        ];

        // cURL setup
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "merchant.$merchantId:$password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute request
        $result = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Check for cURL errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['status' => false, 'code' => 100, 'message' => 'Request Failed'];
        }

        curl_close($ch);

        $response = json_decode($result, true);;

        // Log request and response
        $this->logData("[RESPONSE]: " . json_encode($response), true);

        // Parse and return response
        if ($response['result'] === "SUCCESS") {
            return ['status' => true, 'code' => $httpCode, 'data' => $response];
        } else {
            return ['status' => false, 'code' => $httpCode, 'message' => $response['error']['explanation']];
        }
    }
    public function logData($payload, $end = false): void
    {
        $logDir = 'payment/' . $this->dir . '/' . date('Y') . '/' . date('M');
        $logFile = $this->path;
        ApiLog::custom_log_payment($logDir, $logFile, $payload, $end);
    }
}