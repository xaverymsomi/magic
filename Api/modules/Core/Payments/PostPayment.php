<?php

namespace Modules\Core\Payments;

use AllowDynamicProperties;
use Core\database\QueryBuilder as DB;
use Libs\ApiLib;
use Modules\Core\Payments\PaymentComplete\PaymentCompletion;

class PostPayment
{
    private DB $db;
    private $data;
    private $payment_id;
    private $application;

    public function __construct($data, $db)
    {
        $this->db = $db;
        $this->data = $data;
    }

    public function index(){

        //verify Payment Transaction Number
        $receipt_no = $this->verifyPaymentNo($this->data['transaction_id']);
        if (count($receipt_no) > 0) {
            return ['status' => false, 'code' => 100, 'message' => 'Transaction Number already exists'];
        }
        // check if receipt already exists
        $receipt_no = $this->verifyReceiptNo($this->data['receipt_id']);

        if (count($receipt_no) > 0) {
            return ['status' => false, 'code' => 100, 'message' => 'Receipt Number already exists'];
        }

        $save_payment = $this->savePaymentDetails();

        if (!$save_payment['status']) {
            return ['status' => false, 'code' => 100, 'message' => $save_payment['message']];
        }

        // save receipt details
        $save_receipt = $this->saveReceiptDetails();
        if (!$save_receipt['status']) {

            return ['status' => false, 'code' => 100, 'message' => $save_receipt['message']];
        }


        // update invoice status
        $update_invoice = $this->updateInvoice();
        if (!$update_invoice['status']) {
            return ['status' => false, 'code' => 100, 'message' => $save_receipt['message']];
        }


        $this->application = [
            'control_number'=>$this->data['txt_control_number'],
            'bill_number'=>$this->data['txt_invoice_number'],
            'bill_type'=>$this->data['bill_type'],

        ];

        $complete = (new PaymentCompletion($this->application, $this->db))->index();

        if(!$complete){
            return ['status'=>false, 'code'=>100, 'message'=>"Failed To Complete Payment"];
        }

        return ['status' => true, 'code' => 200, 'message' => 'Payment posted successfully'];

    }

    private function verifyPaymentNo($rtransaction_no)
    {

        $sql = "SELECT * FROM mx_payment WHERE txt_transaction_number = :transaction_no";
        $params = [
            ':transaction_no' => $rtransaction_no
        ];
        return $this->db->select($sql, $params);
    }

    private function verifyReceiptNo($receipt_no)
    {

        $sql = "SELECT * FROM mx_receipt WHERE txt_receipt_number = :receipt_no";
        $params = [
            ':receipt_no' => $receipt_no
        ];
        return $this->db->select($sql, $params);
    }
    private function savePaymentDetails(): array
    {
        // prepare data
        $data = [
            'opt_mx_invoice_id' => filter_var($this->data['invoice_id'], FILTER_SANITIZE_NUMBER_INT),
            'txt_transaction_number' => filter_var($this->data['transaction_id'], FILTER_SANITIZE_SPECIAL_CHARS),
            'dat_paid_date' => date('Y-m-d H:i:s'),
            'dbl_amount' => filter_var($this->data['paid_amount'], FILTER_VALIDATE_FLOAT),
            'opt_mx_currency_id' => filter_var($this->data['paid_amount'], FILTER_SANITIZE_NUMBER_INT),
            'opt_mx_payment_setup_id' => filter_var($this->data['currency_id'], FILTER_SANITIZE_NUMBER_INT),
            'txt_payment_reference_number' => filter_var($this->generateUniquePaymentReferenceNumber(), FILTER_SANITIZE_SPECIAL_CHARS),
            'opt_mx_payment_status_id' => filter_var(SUCCESSFUL_PAYMENT, FILTER_SANITIZE_NUMBER_INT),
            'txt_remarks' => $this->data['message'],
            'txt_third_party_reference_number' => filter_var($this->data['transaction_id'], FILTER_SANITIZE_SPECIAL_CHARS),
            'int_added_by' => filter_var($this->data['applicant_id'], FILTER_SANITIZE_NUMBER_INT),
            'dat_added_date' => date('Y-m-d H:i:s'),
        ];

        // save payment details
        $save_payment_data = $this->db->save('mx_payment', $data, 'PAYMENT_MODEL');
        if (!$save_payment_data) {
            return ['status' => false, 'code' => 100, 'message' => 'Currency not found'];
        }

        $this->payment_id = $this->db->last_id();

        return ['status' => true, 'payment_id' => $this->payment_id];
    }

    private function saveReceiptDetails(): array
    {
        // prepare data
        $data = [
            'txt_receipt_number' => filter_var($this->data['receipt_id'], FILTER_SANITIZE_SPECIAL_CHARS),
            'opt_mx_payment_id' => $this->payment_id,
            'dbl_amount' => filter_var($this->data['paid_amount'], FILTER_VALIDATE_FLOAT),
            'opt_mx_currency_id' => filter_var($this->data['currency_id'], FILTER_SANITIZE_NUMBER_INT),
            'dat_paid_date' => date('Y-m-d'),
            'tim_paid_time' => date('H:i:s'),
            'int_added_by' => filter_var($this->data['applicant_id'], FILTER_SANITIZE_NUMBER_INT),
            'dat_added_date' => date('Y-m-d H:i:s'),
        ];

        // save receipt details
        $save_receipt = $this->db->save('mx_receipt', $data, 'RECEIPT_MODEL');
        if (!$save_receipt) {
            return ['status' => false, 'code' => 100, 'message' => 'Failed to save receipt'];
        }

        return ['status' => true];
    }

    private function updateInvoice(): array
    {

        $current_amt_due = $this->data['due_amount'];

        $amt_due = $current_amt_due - $this->data['paid_amount'];
        if ($current_amt_due > $this->data['paid_amount']) {
            $invoice_status = PAID_INVOICE;
        } elseif ($current_amt_due < $this->data['paid_amount']) {
            $invoice_status = PARTIAL_INVOICE;
        } else {
            $invoice_status = PAID_INVOICE;
        }


        $update_invoice = 'UPDATE mx_invoice SET opt_mx_invoice_status_id = :invoice_status_id, dbl_amount_due = :amount_due WHERE id = :invoice_id';
        $stmt = $this->db->prepare($update_invoice);
        $result = $stmt->execute([':amount_due' => $amt_due, ':invoice_status_id' => $invoice_status, ':invoice_id' => $this->application['id']]);
        if (!$result) {
            return ['status' => false, 'code' => 100, 'message' => 'Failed to update invoice'];
        }

        return ['status' => true, 'invoice_status' => $invoice_status];
    }

    private function generateUniquePaymentReferenceNumber(): string
    {
        do {
            $number = microtime(true);
            $reference = "PRN" . str_replace(".", "", $number);
            $result = $this->checkExistingUniquePaymentReference($reference);
        } while ($result);

        return $reference;
    }
    private function checkExistingUniquePaymentReference($reference): bool
    {
        $sql = "SELECT * FROM mx_payment WHERE txt_payment_reference_number = :reference ";
        $result = $this->db->select($sql, [':reference' => $reference]);
        if ($result) {
            return true;
        }
        return false;
    }
}