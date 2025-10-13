<?php

namespace Libs;

// include APP_BASE_PATH . '/vendor/autoload.php';

use Mailgun\Mailgun;
use Core\database\DB;

class MXMailGun
{
    public function sendEmail($source, $recipient, $attachment, $labels, $values, $cc = null)
    {
        $log = new ApiLog();
        try {
            $mgClient = Mailgun::create(MAILGUN_API_KEY, MAILGUN_API_HOSTNAME);
            echo 234;
            exit;

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
                // 'to'    => 'joelvankibona@gmail.com',
                'from' => $setting['txt_from_name'] . ' ' . $setting['txt_from_email'],
                // 'from'    => 'Admin <noreply@covid.rahisiweb.co.tz>',
                //                'cc'      => 'bix2499@gmail.com',
                //                'bcc'     => $email_body['txt_bcc'],
                'subject' => $email_body['txt_subject'],
                // 'text'    => 'Testing some Mailgun awesomness!',
                'html' => $email_body['tar_email_body'],

            );

            if ($attachment != null) {
                $params['attachment'] = [['filePath' => $attachment]];
            }

            if ($cc != null) {
                $params['cc'] = $cc;
            }

//            if ($email_body['txt_bcc']) {
//                $params['bcc'] = $email_body['txt_bcc'];
//            }

            // Make the call to the client.

            $RESULT = $mgClient->messages()->send($domain, $params);
            // echo json_encode($RESULT);

            return 200;
        } catch (Exception $e) {
            $log->emailErr($e->getMessage());
        }

        return 100;
    }

    private function getMailConfig()
    {
        // $db = new Database();
        $sql = "SELECT * from mx_email";
        $result = DB::select($sql);
        return $result[0];
    }

    private function getContent($source_id)
    {
        $query = "SELECT * FROM mx_email_content WHERE opt_mx_source_id = $source_id";
        // $db = new Database();
        $content = DB::select($query);
        if (count($content) > 0) {
            return $content[0];
        }
        return null;
    }
}
