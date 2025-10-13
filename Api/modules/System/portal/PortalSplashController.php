<?php

namespace Modules\System\portal;

use Core\Controller;
use Libs\ApiLib;

class PortalSplashController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $splash = [
            'api_status' => 'Working',
        ];
        ApiLib::handleResponse('Portal splash data retrieved', $splash);
    }
}
