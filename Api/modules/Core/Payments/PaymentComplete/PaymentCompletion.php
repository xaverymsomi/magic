<?php

namespace Modules\Core\Payments\PaymentComplete;

use Core\database\QueryBuilder as DB;
use Libs\ApiLog;
use Modules\Core\Payments\PaymentComplete\PaymentCompletions\ApplicationFeePaymentCompletion;

class PaymentCompletion
{

    private $control_number;
    private $invoice_number;
    private mixed $bill_type;
    private DB $db;
    public function __construct($invoice, $db)
    {
        $this->control_number = $invoice['control_number'];
        $this->invoice_number = $invoice['bill_number'];
        $this->bill_type = $invoice['bill_type'];
        $this->db = $db;
    }

    public function index(){
        return $this->checkControlNumberInitials();
    }

    private function checkControlNumberInitials()
    {

        $invoice_number = $this->invoice_number;
        $control_number = $this->control_number;

        $invoice = $this->getInvoiceDetails();
        //Check Invoice Number Initials
        $initials = substr($invoice_number, 0, 3);

        switch ($initials) {

            case 'WLT':
                $complete = (new ApplicationFeePaymentCompletion($control_number, $this->db))->index();
                break;
            default:
                $complete = false;
        }

        $payment_status = 'COMPLETE';
        if($complete){
//            $this->sendNotification($payment_status);
        }

        return $complete;

    }

    private function getInvoiceDetails()
    {

        return $this->db->select("SELECT * FROM mx_invoice
                    WHERE txt_control_number = :control_number",
            [':control_number' => $this->control_number])[0];
    }

    private function sendNotification($payment_status): array
    {
        $fetch_notification_params = $this->db->select(
            "
                SELECT
                    CONCAT(mx_applicant.txt_first_name, ' ', mx_applicant.txt_middle_name, ' ',
                           mx_applicant.txt_last_name) AS applicant,
                    mx_application.txt_reference AS application_reference,
                    mx_institution.id AS institution_id,
                    mx_institution.txt_name AS institution_name,
                    mx_institution.txt_contact_phone AS institution_phone,
                    mx_institution.txt_contact_email AS institution_email,
                    mx_login_credential.id AS institution_login_credential_id
                FROM mx_invoice
                    JOIN mx_application ON mx_invoice.opt_mx_application_id = mx_application.id
                    JOIN mx_applicant ON mx_application.entity_id = mx_applicant.id
                    JOIN mx_applicant_institution ON mx_applicant_institution.opt_mx_applicant_id = mx_applicant.id
                    JOIN mx_institution ON mx_applicant_institution.opt_mx_institution_id = mx_institution.id
                    JOIN mx_login_credential ON mx_institution.id = mx_login_credential.user_id AND mx_login_credential.txt_domain = 'mx_institution'
                WHERE mx_invoice.txt_control_number = :txt_control_number
            ",
            [':txt_control_number' => $this->control_number]
        );


        if (empty($fetch_notification_params) || empty($fetch_notification_params[0])) {
            return ['status' => false, 'code' => 200, 'message' => 'Payment posted successfully, but notification was not sent'];
        } else {
            $notification_params = $fetch_notification_params[0];

            $submitted_mobile = filter_var($notification_params['institution_phone'], FILTER_SANITIZE_SPECIAL_CHARS);

            $labels = ['_name', '_controlNumber', '_invoiceType'];
            $values = [$notification_params['institution_name'], $this->control_number, strtoupper($this->bill_type)];

            if ($submitted_mobile != $notification_params['institution_phone']) {
                $notification_data_1 = [
                    'source_id' => 41,
                    'labels' => $labels,
                    'values' => $values,
                    'mobile' => $submitted_mobile,
                    'email' => $notification_params['institution_email'],
                    'notification_title' => 'Payment Notification',
                    'notification_message' => 'Dear ' . $notification_params['institution_name'] . ', a ' . $payment_status . ' payment has just been paid for the control number  ' . $this->control_number,
                    'notification_type' => SPECIFIC_NOTIFICATION,
                    'login_credential_id' => $notification_params['institution_login_credential_id'],
                ];

                $notify_1 = $this->saveNotificationData($notification_data_1,$this->db);
                if (!$notify_1) {
                    ApiLog::sysLog('Failed to save notification');
                    return ['status' => true, 'code' => 200, 'message' => 'Payment posted successfully, but notification was not sent'];
                }
            }

            $notification_data_2 = [
                'source_id' => 41,
                'labels' => $labels,
                'values' => $values,
                'mobile' => $notification_params['institution_phone'],
                'email' => $notification_params['institution_email'],
                'notification_title' => 'Payment Notification',
                'notification_message' => 'Dear ' . $notification_params['institution_name'] . ', a ' . $payment_status . ' payment has just been paid for the control number  ' . $this->control_number,
                'notification_type' => SPECIFIC_NOTIFICATION,
                'login_credential_id' => $notification_params['institution_login_credential_id'],
            ];

            $notify_2 = $this->saveNotificationData($notification_data_2, $this->db);

            if (!$notify_2) {
                ApiLog::sysLog('Failed to save notification');
                return ['status' => true, 'code' => 200, 'message' => 'Payment posted successfully, but notification was not sent'];
            }
            return ['status' => true, 'code' => 200, 'message' => 'Payment posted successfully'];
        }
    }
}