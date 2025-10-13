<?php

namespace Modules\Core\Payments\PaymentComplete\PaymentCompletions;

use Core\database\QueryBuilder as DB;
use Modules\Core\payments\PaymentComplete\SavePermit;

class ApplicationFeePaymentCompletion
{

    protected DB $db;
    private $echo_log;
    private $control_number;
    function __construct($control_number, $db, $echo_log = false)
    {
        $this->control_number = $control_number;
        $this->echo_log = $echo_log;
        $this->db = $db;
    }

    public function index()
    {
        //Get Invoice Details
        $invoice = $this->getInvoiceDetails();

        $update_application_status = $this->updateApplication($invoice);

        if (!$update_application_status) {
            return false;
        }

        return true;
    }
    private function getInvoiceDetails()
    {

        return $this->db->select("SELECT
                mx_invoice.*
            FROM mx_invoice 
            WHERE
            txt_control_number =:control_number", [':control_number' => $this->control_number])[0];
    }

    private function updateApplication($invoice): array
    {

        $application_id = $invoice['opt_mx_application_id'];

        $data = [
            'opt_mx_application_status_id' => APPROVED_APPLICATION
        ];


        $update_application = $this->db->update('mx_application', $data, $application_id);
        if (!$update_application) {
            return ['status' => false, 'code' => 100, 'message' => 'Failed to update application status'];
        }


        return ['status' => true];
    }
}