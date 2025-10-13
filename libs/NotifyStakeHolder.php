<?php


namespace Libs;
use Libs\MXMailGun;
use Libs\SmsSender;

class NotifyStakeHolder
{
    private $notification;

    public function __construct($notification){
        $this->notification = $notification;
    }

    public function init(){
        //Check notification type
        if ($this->notification['type'] == 'sms'){
            $this->sendSMS();
        }
        else if ($this->notification['type'] == 'email'){
            $this->sendEmail();
        }
    }

    private function sendSMS(){
        $sms = new SmsSender();
        $sms->sendTemplateSMS($this->notification['source'],$this->notification['mobile'],$this->notification['labels'],$this->notification['values']);
    }

    private function sendEmail(){
        $email = new MXMailGun();
        $email->sendEmail($this->notification['source'],$this->notification['email'],$this->notification['attachment'],$this->notification['labels'],$this->notification['values'],'');
    }
}