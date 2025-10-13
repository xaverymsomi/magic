<?php

namespace Modules\Core\Payments\Bank\Crdb;

use AllowDynamicProperties;
use Core\database\QueryBuilder as DB;
use Core\Request;
use Libs\ApiLib;
use Libs\ApiLog;
use Libs\Lib;

 class CrdbController
{
     private $dir = 'CRDB/';
     private $path = 'payment_config';

    protected $db;
    protected $validate_data;
     private $lib;

    public function __construct()
    {
        $this->db = new DB;
        $this->lib = new ApiLib();
    }

    public function index(): void
    {

        //Get submitted application Data
        $data = Request::getBody();

        $this->logData(['received Application data' => $data]);
        //Validate data
        $required_params = [
            'application_reference' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'amount' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'currency' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'is_checked' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS]
        ];

        $validator = ApiLib::validator($data, $required_params);
        if (!$validator['status']) {
            ApiLib::handleResponse("Missing key: " . $validator['message'],null, 100);
        }

        $this->validate_data = $validator['data'];


        $form = $this->preparePaymentForm();

        echo $form;
    }

    private function preparePaymentForm()
    {
        $accessKey = CYBERSOURCE_PAYMENT_ACCESS_KEY;
        $profileId = CYBERSOURCE_PAYMENT_PROFILE_ID;
        $secretKey = CYBERSOURCE_PAYMENT_SECRET_KEY;
        $locale = CYBERSOURCE_PAYMENT_LOCALE;

        $currency = strtoupper($this->validate_data['currency']);
        $reference = $this->validate_data['application_reference'];
        $amount = $this->validate_data['amount'];


        $transactionUuid = uniqid();
        $signedDateTime = gmdate('Y-m-d\TH:i:s\Z');
        $transactionType = 'sale';

        $fields = [
            'access_key' => $accessKey,
            'profile_id' => $profileId,
            'transaction_uuid' => $transactionUuid,
            'signed_field_names' => 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency',
            'unsigned_field_names' => '',
            'signed_date_time' => $signedDateTime,
            'locale' => $locale,
            'transaction_type' => $transactionType,
            'reference_number' => $reference,
            'amount' => $amount,
            'currency' => $currency,
        ];

        $signature = $this->signFields($fields, $secretKey);

        $log_data = [
            'txt_access_key' => $fields['access_key'],
            'txt_profile_id' => $fields['profile_id'],
            'txt_transaction_uuid' => $fields['transaction_uuid'],
            'txt_signed_field_names' => $fields['signed_field_names'],
            'txt_unsigned_field_names' => $fields['unsigned_field_names'],
            'dat_signed_date_time' => $fields['signed_date_time'],
            'txt_locale' => $fields['locale'],
            'txt_transaction_type' => $fields['transaction_type'],
            'txt_reference_number' => $fields['reference_number'],
            'dbl_amount' => $fields['amount'],
            'txt_currency' => $fields['currency'],
            'txt_signature' => $signature,
            'int_used' => 0
        ];
        $this->logData(['push data to cybersource' => $log_data], true);

        $referenceNumberExist = $this->db->select(
            "SELECT * FROM mx_payment_configuration_logs
                                                            WHERE txt_reference_number = :reference",
            [':reference' => $log_data['txt_reference_number']]
        );

        if(!empty($referenceNumberExist)){
            $this->db->update('mx_payment_configuration_logs', $log_data, $referenceNumberExist[0]['id']);
        }else{
            $save_payment_configuration_logs = $this->db->save('mx_payment_configuration_logs', $log_data, 'PAYMENT_CONFIGURATION_MODEL');
            if (!$save_payment_configuration_logs) {
                return false;
            }
        }

        return $this->renderForm($fields, $signature);
    }

    private function signFields($fields, $secretKey): string
    {
        $dataToSign = [];
        foreach (explode(',', $fields['signed_field_names']) as $key) {
            $dataToSign[] = $key . "=" . $fields[$key];
        }

        $data = implode(',', $dataToSign);
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }
    private function renderForm($fields, $signature): string
    {
        $actionUrl = CYBERSOURCE_PAYMENT_URL;

        $form = '<form method="POST" action="' . $actionUrl . '">';
        foreach ($fields as $name => $value) {
            $form .= '<input type="hidden" name="' . $name . '" value="' . $value . '">' . PHP_EOL;
        }
        $form .= '<input type="hidden" name="signature" value="' . $signature . '">' . PHP_EOL;
        $form .= '<button type="submit">Pay Now</button>';
        $form .= '</form>';

        return $form;
    }


     public function logData($payload, $end = false): void
     {
         $logDir = 'payment/' . $this->dir . '/' . date('Y') . '/' . date('M');
         $logFile = $this->path;
         ApiLog::custom_log_payment($logDir, $logFile, $payload, $end);
     }
}
