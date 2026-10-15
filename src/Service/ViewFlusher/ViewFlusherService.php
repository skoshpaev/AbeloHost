<?php

declare(strict_types=1);

namespace App\Service\ViewFlusher;

use App\Infrastructure\NamedLock;
use App\Repository\RedisViewBufferRepository;
use App\Repository\ViewBatchRepository;
use App\Service\ViewFlusher\Api\ViewFlusherServiceInterface;
use JsonException;
use Throwable;

final readonly class ViewFlusherService implements ViewFlusherServiceInterface
{
    public function __construct(
        private RedisViewBufferRepository $buffer,
        private ViewBatchRepository $batches,
        private NamedLock $lock,
    ) {
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function flush(bool $sealActive = true): int
    {
        return $this->lock->run(
 'views-flush',
            function () use ($sealActive): int {
                if ($sealActive) {
                    $this->buffer->seal();
                }
                $applied = 0;
                $pending = $this->buffer->pending();
                if ($pending !== []) {
                    $this->buffer->ensureDurable();
                }
                foreach ($pending as $batchId) {
                    $counts = $this->buffer->read($batchId);
                    if ($this->batches->apply($batchId, $counts)) {
                        $applied++;
                    }
                    // Keep the Redis batch until the MySQL transaction has committed.
                    $this->buffer->acknowledge($batchId);
                }
                return $applied;
            }
        );
    }
}
