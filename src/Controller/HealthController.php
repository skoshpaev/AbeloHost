<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\HttpException;
use App\Http\Response;
use App\Service\Health\Api\HealthServiceInterface;
use PDOException;

final readonly class HealthController
{
    public function __construct(private HealthServiceInterface $health)
    {
    }

    public function index(): Response
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
