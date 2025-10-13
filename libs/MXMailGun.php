<?php

namespace Libs;

include MX17_APP_ROOT . '/vendor/autoload.php';

use Exception;
use Mailgun\Mailgun;

class MXMailGun
{
    public function sendEmail($source, $recipient, $attachment, $labels, $values, $cc = null) : int
    {
        try {
            $mgClient = Mailgun::create(MAILGUN_API_KEY, MAILGUN_API_HOSTNAME);

            $setting = $this->getMailConfig();

            $domain = MAILGUN_DOMAIN;
            $content = $this->getContent($source);
            $email_body = "";
            if (count($values) > 0 && count($labels) > 0) {
                $email_body = str_replace($labels, $values, $content);
            } else {
                $email_body = $content;
            }

            $params = array(
                'to' => $recipient,
                'from' => $setting['txt_from_name'] . ' ' . $setting['txt_from_email'],
                'subject' => $email_body['txt_subject'],
                'html' => $email_body['tar_email_body'],

            );

            if ($attachment != null) {
                $params['attachment'] = [['filePath' => $attachment]];
            }

            if ($cc != null) {
                $params['cc'] = $cc;
            }

            if ($email_body['txt_bcc']) {
                $params['bcc'] = $email_body['txt_bcc'];
            }

            $msg = PHP_EOL . 'Source: ' . $source . PHP_EOL;
            $msg .= 'Params: [' . json_encode($params) . ']' . PHP_EOL;
            $msg .= 'Recipient: ' . $recipient . PHP_EOL;
            $msg .= 'Attachment: ' . $attachment . PHP_EOL;
            $msg .= 'Body: [' . json_encode($email_body) . ']' . PHP_EOL;
            $msg .= '***********************************************************************************************************************' . PHP_EOL;
            Log::logEmail($msg);

            // Make the call to the client.
            $result = $mgClient->messages()->send($domain, $params);
            Log::sysLog('EMAIL RESPONSE: ' . json_encode($result));
            return 200;

        } catch (Exception $e) {
            Log::sysErr('Email error at ' . __METHOD__);
            Log::emailErr(['message' => $e->getMessage(), 'trace' => $e->getTrace()]);
            return 100;
        }
    }

    private function getMailConfig()
    {
        $db = new Database();
        $sql = "SELECT * from mx_email";
        $result = $db->select($sql);
        return $result[0];
    }

    private function getContent($source_id)
    {
        $query = "SELECT * FROM mx_email_content WHERE opt_mx_source_id = $source_id";
        $db = new Database();
        $content = $db->select($query);
        if (count($content) > 0) {
            return $content[0];
        }
        return null;
    }
}