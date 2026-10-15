<?php
declare(strict_types=1);

use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Controller\CategoryController;
use App\Controller\ArticleController;
use App\Http\Router;

/** @var $router Router */
/** @var $homeController HomeController */
/** @var $categoryController CategoryController */
/** @var $articleController ArticleController */
/** @var $healthController HealthController */

$router->get('~^/$~D', [$homeController, 'index']);
$router->get('~^/categories/(?P<slug>[a-z0-9]+(?:-[a-z0-9]+)*)$~D', [$categoryController, 'index']);
$router->get('~^/articles/(?P<slug>[a-z0-9]+(?:-[a-z0-9]+)*)$~D', [$articleController, 'index']);
$router->get('~^/health$~D', [$healthController, 'index']);
