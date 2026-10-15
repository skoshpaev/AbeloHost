<?php
declare(strict_types=1);

use App\Controller\HomeController;
use App\Http\Application;
use App\Http\Router;
use App\Infrastructure\SmartyRenderer;

require_once dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('UTC');

$renderer = new SmartyRenderer(dirname(__DIR__));
$homeController = new HomeController($renderer);
$router = new Router();

require __DIR__ . '/routes.php';

return new Application($router, $renderer);
