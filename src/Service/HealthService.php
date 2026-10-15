<?php
declare(strict_types=1);

namespace App\Service;

use PDO;

final class HealthService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function check(): void
    {
        $this->pdo->query('SELECT 1');
    }
}
