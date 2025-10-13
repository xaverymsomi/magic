<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * Author: Said Makono
 * Date : 24/7/2019
 */

namespace  Libs;

Class CaptchaLib {

    private $permitted_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private $image;
    private $captcha_string = '';
    private $time_out = 30;


    private function generateString($strength = 5) {

        $input = $this->permitted_chars;

        $input_length = strlen($input);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }

    private function renderBackground() {
        $image = imagecreatetruecolor(170, 30);

        imageantialias($image, true);

        $colors = [];

        $red =  rand(125, 175);
        $green = rand(125, 175);
        $blue =  rand(125, 175);

        for ($i = 0; $i < 5; $i++) {
            $colors[] = imagecolorallocate($image, $red - 20 * $i, $green - 20 * $i, $blue - 20 * $i);
        }

        imagefill($image, 0, 0, $colors[0]);

        for($i = 0; $i < 10; $i++) {
          imagesetthickness($image, rand(2, 10));
          $rect_color = $colors[rand(1, 4)];
          imagerectangle($image, rand(-10, 190), rand(-10, 10), rand(-10, 190), rand(40, 60), $rect_color);
        }

        $this->image = $image;
    }

    private function renderString() {
        $black = imagecolorallocate($this->image, 0, 0, 0);
        $white = imagecolorallocate($this->image, 255, 255, 255);
        $textcolors = [$black, $white];
        
        $string_length = 6;
        $captcha_string = $this->generateString($string_length);

        $this->captcha_string = $captcha_string;
        for ($i = 0; $i < $string_length; $i++) {
            $letter_space = round(170 / $string_length);
            $initial = 15;
            
            imagestring($this->image, 63, $initial + $i*$letter_space, rand(5, 10), $captcha_string[$i], $textcolors[rand(0,1)] );
        }
        header('Content-type: image/png');
        imagepng($this->image);
        imagedestroy($this->image);
    }

    public function generateCapture() {
        $this->renderBackground();
        $this->renderString();

        date_default_timezone_set('Africa/Nairobi');
        $date = date('m/d/Y h:i:s a');

        unset($_SESSION['capture_activity']);
        unset($_SESSION['captcha_string']);

        $_SESSION['capture_activity'] = $date;
        $_SESSION['captcha_string'] = $this->captcha_string;
    }

    public function testCapture($input_string) {
        $response = ['status' => 404, 'message' => 'unknow reason'];

        if (isset($_SESSION['capture_activity']) && isset($_SESSION['captcha_string'])) {
            // check if capture set within required time
            date_default_timezone_set('Africa/Nairobi');
            $date = date('m/d/Y h:i:s a');
            $date_capture = $_SESSION['capture_activity'];

            // get minutes difference
            $diff = strtotime($date) - strtotime($date_capture);

            // check if exceed timeout
            if (round(abs($diff) / 60, 2) > $this->time_out) {
                $response['status'] = 404;
                $response['message'] = 'time out';

                goto break_point;
            }

            if ($_SESSION['captcha_string'] == $input_string) {
                $response['status'] = 200;
                $response['message'] = 'capture passed';
            } else {
                $response['status'] = 404;
                $response['message'] = 'capture failed';
            }
        }

        break_point:
        return $response;
    }

    public function refreshCapture() {
        unset($_SESSION['capture_activity']);
        unset($_SESSION['captcha_string']);

        return $this->generateCapture();
    }

}
