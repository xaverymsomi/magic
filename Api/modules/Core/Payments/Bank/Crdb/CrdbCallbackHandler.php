<?php

namespace Modules\Core\Payments\Bank\Crdb;

use Core\database\QueryBuilder as DB;
use Libs\ApiLib;
use Libs\ApiLog;
use Modules\Core\payments\PaymentComplete\PaymentCompletion;
use Modules\Core\Payments\PostPayment;

class CrdbCallbackHandler
{
    private $lib;
    private $db;
    private $invoice_data;

    public function __construct()
    {
        $this->db = new DB;
        $this->lib = new ApiLib();
    }

    public function index(): void
    {

        $this->db->begin();

        $raw = $_POST;

        $response = json_encode(json_encode($raw));

        $this->logData(['received response from cybersource' => $response]);

        $payment_configuration_logs_result = $this->db->select("SELECT * FROM mx_payment_configuration_logs WHERE mx_payment_configuration_logs.int_used = 0 AND mx_payment_configuration_logs.txt_reference_number=:reference_number",
            [':reference_number' => $response->req_reference_number]);

        //  echo json_encode($payment_configuration_logs_result);exit;
        if (empty($payment_configuration_logs_result)) {
            ApiLib::handleResponse("Payment Failed. Please back to your Application", $response->req_reference_number, 100);
            $this->renderForm(100,$response->req_reference_number,"Payment Failed. Please back to your Application");
        }

        $payment_configuration_logs = $payment_configuration_logs_result[0];
        if ($payment_configuration_logs['int_used'] == 1) {
            ApiLib::handleResponse("Token was already used. Please back to your Application", $response->req_reference_number, 100);
            $this->renderForm(100, $response->req_reference_number, "Token was already used. Please back to your Application");
        }

        $data = [
            'int_used' => 1
        ];

        $update = $this->db->update('mx_payment_configuration_logs',$data, $payment_configuration_logs['id']);

        if (!$update) {
            ApiLib::handleResponse("Failed to update Payment configuration. Please try again!", $response->req_reference_number, 100);
            $this->renderForm(100, $response->req_reference_number, "Failed to update Payment configuration. Please try again!");
        }

        $result = $this->db->select("SELECT
                mx_invoice.*,
                mx_applicant.id as applicant_id,
                mx_invoice_type.txt_name as bill_type,
                mx_currency.txt_abbreviation AS txt_currency
                FROM mx_application
                    JOIN mx_invoice ON mx_application.id = mx_invoice.opt_mx_application_id
                    JOIN mx_applicant ON mx_application.entity_id = mx_applicant.id AND mx_application.opt_mx_entity_type_id = 2
                    JOIN mx_currency  ON mx_currency.id = mx_invoice.opt_mx_currency_id
                    JOIN mx_invoice_status ON mx_invoice.opt_mx_invoice_status_id = mx_invoice_status.id
                    JOIN mx_invoice_type ON mx_invoice.opt_mx_invoice_type_id = mx_invoice_type.id
                    WHERE txt_reference =:txt_reference",
            [':txt_reference' => $response->req_reference_number]);

        if (empty($result) || empty($result[0])) {
            $this->db->rollBack();
            ApiLib::handleResponse("Invoice Details not found", $response->req_reference_number, 100);
            $this->renderForm(100, $response->req_reference_number, "Invoice Details not found");
        }

        $this->invoice_data = $result[0];


        if ($this->invoice_data['opt_mx_invoice_status_id'] == 1) {
            $this->db->rollBack();
            ApiLib::handleResponse('Invoice details already paid', null, 100);
            $this->renderForm(100, $response->req_reference_number, "Invoice details already paid");

        }


        // echo json_encode($payment_configuration_logs);exit;
        //provided profile id from response
        $receivedProfile = $response->req_profile_id;
        //provided access key from response
        $receivedAccesskey = $response->req_access_key;
        //provided signature from response
        $receivedSignature = $response->signature;
        //provided amount from response
        $receivedAmount = $response->req_amount;

        //institution profile id
        $institution_profile = $payment_configuration_logs['txt_profile_id'];
        //institution access key
        $institution_access_key = $payment_configuration_logs['txt_access_key'];
        //billed amount
        $invoice_amount = $this->invoice_data['dbl_amount'];
//        $invoice_partial_amount = $this->invoice_data['dbl_partial_amount'];
        $billed_amount = $this->invoice_data['dbl_amount_due'];
        $invoice_amount_to_pay = $this->invoice_data['dbl_amount_due'];
        //payment signature
        $payment_conf_signature = $payment_configuration_logs['txt_signature'];

// echo json_encode($invoice_amount_to_pay);exit;
//        check payment signature if matches the signature provided from response
         $validate_payment_signature = $this->validatePaymentSignature($receivedSignature, $payment_conf_signature);
         if (!$validate_payment_signature ) {
             ApiLib::handleResponse('Payment Signature does not match', null, 100);
             $this->renderForm(100,$response->req_reference_number,"Payment Signature does not match");
         }

        //validate institution profile id if matches the profile id provided from response
        $validate_institution_profile_id = $this->validateInstitutionProfile($institution_profile, $receivedProfile);
        if (!$validate_institution_profile_id) {
            ApiLib::handleResponse('Profile Id provided is not of the institution', null, 100);
            $this->renderForm(100,$response->req_reference_number,"Profile Id provided is not of the institution");
        }

        //validate institution access key if matches the access key provided from response
        $validateinstitutionaccesskey = $this->validateInstitutionAccessKey($institution_access_key, $receivedAccesskey);
        if (!$validateinstitutionaccesskey) {
            ApiLib::handleResponse("Access key provided is not of the institution", $response->req_reference_number, 100);
            $this->renderForm(100,$response->req_reference_number,"Access key provided is not of the institution");
        }

        //check if amount paid matches the amount billed
        /*  $checkAmountPaidValidity = $this->checkAmountPaidValidity($receivedAmount, $invoice_amount, $response);
          if (!$checkAmountPaidValidity) {
              $this->renderForm(201,$response->req_reference_number,"Paid Amount is less to the billed Amount, Please complete your payment");
              exit;
          }*/

        $response_handler = $this->handleCybersourceResponse($receivedAmount, $response);

        if (!$response_handler['status']) {
            $this->db->rollBack();
            ApiLib::handleResponse($response_handler['message'], null, 100);
            $this->renderForm(100, $response->req_reference_number, $response_handler['message']);
        }


        ApiLib::handleResponse($response_handler['message'],$response->req_reference_number );

        $this->renderForm(200,$response->req_reference_number,$response_handler['message']);
        $this->db->commit();
    }

    private function validateInstitutionProfile($institution_profile, $receivedProfile): bool
    {
        if ($institution_profile == $receivedProfile) {
            return true;
        } else {
            return false;

        }
    }

    private function validateInstitutionAccessKey($institution_access_key, $receivedAccesskey): bool
    {
        if ($institution_access_key == $receivedAccesskey) {
            return true;
        } else {
            return false;

        }
    }
    private function validatePaymentSignature($receivedSignature, $payment_conf_signature){
        if ($receivedSignature == $payment_conf_signature) {
            return true;
        } else {
            return false;
        }
    }
    private function getPaymentProvider($currency): array
    {

        if ($currency == 'TZS') {
            $provider = CRDB_CARD_ONLINE_TZS;
            $currency = TZS;
            $data = [
                'provider_id' => $provider,
                'currency_id' => $currency
            ];
            return ['status' => true, 'code' => 200, 'data'=> $data];
        } elseif ($currency == 'USD') {
            $provider = CRDB_CARD_ONLINE_USD;
            $currency = USD;
            $data = [
                'provider_id' => $provider,
                'currency_id' => $currency
            ];
            return ['status' => true, 'code' => 200, 'data'=> $data];
        } else {
            return ['status' => false, 'code' => 100, 'message' => 'Invalid Currency!'];
        }
    }


    private function handleCybersourceResponse($receivedAmount, $response): array
    {

        $reasonCode = $response->reason_code ?? '';
        $decision = $response->decision;
        $message = $response->message;

        $responseCodes = (New ResponseCodes())->getCode($reasonCode, $message);

        if (in_array($responseCodes['code'], [100, 110]) && $decision == 'ACCEPT') {

            // Handle success payment

            $check_provider = $this->getPaymentProvider($this->data->req_currency);

            if(!$check_provider['status']){
                return ['status'=>false, 'code'=>100, 'message'=>$check_provider['message']];
            }

            $provider_data = $check_provider['data'];
            // prepare Payment Data

            $payment_data = [
                'transaction_id' => $response->transaction_id,
                'receipt_id' => $response->transaction_id,
                'invoice_id' => $this->invoice_data['id'],
                'bill_amount' => $this->invoice_data['dbl_amount'],
                'due_amount' => $this->invoice_data['dbl_amount_due'],
                'paid_amount' => $response->req_amount,
                'currency_id' => $provider_data['currency_id'],
                'provider_id' => $provider_data['provider_id'],
                'message' => $response->message,
                'applicant_id' => $this->invoice_data['applicant_id'],
                'txt_control_number' => $this->invoice_data['txt_control_number'],
                'txt_invoice_number' => $this->invoice_data['txt_invoice_number'],
                'bill_type' => $this->invoice_data['bill_type'],
            ];

            $postPayment = (new PostPayment($payment_data, $this->db))->index();

            if (!$postPayment['status']) {
                return ['status'=>false, 'code'=>100, 'message'=>$postPayment['message']];
            }

            return ['status'=>true, 'code'=>200, 'message'=>$message];

        }

        // Handle failed payment
        $save_failed_payment = $this->saveFailedPayment($response);

        return ['status'=>false, 'code'=>100, 'message'=>$message];

    }
    private function renderForm($code,$data,$message)
    {
        header('Content-Type: text/html; charset=utf-8');

        include '../View/complete_response.php';

        exit;
    }

    public function logData($payload): void
    {
        $logDir = 'payment/' . $this->dir . '/' . date('Y') . '/' . date('M');
        $logFile = $this->path;
        ApiLog::custom_log_payment($logDir, $logFile, $payload);

    }


}