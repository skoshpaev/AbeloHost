<?php

declare(strict_types=1);

namespace App\Service\Health;

use App\Service\Health\Api\HealthServiceInterface;
use PDO;

final readonly class HealthService implements HealthServiceInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function check(): void
    {
        $this->pdo->query('SELECT 1');
    }
}
