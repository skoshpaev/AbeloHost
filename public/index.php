<?php
declare(strict_types=1);

use App\Http\Request;

try {
    $application = require dirname(__DIR__) . '/config/bootstrap.php';
    $request = Request::fromGlobals();
    $application->handle($request)->send($request->method === 'HEAD');
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code($error instanceof PDOException ? 503 : 500);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
        echo 'Не удалось открыть страницу. Попробуйте немного позже.';
    }
}
