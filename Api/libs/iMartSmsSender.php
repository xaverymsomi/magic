<?php

namespace Libs;

use Exception;

/**
 * Description of SmsSender
 *
 * @author Bagashy
 */
class iMartSmsSender
{
    var $host;
    /*     * Username that is to be used for submission */
    var $strToken;
    /*     * password that is to be used along with username */
    var $strSender;
    /*     * Message content that is to be transmitted */
    var $strMessage;
    /*     * Mobile No is to be transmitted. */
    var $strMobile;
    var $strCampain;

    //Constructor.. 
    function __construct($host, $token, $campain, $sender, $message, $mobile)
    {
        $this->host = $host;
        $this->strToken = $token;
        $this->strSender = $sender;
        $this->strMessage = $message;
        $this->strMobile = $mobile;
        $this->strCampain = $campain;
    }

    public function Submit()
    {
        try {
            $url = $this->host . "/app/smsapi/index.php";
            $parse_url = $this->sendToProvider($url);
            return $parse_url;
        } catch (Exception $e) {
            ApiLib::handleResponse('IMART-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        }
    }


    function sendToProvider($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "key=" . $this->strToken . "&campaign=" . $this->strCampain . "&routeid=8&type=text&contacts=" . $this->strMobile . "&senderid=" . $this->strSender . "&msg=" . urlencode($this->strMessage));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;


        //$curl_handle = curl_init();
        //curl_setopt($curl_handle, CURLOPT_URL,$url);
        //curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
        //curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($curl_handle, CURLOPT_USERAGENT, 'SMS');
        //return $query = curl_exec($curl_handle);
        //echo $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        //curl_close($curl_handle);
    }
}

/**$host = "http://smsportal.imartgroup.co.tz";
 * $token = "25F495BDAE827C";
 * $sender = "ZAWA";
 * $message = "Nd Mteja wa ZAWA, bili yako ya maji kwa mwezi wa FEB 2018 ni TSH 0 kwenye akaunti no: 53-1-071890. Salio lako ni TSH 20600. Huna ulazima kulipia bili hii. Asante sana";
 * $mobile = "255776936000";
 *
 * $sms = new iMartSMS($host,$token, $sender, $message, $mobile);
 * echo $sms->Submit();**/
