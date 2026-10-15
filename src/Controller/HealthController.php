<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Service\HealthService;
use PDOException;

final class HealthController
{
    public function __construct(private readonly HealthService $health)
    {
    }

    public function index(Request $request): Response
    {
        try {
            $this->health->check();
        } catch (PDOException $error) {
            error_log((string)$error);
            throw new HttpException(503, 'Сервис временно недоступен.');
        }

        return new Response('ok', 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
