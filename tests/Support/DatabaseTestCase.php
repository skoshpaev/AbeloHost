<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Infrastructure\Database;
use App\Infrastructure\Migrator;
use App\Infrastructure\RedisConnection;
use App\Service\Seeder\SeederService;
use PDO;
use PHPUnit\Framework\TestCase;
use Redis;
use RuntimeException;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;
    protected Redis $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Database::connect();
        $database = (string)$this->pdo->query('SELECT DATABASE()')->fetchColumn();
        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException('Destructive tests require a database ending in _test.');
        }
        $this->redis = RedisConnection::connect();
        $this->redis->flushDB();
        new Migrator($this->pdo, dirname(__DIR__, 2) . '/database/migrations')->migrate();
        $this->clearData();
        $this->seed();
    }

    protected function clearData(): void
    {
        $this->pdo->exec('DELETE FROM view_batches WHERE 1');
        $this->pdo->exec('DELETE FROM articles WHERE 1');
        $this->pdo->exec('DELETE FROM categories WHERE 1');
    }

    protected function seed(): array
    {
        return new SeederService($this->pdo, dirname(__DIR__, 2) . '/database/fixtures/blog.php')->seed();
    }

    protected function id(string $table, string $slug): int
    {
        if (!in_array($table, ['articles', 'categories'], true)) {
            throw new RuntimeException('Unexpected table.');
        }

        $statement = $this->pdo->prepare('SELECT id FROM ' . $table . ' WHERE slug = :slug');
        $statement->execute(['slug' => $slug]);

        return (int)$statement->fetchColumn();
    }
}
