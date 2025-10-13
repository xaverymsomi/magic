<?php

/**
 *
 */

namespace Libs;

class Auth
{

    public static function checkLogin()
    {
        if (!isset($_SESSION)) {
            session_start();
            $logged = false;

            if (isset($_SESSION['rp_signed_in'])) {
                $logged = $_SESSION['rp_signed_in'];
            }

            if ($logged == false) {
                session_destroy();
                header('location: ' . URL . '/login');
                exit;
            }
        }
    }

    public static function isLogged()
    {
        if (array_key_exists('rp_signed_in', $_SESSION)) {
            return true;
        } else {
            return false;
        }
    }

    public static function user()
    {
        if (array_key_exists('rp_signed_in', $_SESSION)) {
            return $_SESSION;
        } else {
            return false;
        }
    }

}
