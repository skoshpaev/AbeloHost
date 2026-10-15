<?php
declare(strict_types=1);

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

require dirname(__DIR__) . '/config/bootstrap.php';

$request = Request::fromGlobals();
try {
    if (!in_array($request->method, ['GET', 'HEAD'], true)) {
        throw new HttpException(405, 'Метод запроса не поддерживается.');
    }

    $response = new Response(<<<'HTML'
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Блог</title>
</head>
<body>
    <h1>Блог</h1>
    <p>Приложение запущено.</p>
</body>
</html>
HTML, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
} catch (HttpException $error) {
    $response = new Response($error->getMessage(), $error->status, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Allow' => 'GET, HEAD',
    ]);
}

$response->send($request->method === 'HEAD');
