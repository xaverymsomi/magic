<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Notification
 *
 * @author abdirahmanhassan
 */
namespace Libs;

class Notification {

    public static function show($class) {
        $title = '<b class="notification-title">' . str_replace("_Model", "", $class) . '</b>';
        if (Session::get('returned') != "") {
            //global $status_notifier;
            $returned_value = $_SESSION['returned'];
            if ($returned_value == 200) {
                $msg = '<p class="notification-msg">' . $title . ' data saved successfully.</p>';
                $type = "success";
                $icon = "pe pe-7s-check fa-2x";
            } elseif ($returned_value == 100) {
                $msg = '<p class="notification-msg">Sorry! ' . $title . ' data could not be saved.</p>';
                $type = "danger";
                $icon = "pe-7s-close fa-2x";
            } elseif ($returned_value == 201) {
                $msg = '<p class="notification-msg">' . $title .  ' data updated successfully.</p>';
                $type = "success";
                $icon = "pe pe-7s-check fa-2x";
            } elseif ($returned_value == 101) {
                $msg = '<p class="notification-msg"> Sorry! ' . $title . ' data could not be updated.</p>';
                $type = "danger";
                $icon = "pe pe-7s-close fa-2x";
            }elseif ($returned_value == 3000) {
                $msg = '<p class="notification-msg"> Sorry! ' . $title . ' data validation failed.</p>';
                $type = "danger";
                $icon = "pe pe-7s-close fa-2x";
            }elseif ($returned_value == 10) {
                $msg = '<p class="notification-msg">Sorry, your email address or password is incorrect.</p>';
                $type = "danger";
                $icon = "pe pe-7s-lock fa-2x";
            }elseif ($returned_value == 1993) {
                $msg = '<p class="notification-msg">Sorry,  Please verify your not a robot!.</p>';
                $type = "danger";
                $icon = "pe pe-7s-lock fa-2x";
            }elseif ($returned_value == 5000) {
                $msg = '<p class="notification-msg">A Password recovery email has been sent successfully.</p>';
                $type = "success";
                $icon = "pe pe-7s-close fa-2x";
            }elseif ($returned_value == 6000) {
                $msg = '<p class="notification-msg">Your Password has been changed.</p>';
                $type = "success";
                $icon = "pe pe-7s-close fa-2x";
            }elseif ($returned_value == 6060) {
                $msg = '<p class="notification-msg">Sorry! Your User acount is not active.</p>';
                $type = "danger";
                $icon = "pe pe-7s-lock fa-2x";
            }elseif ($returned_value == 6061) {
                $msg = '<p class="notification-msg">Sorry! Your User account does not exist.</p>';
                $type = "danger";
                $icon = "pe pe-7s-lock fa-2x";
            }elseif ($returned_value == 6062) {
                $msg = '<p class="notification-msg">Sorry! Your User account is active in another browser, Log out to continue.</p>';
                $type = "danger";
                $icon = "pe pe-7s-lock fa-2x";
            }elseif ($returned_value == 6063) {
                $msg = '<p class="notification-msg">Sorry! Your User Passwords does not match, Try again.</p>';
                $type = "danger";
                $icon = "pe pe-7s-lock fa-2x";
            }else{
                $msg = $returned_value;
                $type = "danger";
                $icon = "pe pe-7s-lock fa-2x";
            }

            echo "<script>
                $.notify({
                        title: '".$title."',
                        message: '" . $msg . "',
                        icon:  '" . $icon . "'
                    }, {
                        delay: 4000,
                        type: '" . $type . "',
                        
                        placement: {
                            from: 'top',
                            align: 'center'
                        }
                    });               
            </script>";
            unset($_SESSION['returned']);
        }
    }

    public static function progress($action) {
        echo "<script> 
            var notify = $.notify( '" . $action . "',
                        {
                            type: 'info',
                            allow_dismiss: false,
                            showProgressbar: true
                        });

            </script>";
    }

}
