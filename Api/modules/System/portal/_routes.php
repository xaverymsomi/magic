<?php

use Modules\System\portal\PortalSplashController;

// PORTAL SPLASH
$router->post('api/splash', [PortalSplashController::class, 'index']);
