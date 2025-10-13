<?php

namespace Libs;

class PhoneNumber
{
    public static function validateTzPhoneNumber($mobile)
    {
        if (strlen($mobile) == 12 && substr($mobile, 0, 3) == '255') {
            return $mobile;
        } else if (strlen($mobile) == 10 && substr($mobile, 0, 1) == '0') {
            return '255' . substr($mobile, 1);
        } else if (strlen($mobile) == 9 && substr($mobile, 0, 1) != '0') {
            return '255' . $mobile;
        } else {
            return false;
        }
    }
}
