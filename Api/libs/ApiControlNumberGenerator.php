<?php

namespace Libs;

class ApiControlNumberGenerator
{
    public static string $prefix = '60';

    public static function getInvoiceDetails(string $service_type, int $invoice_type): array
    {
        $tmp = [];

        $invoice_number = self::generateInvoiceNumber();
        $inv_type = filter_var($invoice_type, FILTER_SANITIZE_NUMBER_INT);
        $price_config = ApiSystemAmount::getAmount($invoice_type);
        if ($price_config['amount'] == 0 || empty($price_config['currency'])) {
            ApiLog::sysLog('Amount was 0 or the currency did not match');
            return [];
        }

        $amount = $price_config['amount'];
        $currency = $price_config['currency'];

        switch ($invoice_type) {
            case LONG_TERM_WP_FEE:
                $tmp = [
                    'control_number' => self::longTermWPFee(),
                    'invoice_number' => $service_type . 'LT' . $invoice_number,
                    'invoice_type' => $inv_type,
                    'amount' => $amount,
                    'currency' => $currency,
                ];
                break;
            case RENEWED_WP_FEE:
                $tmp = [
                    'control_number' => self::renewedWPFee(),
                    'invoice_number' => $service_type . 'RN' . $invoice_number,
                    'invoice_type' => $inv_type,
                    'amount' => $amount,
                    'currency' => $currency,
                ];
                break;
            case CONTRACT_FEE:
                $tmp = [
                    'control_number' => self::contractFee(),
                    'invoice_number' => $service_type . 'CN' . $invoice_number,
                    'invoice_type' => $inv_type,
                    'amount' => $amount,
                    'currency' => $currency,
                ];
                break;
            case TEMPORARY_WP_FEE:
                $tmp = [
                    'control_number' => self::temporaryWPFee(),
                    'invoice_number' => $service_type . 'TM' . $invoice_number,
                    'invoice_type' => $inv_type,
                    'amount' => $amount,
                    'currency' => $currency,
                ];
                break;
            case FOREIGNER_MARRIAGE_WP_FEE:
                $tmp = [
                    'control_number' => self::foreignerMarriageWPFee(),
                    'invoice_number' => $service_type . 'FR' . $invoice_number,
                    'invoice_type' => $inv_type,
                    'amount' => $amount,
                    'currency' => $currency,
                ];
                break;
            case EXEMPTION_WP_RENEWAL_FEE:
                $tmp = [
                    'control_number' => self::exemptionWPRenewalFee(),
                    'invoice_number' => $service_type . 'EX' . $invoice_number,
                    'invoice_type' => $inv_type,
                    'amount' => $amount,
                    'currency' => $currency,
                ];
                break;
            default:
                break;
        }
        return $tmp;
    }

    private static function longTermWPFee(): string
    {
        return self::generateControlNumber('00');
    }

    private static function renewedWPFee(): string
    {
        return self::generateControlNumber('01');
    }

    private static function contractFee(): string
    {
        return self::generateControlNumber('02');
    }

    private static function temporaryWPFee(): string
    {
        return self::generateControlNumber('03');
    }

    private static function foreignerMarriageWPFee(): string
    {
        return self::generateControlNumber('04');
    }

    private static function exemptionWPRenewalFee(): string
    {
        return self::generateControlNumber('05');
    }

    private static function generateControlNumber($control_prefix): string
    {
        $new_control_number = self::$prefix . $control_prefix . ApiLib::generateRandomNo(8);
        $control_no_check = self::control_number_check($new_control_number);
        if (!$control_no_check) {
            return self::generateControlNumber($control_prefix);
        }
        return $new_control_number;
    }

    private static function control_number_check($control_number): bool
    {
        $check = (new \Core\Controller())->getRecordByFieldName('mx_invoice', 'txt_control_number', $control_number, true);
        if (empty($check)) {
            return true;
        }
        return false;
    }

    private static function generateInvoiceNumber(): string
    {
        $new_invoice_number = ApiLib::generateRandomNo(13);
        $invoice_no_check = self::invoice_number_check($new_invoice_number);
        if (!$invoice_no_check) {
            return self::generateInvoiceNumber();
        }
        return $new_invoice_number;
    }

    private static function invoice_number_check($invoice_number): bool
    {
        $check = (new \Core\Controller())->getRecordByFieldName('mx_invoice', 'txt_invoice_number', $invoice_number, true);
        if (empty($check)) {
            return true;
        }
        return false;
    }
}