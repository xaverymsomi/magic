<?php

use Libs\Notification;
use Libs\Session;

Notification::show("Login");

if (empty($_GET)) {
    $_SESSION['lang'] = 'en';
    setlocale(LC_ALL, "en_US");
} else {

    if (isset($_GET['language'])) {
        $_SESSION['lang'] = $_GET['language'];
        if ($_GET['language'] == "sw") {
            setlocale(LC_ALL, "sw_TZ");
        } else {
            setlocale(LC_ALL, "en_US");
        }
    } else {
        $_SESSION['lang'] = 'en';
        setlocale(LC_ALL, "en_US");
    }
}

if (!isset($_COOKIE['scheme'])) {
    $schemes = [];
} else {
    $schemes = unserialize($_COOKIE['scheme']);
}

$returned = Session::get('returned') != null || Session::get('returned') != '' ? Session::get('returned') : 0;
?>
<style>
    *,
    :after,
    :before {
        box-sizing: inherit;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        margin-top: 0px;
        margin-right: 0px;
        margin-bottom: 0px;
        margin-left: 0px;
        padding-top: 0px;
        padding-right: 0px;
        padding-bottom: 0px;
        padding-left: 0px;
    }


    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: white;
        background-color: #fff;
        margin-top: 0px;
        margin-right: 0px;
        margin-bottom: 0px;
        margin-left: 0px;
    }

    body,
    html {
        height: 100%;
        font-family: Poppins-Regular, sans-serif;
    }

    html {
        box-sizing: border-box;
        font-family: sans-serif;
        line-height: 1.15;
        -webkit-text-size-adjust: 100%;
        -ms-text-size-adjust: 100%;
        -ms-overflow-style: scrollbar;
        -webkit-tap-highlight-color: transparent;
        text-size-adjust: 100%;
    }


    .text-center {
        text-align: center !important;
    }

    .text-center {
        text-align: center;
    }


    [role="button"],
    a,
    area,
    button,
    input,
    label,
    select,
    summary,
    textarea {
        -ms-touch-action: manipulation;
        touch-action: manipulation;
    }

    button,
    input,
    optgroup,
    select,
    textarea {
        margin: 0;
        font-family: inherit;
        font-size: inherit;
        line-height: inherit;
        margin-top: 0px;
        margin-right: 0px;
        margin-bottom: 0px;
        margin-left: 0px;
    }

    button,
    input {
        overflow: visible;
        overflow-x: visible;
        overflow-y: visible;
    }

    input {
        outline: none;
        border: none;
        outline-color: initial;
        outline-style: none;
        outline-width: initial;
        border-top-width: initial;
        border-right-width: initial;
        border-bottom-width: initial;
        border-left-width: initial;
        border-top-style: none;
        border-right-style: none;
        border-bottom-style: none;
        border-left-style: none;
        border-top-color: initial;
        border-right-color: initial;
        border-bottom-color: initial;
        border-left-color: initial;
        border-image-source: initial;
        border-image-slice: initial;
        border-image-width: initial;
        border-image-outset: initial;
        border-image-repeat: initial;
    }

    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active {
        transition: background-color 5000s ease-in-out 0s;
    }

    /*Change text in autofill textbox*/
    input:-webkit-autofill {
        -webkit-text-fill-color: white !important;
    }

    label {
        display: inline-block;
        margin-bottom: .5rem;
    }

    label {
        margin: 0;
        display: block;
        margin-top: 0px;
        margin-right: 0px;
        margin-bottom: 0px;
        margin-left: 0px;
    }


    button,
    select {
        text-transform: none;
    }

    [type="reset"],
    [type="submit"],
    button,
    [type="button"] {
        -webkit-appearance: button;
        /*appearance: button;*/
    }

    button {
        outline: none !important;
        border: none;
        background: 0 0;
        border-top-width: initial;
        border-right-width: initial;
        border-bottom-width: initial;
        border-left-width: initial;
        border-top-style: none;
        border-right-style: none;
        border-bottom-style: none;
        border-left-style: none;
        border-top-color: initial;
        border-right-color: initial;
        border-bottom-color: initial;
        border-left-color: initial;
        border-image-source: initial;
        border-image-slice: initial;
        border-image-width: initial;
        border-image-outset: initial;
        border-image-repeat: initial;
        background-image: initial;
        background-position-x: 0px;
        background-position-y: 0px;
        background-size: initial;
        background-repeat-x: initial;
        background-repeat-y: initial;
        background-attachment: initial;
        background-origin: initial;
        background-clip: initial;
        background-color: initial;
        outline-color: initial !important;
        outline-style: none !important;
        outline-width: initial !important;
    }

    a {
        color: #007bff;
        text-decoration: none;
        background-color: transparent;
        -webkit-text-decoration-skip: objects;
        text-decoration-line: none;
        text-decoration-thickness: initial;
        text-decoration-style: initial;
        text-decoration-color: initial;
    }

    a {
        font-family: Poppins-Regular;
        font-size: 14px;
        line-height: 1.7;
        color: #666;
        margin: 0;
        transition: all .4s;
        -webkit-transition: all .4s;
        -o-transition: all .4s;
        -moz-transition: all .4s;
        margin-top: 0px;
        margin-right: 0px;
        margin-bottom: 0px;
        margin-left: 0px;
        transition-duration: 0.4s;
        transition-timing-function: ease;
        transition-delay: 0s;
        transition-property: all;
    }

    #powered_by_login {
        left: 45%;
    }
</style>
<div class="container-login100" style="background-image: url('<?php echo URL; ?>/assets/images/mabrex_bg.jpg');">
    <div class="wrap-login100" ng-controller="formController" ng-init='return_value ="<?php echo $returned; ?>"'>
        <form class="login100-form validate-form" ng-show="current_task === 'login'" method="post" name="login" action="<?php echo URL; ?>/login/login?return_url=<?php echo filter_input(INPUT_GET, 'return_url') ?>">
            <span class="">
                <img src="<?php echo URL; ?>/assets/images/rahisi/official_rahisi_minimal_logo_coloured.png" width="20%" style="margin-left:40%" class="img img-responsive text-center">
                <h4 class="text-center" style="color:#FFFFFF; font-size: 3rem; font-weight: 600;">RAHISI SOLUTION</h4>
                <h5 class="text-center" style="color:#FFFFFF; font-size: 1.6em; margin-top: 15px;">BACKEND MANAGEMENT PORTAL</h5>
            </span>
            <span class="login100-form-title p-b-34 p-t-27">
                LOGIN
            </span>
            <div class="wrap-input100 validate-input" data-validate="Enter username">
                <input class="input100" type="email" name="email" placeholder="Email Address" ng-class="login.email.$invalid.$pristine" ng-model="useremail" required />
                <span class="focus-input100">
                    <svg class="login__icon name svg-icon" viewBox="0 0 20 20">
                        <path d="M0,20 a10,8 0 0,1 20,0z M10,0 a4,4 0 0,1 0,8 a4,4 0 0,1 0,-8" />
                    </svg>
                </span>
            </div>
            <div class="wrap-input100 validate-input" data-validate="Enter password">
                <input class="input100" type="password" name="password" placeholder="Password" ng-class="login.password.$invalid.$pristine" required />
                <span class="focus-input100">
                    <svg class="login__icon pass svg-icon" viewBox="0 0 20 20">
                        <path d="M0,20 20,20 20,8 0,8z M10,13 10,16z M4,8 a6,8 0 0,1 12,0" />
                    </svg>
                </span>
            </div>
            <div class="wrap-input100 validate-input">
                <input ng-class="login.captcha.$invalid.$pristine" type="text" class="input100" placeholder="Captcha" ng-focus="onFocusShowRecaptcha($event)" name="captcha" required />
                <span class="focus-input100">
                    <svg class="login__icon pass svg-icon" viewBox="0 0 20 20">
                        <path d="M2.083,9H0.062H0v5l1.481-1.361C2.932,14.673,5.311,16,8,16c4.08,0,7.446-3.054,7.938-7h-2.021
                                                c-0.476,2.838-2.944,5-5.917,5c-2.106,0-3.96-1.086-5.03-2.729L5.441,9H2.083z" />
                        <path d="M8,0C3.92,0,0.554,3.054,0.062,7h2.021C2.559,4.162,5.027,2,8,2c2.169,0,4.07,1.151,5.124,2.876
                                                          L11,7h2h0.917h2.021H16V2l-1.432,1.432C13.123,1.357,10.72,0,8,0z" />
                    </svg>
                    <span class="tooltip-container" style="margin-left: 50%">
                        <span class='tooltip'>
                            <img style="display:inline;" src="<?php echo APP_DIR;  ?>/Login/get_captcha" alt="CAPTCHA" class="captcha-image">
                        </span>
                    </span>
                </span>
            </div>
            <div class="container-login100-form-btn">
                <button class="login100-form-btn" ng-disabled="login.$invalid" name="SignIn" value="<?php echo trans('login'); ?>">Login</button>
            </div>
            <div class="text-center p-t-90 text-white">
                <a class="txt1" href="#recover" ng-click="current_task = 'recover'">
                    Forgot Password?
                </a>
            </div>
        </form>
        <form id="recover" ng-show="current_task === 'recover'" method="post" name="login" action="<?php echo URL; ?>/Login/recover">
            <span class="">
                <img src="<?php echo URL; ?>/assets/images/rahisi/official_rahisi_minimal_logo_coloured.png" width="20%" style="margin-left:40%" class="img img-responsive text-center">
                <h4 class="text-center" style="color:#FFFFFF; font-size: 3rem; font-weight: 600;">RAHISI SOLUTION</h4>
                <h5 class="text-center" style="color:#FFFFFF; font-size: 1.6em; margin-top: 15px;">BACKEND MANAGEMENT PORTAL</h5>
            </span>
            <br>
            <span class="text-center">
                <h3 style="color:white; text-transform: uppercase;">Password Recovery</h3>
            </span>
            <br>
            <div class="wrap-input100 validate-input" data-validate="Enter username">
                <input class="input100" type="email" name="email" placeholder="Email Address" ng-class="login.email.$invalid.$pristine" ng-model="useremail" required />
                <span class="focus-input100">
                    <svg class="login__icon name svg-icon" viewBox="0 0 20 20">
                        <path d="M0,20 a10,8 0 0,1 20,0z M10,0 a4,4 0 0,1 0,8 a4,4 0 0,1 0,-8" />
                    </svg>
                </span>
            </div>
            <input type="submit" ng-disabled="recover.email.$invalid" class="recover_pwd_btn col-md-offset-3" name="RecoverPassword" value="Recover Password" />
            <br><br/><br/>
            <div class="text-white">
                <a href="#login" class="txt1" ng-click="current_task = 'login'">Back to Login Page</a>
            </div>
        </form>
    </div>
</div>
<!--<div id="powered_by_login" class="hidden-sm hidden-xs text-center text-white" style="margin-right: 279px;"><span>Powered By</span>-->
<!--    <img src="--><?php //echo URL; ?><!--/assets/images/--><?php //echo $my_theme; ?><!--/official_rahisi_logo_white.png" width="62%">-->
<!--</div>-->
