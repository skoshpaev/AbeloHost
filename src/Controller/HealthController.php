<?php
declare(strict_types=1);

namespace App\Controller;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Database;
use PDOException;

final class HealthController
{
    public function index(Request $request): Response
    {
        try {
            Database::connect()->query('SELECT 1');
        } catch (PDOException $error) {
            error_log((string) $error);
            throw new HttpException(503, 'Сервис временно недоступен.');
        }

        return new Response('ok', 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
