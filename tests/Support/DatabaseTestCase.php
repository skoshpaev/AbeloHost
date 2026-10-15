<?php
declare(strict_types=1);

namespace Tests\Support;

use App\Infrastructure\Database;
use App\Infrastructure\Migrator;
use App\Service\Seeder;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Database::connect();
        $database = (string) $this->pdo->query('SELECT DATABASE()')->fetchColumn();
        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException('Destructive tests require a database ending in _test.');
        }
        (new Migrator($this->pdo, dirname(__DIR__, 2) . '/database/migrations'))->migrate();
        $this->clearData();
        $this->seed();
    }

    protected function clearData(): void
    {
        $this->pdo->exec('DELETE FROM articles');
        $this->pdo->exec('DELETE FROM categories');
    }

    protected function seed(): array
    {
        return (new Seeder($this->pdo, dirname(__DIR__, 2) . '/database/fixtures/blog.php'))->seed();
    }

}
