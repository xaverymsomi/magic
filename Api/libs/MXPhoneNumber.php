<?php

namespace Libs;

class MXPhoneNumber
{
    public static function validateTzPhoneNumber($mobile): array
    {
        // Remove the '+' symbol if it exists at the start
        $mobile = ltrim($mobile, '+');

        // Check if the number has 12 digits and starts with '255' followed by '6' or '7'
        if (strlen($mobile) == 12 && substr($mobile, 0, 3) == '255' && in_array(substr($mobile, 3, 1), ['6', '7'])) {
            return ['status' => true, 'mobile' => $mobile];
        }
        // Check if the number has 10 digits and starts with '0' followed by '6' or '7'
        elseif (strlen($mobile) == 10 && substr($mobile, 0, 1) == '0' && in_array(substr($mobile, 1, 1), ['6', '7'])) {
            return ['status' => true, 'mobile' => '255' . substr($mobile, 1)];
        }
        // Check if the number has 9 digits and starts directly with '6' or '7'
        elseif (strlen($mobile) == 9 && in_array(substr($mobile, 0, 1), ['6', '7'])) {
            return ['status' => true, 'mobile' => '255' . $mobile];
        }
        else {
            return ['status' => false, 'mobile' => '255' . $mobile];
        }
    }
}