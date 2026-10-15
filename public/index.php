<?php
declare(strict_types=1);

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

$router = require dirname(__DIR__) . '/config/bootstrap.php';

$request = Request::fromGlobals();
try {
    $response = $router->dispatch($request);
} catch (HttpException $error) {
    $headers = ['Content-Type' => 'text/plain; charset=UTF-8'];
    if ($error->status === 405) {
        $headers['Allow'] = 'GET, HEAD';
    }
    $response = new Response($error->getMessage(), $error->status, $headers);
}

$response->send($request->method === 'HEAD');
