<?php
declare(strict_types=1);

namespace App\Infrastructure;

use PDO;
use PDOException;
use Throwable;

final class TransactionRunner
{
    public function __construct(private PDO $pdo, private int $maxAttempts = 3)
    {
    }

    public function run(callable $operation): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            $this->pdo->beginTransaction();
            try {
                $result = $operation();
                $this->pdo->commit();
                return $result;
            } catch (Throwable $error) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                $retryable = $error instanceof PDOException
                    && in_array((int) ($error->errorInfo[1] ?? 0), [1205, 1213], true);
                if (!$retryable || $attempt >= $this->maxAttempts) {
                    throw $error;
                }
                usleep(random_int(10_000, 40_000) * $attempt);
            }
        }
    }
}
