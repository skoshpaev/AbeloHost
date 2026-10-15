<?php

declare(strict_types=1);

namespace App\Repository;

use App\Infrastructure\TransactionRunner;
use JsonException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final readonly class ViewBatchRepository
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function apply(string $batchId, array $counts): bool
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $batchId) || $counts === []) {
            throw new RuntimeException('Invalid view batch.');
        }
        foreach ($counts as $articleId => $count) {
            if (!is_int($articleId) || $articleId < 1 || !is_int($count) || $count < 1) {
                throw new RuntimeException('Invalid view batch counts.');
            }
        }
        ksort($counts, SORT_NUMERIC);
        $checksum = hash('sha256', json_encode($counts, JSON_THROW_ON_ERROR));
        return new TransactionRunner($this->pdo)->run(function () use ($batchId, $counts, $checksum): bool {
            $statement = $this->pdo->prepare('INSERT INTO view_batches (id, checksum) VALUES (:id, :checksum)');
            try {
                $statement->execute(['id' => $batchId, 'checksum' => $checksum]);
            } catch (PDOException $error) {
                if ((int)($error->errorInfo[1] ?? 0) !== 1062) {
                    throw $error;
                }
                $statement = $this->pdo->prepare('SELECT checksum FROM view_batches WHERE id = :id FOR UPDATE');
                $statement->execute(['id' => $batchId]);
                if ($statement->fetchColumn() !== $checksum) {
                    throw new RuntimeException('Applied view batch has a different payload.');
                }

                return false;
            }
            $statement = $this->pdo->prepare('UPDATE articles SET views = views + :count WHERE id = :id');
            foreach ($counts as $articleId => $count) {
                // Deleted articles are deliberately skipped; their IDs are not reused.
                $statement->execute(['count' => $count, 'id' => $articleId]);
            }

            return true;
        });
    }
}
