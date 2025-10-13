<?php

namespace Modules\Core\Payments\Bank\Nmb;

use Core\database\QueryBuilder as DB;
use Libs\ApiLib;
use Libs\ApiLog;
use Modules\Core\Payments\PostPayment;

class NmbCallbackHandler
{
    private $dir = 'NMB/';
    private $path = 'payment_response';

    protected $db;
    protected $invoice_data;
    private $lib;
    public function __construct()
    {
        $this->db = new DB;
        $this->lib = new ApiLib();
    }

    public function index(): void
    {
        $this->db->begin();

        $response = json_decode(json_encode($_GET));
        $this->logData(['NMB-RESPONSE' => $response]);


        if (empty($response->resultIndicator)) {
            ApiLib::handleResponse('Result indicator should not be null!', null, 100);
            $this->renderForm(100, "Result indicator should not be null!");
        }

        $payment_nmb_logs = $this->db->select("SELECT * FROM mx_payment_nmb_logs WHERE mx_payment_nmb_logs.int_used = 0 AND mx_payment_nmb_logs.txt_success_indicator=:txt_success_indicator",
            [':txt_success_indicator' => $response->resultIndicator]);

        if (empty($payment_nmb_logs) || empty($payment_nmb_logs[0])) {
            ApiLib::handleResponse('Failed to get configuration data!', null, 100);
            $this->renderForm(100, "Failed to get configuration data!");
        }

        $payment_nmb_data = $payment_nmb_logs[0];
        if ($payment_nmb_data['int_used'] == 0 || $payment_nmb_data['int_used'] == null) {
            $update = [
                'int_used' => 1
            ];

            $update_sql = $this->db->update('mx_payment_nmb_logs', $update, $payment_nmb_data['id']);
            if (!$update_sql) {
                ApiLib::handleResponse('Failed to get update payment logs!', null, 100);
                $this->renderForm(100, "Failed to get update payment logs!");
            }
        } else {
            ApiLib::handleResponse('Failed to process token is already used!', null, 100);
            $this->renderForm(100, "Failed to process token is already used!");
        }

        // Get Transaction Order Details
        $getOrder = (new NmbGetTransactionOrder($payment_nmb_data, $this->db))->index();
        $this->logData(['NMB-TRANSACTION-ORDER' => $getOrder]);

        if (!$getOrder['status']) {
            ApiLib::handleResponse('Get transaction failed!', null, 100);
            $this->renderForm(100, "Get transaction failed!");
        }

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
                        'authorizationCode' => $transaction['transaction']['authorizationCode'],
                        'control_number' => $payment_nmb_data['txt_control_number']
                    ];
                }
            }
        }

        if (!$approved) {
            ApiLib::handleResponse('Transaction not approved!', null, 100);
            $this->renderForm(100, "Transaction not approved!");
        }

        $this->logData(['NMB-ORDER-RESPONSE' => $transactionDetails]);

        $invoice_sql = $this->db->select("SELECT
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
                    WHERE mx_invoice.txt_control_number =:txt_control_number",
            [':txt_control_number' => $payment_nmb_data['txt_control_number']]);

        if (empty($invoice_sql) || empty($invoice_sql[0])) {
            $this->db->rollBack();
            ApiLib::handleResponse('Invoice details not found!', null, 100);
            $this->renderForm(100, "Invoice details not found!");
        }

        $invoice = $invoice_sql[0];
        $this->invoice_data = $invoice;

        if ($invoice['dbl_amount'] != $transactionDetails['amount'] || $invoice['txt_currency'] != $transactionDetails['currency']) {
            $this->db->rollBack();
            ApiLib::handleResponse('Invalid payment amount or currency!', null, 100);
            $this->renderForm(100, "Invalid payment amount or currency!");
        }

        if ($invoice['opt_mx_invoice_status_id'] == PAID_INVOICE) {
            $this->db->rollBack();
            ApiLib::handleResponse('Invoice details already paid!', null, 100);
            $this->renderForm(100, "Invoice details already paid!");
        }

        $response_handler = $this->handlNmnbResponse($transactionDetails, $payment_nmb_data);
        if (!$response_handler['status']) {
            $this->db->rollBack();
            $this->renderForm(100, $response_handler['message']);
        }

        $this->logData('NMB-ORDER-COMPLETE', true);

        $db->commit();
        $this->renderForm(200, $response_handler['message']);

        $data = [];
        $this->logData(['received Application data' => $data]);
        // body
        $this->db->commit();
    }

    private function handlNmnbResponse($transactionDetails): array
    {
        $check_provider = $this->getPaymentProvider($transactionDetails['currency']);

        if(!$check_provider['status']){
            return ['status'=>false, 'code'=>100, 'message'=>$check_provider['message']];
        }

        $provider_data = $check_provider['data'];

        // prepare Payment Data
        $payment_data = [
            'transaction_id' => $transactionDetails['transaction_number'],
            'receipt_id' => $transactionDetails['receipt_number'],
            'invoice_id' => $this->invoice_data['id'],
            'bill_amount' => $this->invoice_data['dbl_amount'],
            'due_amount' => $this->invoice_data['dbl_amount_due'],
            'paid_amount' => $transactionDetails['amount'],
            'currency_id' => $provider_data['currency_id'],
            'provider_id' => $provider_data['provider_id'],
            'message' => "Payment Handled SuccessfulY",
            'applicant_id' => $this->invoice_data['applicant_id'],
            'txt_control_number' => $this->invoice_data['txt_control_number'],
            'txt_invoice_number' => $this->invoice_data['txt_invoice_number'],
            'bill_type' => $this->invoice_data['bill_type'],
        ];

        $postPayment = (new PostPayment($payment_data, $this->db))->index();

        if (!$postPayment['status']) {
            return ['status'=>false, 'code'=>100, 'message'=>$postPayment['message']];
        }

        return ['status' => true, 'code' => 200, 'message' => 'Payment Handled Successfully'];
    }

    private function getPaymentProvider($currency): array
    {
        switch ($currency) {
            case 'TZS':
                $provider = NMB_CARD_ONLINE_TZS;
                $currency = TZS;
                $data = [
                    'provider_id' => $provider,
                    'currency_id' => $currency
                ];
                return ['status' => true, 'code' => 200, 'data' => $data];
            case 'USD':
                $provider = NMB_CARD_ONLINE_USD;
                $currency = USD;
                $data = [
                    'provider_id' => $provider,
                    'currency_id' => $currency
                ];
                return ['status' => true, 'code' => 200, 'data' => $data];
            default:
                return ['status' => false, 'code' => 100, 'message' => 'Invalid Currency!'];
        }
    }
    private function renderForm($code, $message): void
    {
        header('Content-Type: text/html; charset=utf-8');
        include __DIR__ . '/../View/complete_response.php';
        exit;
    }
    public function logData($payload, $end = false): void
    {
        $logDir = 'payment/' . $this->dir . '/' . date('Y') . '/' . date('M');
        $logFile = $this->path;
        ApiLog::custom_log_payment($logDir, $logFile, $payload, $end);
    }
}