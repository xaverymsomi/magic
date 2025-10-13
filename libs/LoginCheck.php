<?php

namespace Libs;

class LoginCheck
{
    public function protect($url = null)
    {
        if (!Auth::isLogged() || strtolower($url) == 'bootstrap' || (Auth::isLogged() && strtolower($url) == 'login')) {
            Log::sysLog('Non-authenticated user trying to access protected page');
            if (Auth::isLogged()) {
                session_destroy();
                Session::init();
                Session::set('Forbidden', 403);
            }
            header('location: ' . URL . '/Login');
            exit;
        }
    }

    public function destroy($url = null)
    {
        if (Auth::isLogged() && strtolower($url) == 'login') {
            Log::sysLog('Authenticated user trying to access login page');
            if (Auth::isLogged()) {
                session_destroy();
                Session::init();
                Session::set('Forbidden', 403);
            }
            header('location: ' . URL . '/Login');
            exit;
        }
    }
}