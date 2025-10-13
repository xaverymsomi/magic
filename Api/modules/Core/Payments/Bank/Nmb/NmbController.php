<?php

namespace Modules\Core\Payments\Bank\Nmb;

use Core\database\QueryBuilder as DB;
use Core\Request;
use Libs\ApiLib;
use Libs\ApiLog;

class NmbController
{
    private $dir = 'NMB/';
    private $path = 'payment_checkout';

    protected $db;
    protected $validate_data;
    private $lib;
    public function __construct()
    {
        $this->db = new DB;
        $this->lib = new ApiLib();
    }

    public function index()
    {
        $this->db->begin();

        //Get submitted application Data
        $data = Request::getBody();

        $this->logData(['received Application data' => $data]);

        $required_params = [
            'application_reference' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'currency' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'amount' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'description' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'merchant_name' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'control_number' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
        ];

        $validator = ApiLib::validator($data, $required_params);
        if (!$validator['status']) {
            ApiLib::handleResponse("Missing key: " . $validator['message'],null, 100);
        }

        $this->validate_data = $validator['data'];

        //check if Payment is already Paid

        $check_payment = $this->processCheckPayment();

        if($check_payment['status']){
            $this->db->rollBack();
            ApiLib::handleResponse($check_payment['message'], [], 100);
        }


        $checkout = $this->processCheckOut();

        if(!$checkout['status']){
            $this->db->rollBack();
            ApiLib::handleResponse($check_payment['message'], [], 100);
        }
        
        $this->logData('NMB-CHECKOUT-COMPLETE', true);

        $this->db->commit();
        ApiLib::handleResponse($checkout['message'], $checkout['data']);
    }
    
    public function processCheckPayment(): array
    {
        $payment_nmb_logs = $this->db->select("SELECT * FROM mx_payment_nmb_logs WHERE mx_payment_nmb_logs.txt_control_number=:txt_control_number",
            [':txt_control_number' => $this->validate_data['control_number']]);

        if (empty($payment_nmb_logs) || empty($payment_nmb_logs[0])) {
            return ['status'=>false, 'code'=> 100, 'message'=> 'Payment Configuration not found'];
        }

        foreach ($payment_nmb_logs as $logs) {

            // Search to NMB if any success payment found
            $getOrder = (new NmbGetTransactionOrder($logs, $this->db))->index();

            $responseData = json_decode(json_encode($getOrder), true);

            $approved = false;
            $transactionDetails = [];

            // Check if 'transaction' exists in 'data'
            if (isset($responseData['data']['transaction']) && is_array($responseData['data']['transaction'])) {
                foreach ($responseData['data']['transaction'] as $transaction) {
                    if (
                        isset($transaction['response']['acquirerCode']) &&
                        $transaction['response']['acquirerCode'] === "00" &&
                        isset($transaction['response']['acquirerMessage']) &&
                        $transaction['response']['acquirerMessage'] === "Approved"
                    ) {
                        $approved = true;

                        // Retrieve required transaction details
                        $transactionDetails = [
                            'transaction_number' => $transaction['transaction']['acquirer']['transactionId'],
                            'amount' => $transaction['transaction']['amount'],
                            'currency' => $transaction['transaction']['currency'],
                            'receipt_number' => $transaction['transaction']['receipt'],
                            'authorizationCode' => $transaction['transaction']['authorizationCode']
                        ];
                    }
                }
            }
            // If approved transaction is found, return true with transaction details
            if ($approved) {
                return [
                    'status' => true,
                    'code' => 200,
                    'message' => 'Payment already exists',
                    'transaction_details' => $transactionDetails
                ];
            }
        }

        // If no approved payment is found, return false
        return [
            'status' => false,
            'code' => 101,
            'message' => 'Payment not approved or found'
        ];
    }

    public function processCheckOut(): array
    {
        $returnUrl = API_URL . "/api/payment/nmb/complete_payment";
        $merchantUrl = PORTAL_URL;

        $transId = uniqid();
        $data = [
            "apiOperation" => "INITIATE_CHECKOUT",
            "checkoutMode" => 'WEBSITE',
            "interaction" => [
                "operation" => "PURCHASE",
                "merchant" => [
                    "name" =>  $this->validate_data['merchant_name'],
                    "url" => $merchantUrl
                ],
                "returnUrl" => $returnUrl
            ],
            "order" => [
                "currency" => strtoupper( $this->validate_data['currency']),
                "amount" =>  $this->validate_data['amount'],
                "id" => $transId,
                "description" =>  $this->validate_data['description']
            ]
        ];


        $this->logData("[PAYLOAD-DATA]: [" . json_encode($data) . ']');

        $result = $this->sendRequest($data);

        if(!$result['status']){
            return ['status'=>false, 'code'=> 100, 'message'=> $result['message']];
        }
        return ['status'=>true, 'code'=>200,'message'=> 'Payment initiated successfully', 'data'=>$result['data']];
    }

    public function  sendRequest($data): array
    {
        $url = NMB_URL . "/session";

        // Merchant credentials
        $merchantId = NMB_MERCHANT_ID;
        $password = NMB_PASSWORD;
        $authHeader = "Basic " . base64_encode("merchant.$merchantId:$password");

        $headers = [
            "Authorization: $authHeader",
            "Content-Type: text/plain"
        ];

        // cURL setup
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Execute request
        $result = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Check for cURL errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['status'=> false, 'code' => 100, 'message'=> 'Request Failed'];
        }

        curl_close($ch);

        $response = json_decode($result, true);;

        $this->logData("[RESPONSE-DATA]: [" . json_encode($response) . ']');
        // Log request and response
        $this->logData("[REQUEST]: " . json_encode($data), true);
        $this->logData("[RESPONSE]: " . json_encode($response), true);

        // Parse and return response
        if ($response['result'] === "SUCCESS") {

            $data_to_save = [
                'txt_success_indicator' => $response['successIndicator'],
                'txt_order_id' => $data['order']['id'],
                'txt_control_number' =>  $this->validate_data['control_number'],
                'txt_reference_number' =>  $this->validate_data['application_reference'],
                'txt_currency' => $data['order']['currency'],
                'dbl_amount' => $data['order']['amount']
            ];


            $this->logData("[SAVE-NMB-LOGS-DATA]: [" . json_encode($data_to_save) . ']');
            $save = $this->db->save('mx_payment_nmb_logs', $data_to_save, 'NMB MODEL');

            if(!$save){
                return ['status'=> false, 'code' => 100, 'message'=> 'Failed to Save NMB Logs. Please Try again'];

            }
            return ['status' => true, 'code'=> $httpCode, 'data'=>$response];
        } else {
            return ['status'=> false, 'code' => $httpCode, 'message'=> $response['error']['explanation']];
        }
    }
    public function logData($payload, $end = false): void
    {
        $logDir = 'payment/' . $this->dir . '/' . date('Y') . '/' . date('M');
        $logFile = $this->path;
        ApiLog::custom_log_payment($logDir, $logFile, $payload, $end);
    }
}