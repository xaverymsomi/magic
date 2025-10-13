<?php

namespace Modules\System\app;

use Libs\ApiLib;
use Core\Controller;

class AppSplashController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $splash = [
            'api_status' => 'Working',
        ];
        ApiLib::handleResponse('App splash data retrieved', $splash);
    }
}
