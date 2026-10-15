<?php

declare(strict_types=1);


namespace App\Infrastructure;

use PDO;
use RuntimeException;

/** @noinspection ALL */
final class Migrator
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $directory
    ) {}

    public function migrate(): void
    {
        (new NamedLock($this->pdo))->run('maintenance', function (): void {
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(100) PRIMARY KEY,
                checksum CHAR(64) NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            $files = glob($this->directory . '/*.sql') ?: [];
            sort($files);
            foreach ($files as $file) {
                $version = basename($file);
                $sql = file_get_contents($file);
                if ($sql === false) {
                    throw new RuntimeException("Cannot read migration $version.");
                }
                $checksum = hash('sha256', $sql);
                $statement = $this->pdo->prepare('SELECT checksum FROM schema_migrations WHERE version = :version');
                $statement->execute(['version' => $version]);
                $stored = $statement->fetchColumn();
                if ($stored !== false) {
                    if ($stored !== $checksum) {
                        throw new RuntimeException("Applied migration $version was modified.");
                    }
                    continue;
                }
                // Initial DDL is restartable; MySQL implicitly commits DDL statements.
                foreach (array_filter(explode(';', $sql), fn (string $part): bool => trim($part) !== '') as $part) {
                    $this->pdo->exec($part);
                }
                $statement = $this->pdo->prepare('INSERT INTO schema_migrations (version, checksum) VALUES (:version, :checksum)');
                $statement->execute(['version' => $version, 'checksum' => $checksum]);
            }
        });
    }
}
