<?php
namespace Modules\Login;

use Libs\Auth;
use Libs\CaptchaLib;
use Libs\Controller;
use Libs\Log;

class Login extends Controller
{
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Login_Model();
        Auth::checkLogin();
    }

    public function index() : void
    {
        $this->view->title = 'Login';
        $this->render('index');
    }

    public function get_captcha() : void
    {
        $cap = new CaptchaLib();
        $cap->generateCapture();
        unset($cap);
    }

    public function login() : void
    {
        Log::sysLog('Initiating Login Sequence');
        $e = filter_input(INPUT_POST, 'email');
        $p = filter_input(INPUT_POST, 'password');
        $return_url = filter_input(INPUT_GET, 'return_url');
        $captcha = filter_input(INPUT_POST, 'captcha');

        $this->model->initiateLogin($e, $p, $return_url, $captcha);
    }

    public function recover() : void
    {
        Log::sysLog('Initiating Recovering Sequence');
        $e = filter_input(INPUT_POST, 'email');

        $this->model->recover($e);
    }
}
