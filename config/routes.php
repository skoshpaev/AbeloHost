<?php
declare(strict_types=1);

use App\Controller\HomeController;
use App\Http\Router;

/** @var $router Router */
/** @var $homeController HomeController */

$router->get('~^/$~D', [$homeController, 'index']);
