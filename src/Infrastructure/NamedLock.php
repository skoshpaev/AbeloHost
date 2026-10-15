<?php
declare(strict_types=1);

namespace App\Infrastructure;

use PDO;
use RuntimeException;

final class NamedLock
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function run(string $operation, callable $callback): mixed
    {
        $name = 'blog:' . substr(hash('sha256', (string) $this->pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 20) . ':' . $operation;
        $statement = $this->pdo->prepare('SELECT GET_LOCK(:name, 10)');
        $statement->execute(['name' => $name]);
        if ((int) $statement->fetchColumn() !== 1) {
            throw new RuntimeException("Cannot acquire $operation lock.");
        }
        try {
            return $callback();
        } finally {
            $statement = $this->pdo->prepare('SELECT RELEASE_LOCK(:name)');
            $statement->execute(['name' => $name]);
        }
    }
}
