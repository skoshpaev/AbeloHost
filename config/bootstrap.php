<?php

declare(strict_types=1);

use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Http\Application;
use App\Http\Router;
use App\Infrastructure\SmartyRenderer;
use App\Infrastructure\Database;
use App\Repository\ArticleRepository;
use App\Service\HomeService;
use App\Service\HealthService;

require_once dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('UTC');

$pdo = Database::connect();
$publishedUntil = gmdate('Y-m-d H:i:s');
$renderer = new SmartyRenderer(dirname(__DIR__));
$articles = new ArticleRepository($pdo, $publishedUntil);
$homeService = new HomeService($articles);
$healthService = new HealthService($pdo);
$homeController = new HomeController($homeService, $renderer);
$healthController = new HealthController($healthService);
$router = new Router();

require __DIR__ . '/routes.php';

return new Application($router, $renderer);
