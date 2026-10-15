<?php
declare(strict_types=1);

use App\Controller\HomeController;
use App\Http\Router;

require_once dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('UTC');

$homeController = new HomeController();
$router = new Router();

require __DIR__ . '/routes.php';

return $router;
