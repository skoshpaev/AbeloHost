<?php

declare(strict_types=1);

namespace App\Repository;

use App\Infrastructure\RedisConnection;
use InvalidArgumentException;
use Random\RandomException;
use Redis;
use RuntimeException;

final class RedisViewBufferRepository
{
    public const int WINDOW_SECONDS = 86400;
    private ?Redis $redis;
    private string $scripts;

    public function __construct(
        ?Redis $redis = null,
        private readonly string $prefix = 'blog:views',
    ) {
        $this->redis = $redis;
        $this->scripts = dirname(__DIR__, 2) . '/resources/lua';
        if (!preg_match('/^[a-zA-Z0-9:_-]+$/D', $prefix)) {
            throw new InvalidArgumentException('Invalid Redis namespace.');
        }
    }

    private function connection(): Redis
    {
        return $this->redis ??= RedisConnection::connect();
    }

    private function script(string $name, array $keys, array $arguments = []): int
    {
        $script = file_get_contents($this->scripts . '/' . $name . '.lua');
        if ($script === false) {
            throw new RuntimeException('Cannot read view counting script.');
        }
        $result = $this->connection()->eval($script, array_merge($keys, $arguments), count($keys));
        if (!is_int($result)) {
            throw new RuntimeException('Redis view operation failed.');
        }
        return $result;
    }

    private function parseTotal(mixed $value): int
    {
        $digits = (string)$value;
        $maximum = (string)PHP_INT_MAX;
        if ((!is_string($value) && !is_int($value)) || !ctype_digit($digits)
            || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)
        ) {
            throw new RuntimeException('Invalid Redis view total.');
        }
        return (int)$digits;
    }

    public function record(int $articleId, string $visitorId, int $initialTotal): bool
    {
        if ($articleId < 1 || !preg_match('/^[a-f0-9]{64}$/D', $visitorId) || $initialTotal < 0
            || $initialTotal > PHP_INT_MAX
        ) {
            throw new InvalidArgumentException('Invalid article, visitor, or view total.');
        }
        return $this->script('record-view', [
                $this->prefix . ':seen:' . $articleId . ':' . $visitorId,
                $this->prefix . ':active',
                $this->prefix . ':totals',
            ], [(string)$articleId, (string)self::WINDOW_SECONDS, (string)$initialTotal]) === 1;
    }

    public function total(int $articleId): ?int
    {
        if ($articleId < 1) {
            throw new InvalidArgumentException('Invalid article identifier.');
        }
        $value = $this->connection()->hGet($this->prefix . ':totals', (string)$articleId);
        if ($value === false) {
            return null;
        }
        return $this->parseTotal($value);
    }

    public function totals(array $fallbackByArticleId): array
    {
        if ($fallbackByArticleId === []) {
            return [];
        }
        $arguments = [];
        foreach ($fallbackByArticleId as $articleId => $views) {
            if (!is_int($articleId) || $articleId < 1 || !is_int($views) || $views < 0 || $views > PHP_INT_MAX) {
                throw new InvalidArgumentException('Invalid view total input.');
            }
            $arguments[] = (string)$articleId;
            $arguments[] = (string)$views;
        }
        $script = file_get_contents($this->scripts . '/read-totals.lua');
        if ($script === false) {
            throw new RuntimeException('Cannot read view counting script.');
        }
        $result = $this->connection()->eval($script, array_merge([$this->prefix . ':totals'], $arguments), 1);
        if (!is_array($result) || count($result) !== count($fallbackByArticleId)) {
            throw new RuntimeException('Redis view totals could not be read.');
        }
        $totals = [];
        foreach (array_keys($fallbackByArticleId) as $index => $articleId) {
            $value = $result[$index] ?? false;
            $totals[$articleId] = $this->parseTotal($value);
        }
        return $totals;
    }

    /**
     * @throws RandomException
     */
    public function seal(): ?string
    {
        $batchId = bin2hex(random_bytes(16));
        return $this->script('seal-views', [$this->prefix . ':active', $this->batchKey($batchId)]) === 1 ? $batchId
            : null;
    }

    private function batchKey(string $id): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) {
            throw new InvalidArgumentException('Invalid view batch identifier.');
        }
        return $this->prefix . ':batch:' . $id;
    }

    public function pending(): array
    {
        $cursor = null;
        $batches = [];
        do {
            $keys = $this->connection()->scan($cursor, $this->prefix . ':batch:*', 100);
            foreach ($keys ?: [] as $key) {
                $batches[] = substr($key, strlen($this->prefix . ':batch:'));
            }
        } while ($cursor !== 0);
        sort($batches);
        return array_values(array_unique($batches));
    }

    public function read(string $batchId): array
    {
        $values = $this->connection()->hGetAll($this->batchKey($batchId));
        if ($values === false || $values === []) {
            throw new RuntimeException('Pending view batch is missing or empty.');
        }
        foreach ($values as $id => $count) {
            if (!ctype_digit((string)$id) || (int)$id < 1) {
                throw new RuntimeException('Invalid pending view batch.');
            }
            $count = $this->parseTotal($count);
            if ($count < 1) {
                throw new RuntimeException('Invalid pending view batch.');
            }
            $values[$id] = $count;
        }
        ksort($values, SORT_NUMERIC);
        return $values;
    }

    public function acknowledge(string $batchId): void
    {
        $this->connection()->del($this->batchKey($batchId));
    }

    /**
     * @throws RandomException
     */
    public function ensureDurable(): void
    {
        $redis = $this->connection();
        $redis->setOption(Redis::OPT_READ_TIMEOUT, 3.0);
        // This connection's write barrier covers earlier writes by other clients too.
        if (!$redis->set($this->prefix . ':durability', bin2hex(random_bytes(16)))) {
            throw new RuntimeException('Cannot create view durability barrier.');
        }
        $acknowledged = $redis->rawCommand('WAITAOF', 1, 0, 2000);
        if (!is_array($acknowledged) || (int)$acknowledged[0] < 1) {
            throw new RuntimeException('View batches have not been persisted to Redis AOF.');
        }
    }
}
