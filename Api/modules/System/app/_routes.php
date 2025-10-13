<?php

use Modules\System\app\AppSplashController;

// SPLASH
$router->post('api/app/splash', [AppSplashController::class, 'index']);

