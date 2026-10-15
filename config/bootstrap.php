<?php

declare(strict_types=1);

use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Controller\CategoryController;
use App\Controller\ArticleController;
use App\Http\Application;
use App\Http\Router;
use App\Infrastructure\SmartyRenderer;
use App\Infrastructure\Database;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\HomeService;
use App\Service\HealthService;
use App\Service\CategoryService;

require_once dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('UTC');

$pdo = Database::connect();
$publishedUntil = gmdate('Y-m-d H:i:s');
$renderer = new SmartyRenderer(dirname(__DIR__));
$articlesRepository = new ArticleRepository($pdo, $publishedUntil);
$categoryRepository = new CategoryRepository($pdo, $publishedUntil);
$homeService = new HomeService($articlesRepository);
$categoryService = new CategoryService($categoryRepository);
$healthService = new HealthService($pdo);
$homeController = new HomeController($homeService, $renderer);
$categoryController = new CategoryController($categoryService, $renderer);
$articleController = new ArticleController($articlesRepository, $renderer);
$healthController = new HealthController($healthService);
$router = new Router();

require __DIR__ . '/routes.php';

return new Application($router, $renderer);
