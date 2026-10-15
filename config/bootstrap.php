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
use App\Repository\RedisViewBufferRepository;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\Health\HealthService;
use App\Service\Category\CategoryService;
use App\Service\ViewTracker\ViewTrackerService;
use App\Service\VisitorIdentity\VisitorIdentityService;
use App\Infrastructure\SigningKey;

require_once dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('UTC');

$pdo = Database::connect();
$publishedUntil = gmdate('Y-m-d H:i:s');
$renderer = new SmartyRenderer(dirname(__DIR__));
$viewBuffer = new RedisViewBufferRepository();
$articlesRepository = new ArticleRepository($pdo, $publishedUntil, $viewBuffer);
$categoryRepository = new CategoryRepository($pdo, $publishedUntil);
$categoryService = new CategoryService($categoryRepository);
$healthService = new HealthService($pdo);
$visitorIdentity = new VisitorIdentityService(SigningKey::load(dirname(__DIR__) . '/storage/visitor.key'));
$viewTracker = new ViewTrackerService($viewBuffer, $visitorIdentity);
$homeController = new HomeController($renderer, $articlesRepository);
$categoryController = new CategoryController($categoryService, $renderer, $viewTracker);
$articleController = new ArticleController($articlesRepository, $renderer, $viewTracker);
$healthController = new HealthController($healthService);
$router = new Router();

require __DIR__ . '/routes.php';

return new Application($router, $renderer);
