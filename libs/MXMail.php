<?php

namespace Libs;

/**
 * Description of MXMail
 *
 * @author abdirahmanhassan
 */

use Libs\Log;
use Libs\Database;
use PHPMailer\PHPMailer\PHPMailer;

include MX17_APP_ROOT . '/vendor/autoload.php';

class MXMail
{
    public $mail;

    function __construct()
    {
        $this->mail = $this->init();
    }

    function init()
    {
        $setting = $this->getMailConfig();
        //instantiating phpMailer object and assign settings from db
        $mail = new PHPMailer();
        $mail->IsSMTP(); // telling the class to use SMTP
        $mail->Host = $setting['txt_host'];  //SMTP server
        $mail->SMTPDebug = false; //$setting['int_smtp_debug'];
        $mail->SMTPSecure = $setting['txt_smtp_secure'];
        if ($setting['txt_smtp_auth'] == 'true') {
            $mail->SMTPAuth = true;                // enable SMTP authentication
        } else {
            $mail->SMTPAuth = false;                // enable SMTP authentication
        }
        $mail->Port = $setting['int_port']; // set the SMTP port 
        $mail->Username = $setting['txt_username']; // SMTP account username
        $mail->Password = $setting['password'];  // SMTP account password
        $mail->From = $setting['txt_from_email'];
        $mail->FromName = $setting['txt_from_name'];
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        return $mail;
    }

    private function getMailConfig()
    {
        $db = new Database();
        $sql = "SELECT * from mx_email";
        $result = $db->select($sql);
        return $result[0];
    }

    function sendEmail($source, $recipient, $attachment, $labels, $values)
    {
        try {
            $content = $this->getContent($source);

            if (count($values) > 0 && count($labels) > 0) {
                $email_body = str_replace($labels, $values, $content);
            } else {
                $email_body = $content;
            }

            $msg = PHP_EOL . 'Source: '  . $source . PHP_EOL;
            $msg .= 'Recipient: '  . $recipient . PHP_EOL;
            $msg .= 'Attachment: '  . $attachment . PHP_EOL;
            $msg .= 'Body: ['  . json_encode($email_body) . ']' . PHP_EOL;
            $msg .= '***********************************************************************************************************************' . PHP_EOL;
            Log::logEmail($msg);

            $this->bindContents($attachment, $email_body, $recipient);
            $this->mail->send();
            return 2;
        } catch (phpmailerException $e) {
            Log::emailErr($e->getMessage());
        }
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

    function bindContents($attachment, $content, $recipient) : void
    {
        if ($attachment != null) {
            $this->mail->addAttachment($attachment);
        }
        $this->mail->isHTML(true);
        $to_email_add = explode(',', $recipient);
        foreach ($to_email_add as $email) {
            $this->mail->addAddress($email);
        }
        $this->mail->Subject = $content['txt_subject'];
        $this->mail->Body = $content['tar_email_body'];
        $this->mail->AltBody = $content['tar_email_body'];
    }

}
