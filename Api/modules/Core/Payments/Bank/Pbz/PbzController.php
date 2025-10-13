<?php

namespace Modules\Core\Payments\Bank\Pbz;

use Core\database\QueryBuilder as DB;
use Core\Request;
use Exception;
use Libs\ApiLib;
use Libs\ApiLog;

class PbzController
{
    private $dir = 'PBZ/';
    private $path = 'payment_verify';

    protected $db;
    protected $validate_data;
    private $lib;
    private array $error_response;
    private $data;
    private ?string $bill_type;
    private $application;

    private $control_number;
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
            'control_number' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'token' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'checksum' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'channel_id' => ['required' => false, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
        ];

        $this->required_params = $required_params;

        $validityCheck = $this->init();
        if ($validityCheck !== true) {
            $this->error_response = ['code' => 422, 'description' => 'Unprocessable Entity', 'message' => 'Unprocessable Entity'];
            $this->logData("[RESPONSE-DATA]: [" . json_encode($this->error_response) . ']');
            echo json_encode($this->error_response);
            exit();
        }


        $result = $this->processVerification();
        echo json_encode($result);
        
        $validator = ApiLib::validator($data, $required_params);
        if (!$validator['status']) {
            ApiLib::handleResponse("Missing key: " . $validator['message'],null, 100);
        }

        $this->validate_data = $validator['data'];

        $this->db->commit();
        echo 123;exit;
        // body
    }


    public function init(): bool
    {
        if (
            $this->checkValidPostRequest() !== true
            || $this->checkRequestUrlEncoded() !== true
            || $this->validateRequestData() !== true
            || $this->checkSecurity() !== true
        ) {
            return false;
        }
        return true;
    }


    private function checkValidPostRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->error_response = [
                'code' => 400, 'description' => 'Invalid Request Sent',
                'message' => 'Bad Request'
            ];

            echo json_encode($this->error_response);
            exit();
        }
        return true;
    }

    private function checkRequestUrlEncoded()
    {
        if ($_SERVER['CONTENT_TYPE'] != 'application/json') {
            $this->error_response = [
                'code' => 406, 'description' => 'Invalid Content Supplied. Please use application/json',
                'message' => 'Not Acceptable'
            ];
            echo json_encode($this->error_response);
            exit();
        }
        return true;
    }

    private function validateRequestData()
    {
        $data = Request::getBody();

        $this->logData('[PAYMENT-DATA]: [' . json_encode($data) . ']');

        $validator = $this->validator($data, $this->required_params);

        if (!$validator['status']) {
            $this->error_response = ['status' => 422, 'description' => $validator['message'], 'message' => 'Unprocessable Entity'];
            $this->logData('[ERROR]: [' . json_encode($this->error_response) . ']');
            echo json_encode($this->error_response);
            exit();
        }

        $this->data = $validator['data'];

        $this->logData("[VALIDATED-DATA]: [" . json_encode($this->data) . ']');
        return true;
    }

    private function checkSecurity()
    {
        try {
            $result = $this->db->select("select * from mx_payment_provider where txt_token = :token ", [':token' => $this->data['token']]);

            if ($result) {
                $generated_checksum = SHA1($this->data['token'] . md5(trim($this->data['control_number'])));

                // echo($generated_checksum);
                if ($this->data['checksum'] == $generated_checksum) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->error_response = [
                'code' => 401,
                'description' => 'Invalid Security Credentials',
                'message' => 'Unauthorized'
            ];

            ApiLog::sysErr(['message' => $e->getMessage(), 'trace' => $e->getTrace()]);
            echo json_encode($this->error_response);
            exit();
        }

        $this->error_response = [
            'code' => 401,
            'description' => 'Invalid Security Credentials',
            'message' => 'Unauthorized'
        ];
        echo json_encode($this->error_response);
        exit();
    }


    public function processVerification(): array
    {
        $this->setBillType($this->data['control_number']);

        if ($this->bill_type != 'ZCT') {
            $fail_response = [
                'code' => 422,
                'description' => 'Invalid control number provided',
                'message' => 'Unprocessable Entity'
            ];
            $this->logData("[VERIFICATION-RESULT]: [" . json_encode($fail_response) . ']', true);
            return $fail_response;
        }

        $result = $this->processApplicationVerification($this->data);
        $this->logData("[RESPONSE-DATA]: [" . json_encode($result) . ']');
        $this->logData("[VERIFICATION-RESULT]: [" . json_encode($result) . ']', true);
        return $result;
    }

    public function setBillType($control_number): void
    {
        $control_number_check = substr($control_number, 0, 2);

        if ($control_number_check === "90") {
            $this->bill_type = 'ZCT';
        } else {
            $this->bill_type = null;
        }
    }

    public function processApplicationVerification($data): array
    {
        $this->control_number = $data['control_number'];

        if ($this->checkControlNumberValidityAndSetApplicationData() !== true) {
            return [
                'code' => 405,
                'description' => "Control number is invalid",
                'message' => "Unprocessable Entity"
            ];
        }

        if ($this->application['bill_status_id'] == 1) {
            return [
                'code' => 405,
                'description' => "Payment with this control number is already made",
                'message' => "Unprocessable Entity"
            ];
        } elseif ($this->application['bill_status_id'] == 3) {
            return [
                'code' => 405,
                'description' => "Invoice with this control number is cancelled",
                'message' => "Unprocessable Entity"
            ];

        } elseif ($this->application['bill_status_id'] == 4) {
            return [
                'code' => 422,
                'description' => "Invoice with this control number is Expired",
                'message' => "Unprocessable Entity"
            ];
        }
        return $this->getApplicationPaymentData();
    }

    private function checkControlNumberValidityAndSetApplicationData(): bool
    {
        $sql = "SELECT
            COALESCE(mx_business.txt_name, mx_trainee.txt_name) AS name,
            COALESCE(mx_business.txt_reference, mx_trainee_training_detail.txt_reference) AS txt_reference,
            mx_invoice_type.txt_name AS bill_type,
            mx_invoice.dbl_amount AS amount,
            mx_invoice_status.txt_name AS bill_status,
            mx_invoice_status.id AS bill_status_id,
            mx_invoice.txt_currency AS currency
        FROM mx_invoice
                 LEFT JOIN mx_business
                           ON mx_invoice.opt_mx_business_id = mx_business.id
                 LEFT JOIN mx_trainee_training_detail
                           ON mx_invoice.opt_mx_trainee_training_detail_id = mx_trainee_training_detail.id
                 LEFT JOIN mx_trainee
                           ON mx_trainee_training_detail.opt_mx_trainee_id = mx_trainee.id
                 JOIN mx_invoice_status
                      ON mx_invoice.opt_mx_invoice_status_id = mx_invoice_status.id
                 JOIN mx_invoice_type
                      ON mx_invoice.opt_mx_invoice_type_id = mx_invoice_type.id
            WHERE mx_invoice.txt_control_number = :control_number
            ";
        $params = [
            ':control_number' => $this->control_number
        ];

        $result = $this->db->select($sql, $params);
        if ($result) {
            $this->application = $result[0];
            return true;
        }
        return false;
    }

    private function getApplicationPaymentData(): array
    {
        $this->data['amount'] = $this->getApplicationPaymentAmounts();

        $response = [
            'amount' => $this->data['amount'],
        ];

        $response['applicant'] = $this->application['name'];
        $response['business_reference'] = $this->application['txt_reference'];
        $response['bill_status'] = $this->application['bill_status'];
        $response['bill_type'] = $this->application['bill_type'];
        $response['control_number'] = $this->control_number;

        return [
            'code' => 200,
            'description' => 'Control Number Verified Successfully',
            'message' => 'Success',
            'data' => $response
        ];
    }

    private function getApplicationPaymentAmounts(): array
    {
        return [
            'currency' => $this->application['currency'],
            'amount' => $this->application['amount'],
        ];
    }
    public function logData($payload, $end = false): void
    {
        $logDir = 'payment/' . $this->dir . '/' . date('Y') . '/' . date('M');
        $logFile = $this->path;
        ApiLog::custom_log_payment($logDir, $logFile, $payload, $end);
    }

}