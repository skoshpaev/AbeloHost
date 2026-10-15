<?php
declare(strict_types=1);

use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Http\Router;

/** @var $router Router */
/** @var $homeController HomeController */
/** @var $healthController HealthController */

$router->get('~^/$~D', [$homeController, 'index']);
$router->get('~^/health$~D', [$healthController, 'index']);
