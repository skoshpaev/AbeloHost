<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\NamedLock;
use App\Http\Request;
use App\Repository\RedisViewBufferRepository;
use App\Repository\ViewBatchRepository;
use App\Service\ViewFlusher\ViewFlusherService;
use App\Service\ViewTracker\ViewTrackerService;
use App\Service\VisitorIdentity\VisitorIdentityService;
use JsonException;
use PDOException;
use RuntimeException;
use Tests\Support\DatabaseTestCase;
use Throwable;

final class ViewCountingTest extends DatabaseTestCase
{
    private function buffer(): RedisViewBufferRepository
    {
        return new RedisViewBufferRepository($this->redis);
    }

    private function flusher(): ViewFlusherService
    {
        return new ViewFlusherService($this->buffer(), new ViewBatchRepository($this->pdo), new NamedLock($this->pdo));
    }

    private function views(int $id): int
    {
        $statement = $this->pdo->prepare('SELECT views FROM articles WHERE id = :id');
        $statement->execute(['id' => $id]);
        return (int)$statement->fetchColumn();
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function testRepeatDoesNotExtendWindowAndAnotherArticleCountsSeparately(): void
    {
        $id = $this->id('articles', 'technology-1');
        $visitor = str_repeat('a', 64);
        $key = 'blog:views:seen:' . $id . ':' . $visitor;
        self::assertTrue($this->buffer()->record($id, $visitor, $this->views($id)));
        self::assertGreaterThanOrEqual(86399, $this->redis->ttl($key));
        self::assertSame(121, $this->buffer()->total($id));
        $this->redis->expire($key, 30);
        self::assertFalse($this->buffer()->record($id, $visitor, $this->views($id)));
        self::assertSame(121, $this->buffer()->total($id));
        self::assertLessThanOrEqual(30, $this->redis->ttl($key));
        $second = $this->id('articles', 'technology-2');
        self::assertTrue($this->buffer()->record($second, $visitor, $this->views($second)));
        self::assertSame(120, $this->views($id));
        self::assertSame(1, $this->flusher()->flush());
        self::assertSame(121, $this->views($id));
        self::assertSame(121, $this->buffer()->total($id));
        self::assertSame(0, $this->flusher()->flush());
        self::assertFalse($this->buffer()->record($id, $visitor, $this->views($id)));
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function testVisitorCanCountAgainAfterMarkerExpires(): void
    {
        $id = $this->id('articles', 'technology-1');
        $visitor = str_repeat('a', 64);
        $this->buffer()->record($id, $visitor, $this->views($id));
        $this->redis->pExpire('blog:views:seen:' . $id . ':' . $visitor, 1);
        usleep(10_000);
        self::assertTrue($this->buffer()->record($id, $visitor, $this->views($id)));
        self::assertSame(122, $this->buffer()->total($id));
        $this->flusher()->flush();
        self::assertSame(122, $this->views($id));
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function testSealingDoesNotLoseNewViewsAndBatchesAreImmutable(): void
    {
        $id = $this->id('articles', 'technology-1');
        $this->buffer()->record($id, str_repeat('a', 64), $this->views($id));
        $batch = $this->buffer()->seal();
        $this->buffer()->record($id, str_repeat('b', 64), $this->views($id));
        self::assertSame([$id => 1], $this->buffer()->read($batch));
        self::assertSame('1', $this->redis->hGet('blog:views:active', (string)$id));
        self::assertSame(122, $this->buffer()->total($id));
        self::assertSame(1, $this->flusher()->flush(false));
        self::assertSame(121, $this->views($id));
        self::assertSame('1', $this->redis->hGet('blog:views:active', (string)$id));
        $this->flusher()->flush();
        self::assertSame(122, $this->views($id));
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function testRetryAfterMysqlCommitBeforeRedisAcknowledgementIsSafe(): void
    {
        $id = $this->id('articles', 'technology-1');
        $this->buffer()->record($id, str_repeat('a', 64), $this->views($id));
        $batch = $this->buffer()->seal();
        $counts = $this->buffer()->read($batch);
        self::assertTrue(new ViewBatchRepository($this->pdo)->apply($batch, $counts));
        self::assertSame(121, $this->views($id));
        self::assertSame([$batch], $this->buffer()->pending());
        self::assertSame(0, $this->flusher()->flush(false));
        self::assertSame(121, $this->views($id));
        self::assertSame([], $this->buffer()->pending());
        self::assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM view_batches')->fetchColumn());
        // A Redis recovery may resurrect a batch whose deletion was not durable.
        $this->redis->hMSet('blog:views:batch:' . $batch, $counts);
        self::assertSame(0, $this->flusher()->flush(false));
        self::assertSame(121, $this->views($id));
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function testMysqlErrorPreservesPendingBatchAndRollsBackAllUpdates(): void
    {
        $first = $this->id('articles', 'technology-1');
        $second = $this->id('articles', 'technology-2');
        $firstBaseline = $this->views($first);
        $secondBaseline = $this->views($second);
        $this->pdo->prepare('UPDATE articles SET views = :views WHERE id = :id')->execute([
            'views' => '18446744073709551615',
            'id' => $second,
        ]);
        $this->buffer()->record($first, str_repeat('a', 64), $firstBaseline);
        $this->buffer()->record($second, str_repeat('a', 64), $secondBaseline);
        try {
            $this->flusher()->flush();
            self::fail('Overflow must fail the entire batch.');
        } catch (PDOException) {
            self::assertSame(120, $this->views($first));
            self::assertCount(1, $this->buffer()->pending());
            self::assertSame(0, (int)$this->pdo->query('SELECT COUNT(*) FROM view_batches')->fetchColumn());
        }
        $this->pdo->prepare('UPDATE articles SET views = :views WHERE id = :id')->execute([
            'views' => 437,
            'id' => $second,
        ]);
        self::assertSame(1, $this->flusher()->flush(false));
        self::assertSame(121, $this->views($first));
        self::assertSame(438, $this->views($second));
    }

    public function testLuaFailureDoesNotLeaveSuppressionMarker(): void
    {
        $id = $this->id('articles', 'technology-1');
        $visitor = str_repeat('a', 64);
        $this->redis->hSet('blog:views:active', (string)$id, 'invalid');
        try {
            $this->buffer()->record($id, $visitor, $this->views($id));
            self::fail('Invalid Redis counter should fail.');
        } catch (RuntimeException) {
            self::assertSame(0, (int)$this->redis->exists('blog:views:seen:' . $id . ':' . $visitor));
        }
        $this->redis->del('blog:views:active');
        self::assertTrue($this->buffer()->record($id, $visitor, $this->views($id)));
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function testBatchIdentifierCannotBeReusedWithDifferentPayload(): void
    {
        $id = $this->id('articles', 'technology-1');
        $batch = str_repeat('a', 32);
        $repository = new ViewBatchRepository($this->pdo);
        self::assertTrue($repository->apply($batch, [$id => 1]));
        $this->expectException(RuntimeException::class);
        $repository->apply($batch, [$id => 2]);
    }

    public function testRedisFailureDoesNotPreventServingAnArticle(): void
    {
        $host = getenv('REDIS_HOST');
        $port = getenv('REDIS_PORT');
        putenv('REDIS_HOST=127.0.0.1');
        putenv('REDIS_PORT=1');
        try {
            $tracker = new ViewTrackerService(
                new RedisViewBufferRepository(),
                new VisitorIdentityService(str_repeat('test-secret-', 4))
            );
            $result = $tracker->track(
                new Request('GET', '/articles/technology-1'),
                $this->id('articles', 'technology-1'),
                120
            );
            self::assertArrayHasKey('Set-Cookie', $result['headers']);
            self::assertSame(120, $result['views']);
            self::assertSame(120, $this->views($this->id('articles', 'technology-1')));
        } finally {
            putenv($host === false ? 'REDIS_HOST' : 'REDIS_HOST=' . $host);
            putenv($port === false ? 'REDIS_PORT' : 'REDIS_PORT=' . $port);
        }
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function testParallelRequestsForSameAndDifferentVisitors(): void
    {
        $id = $this->id('articles', 'technology-1');
        $start = (string)(microtime(true) + 0.3);
        $baseline = (string)$this->views($id);
        $workers = [];
        for ($index = 0; $index < 12; $index++) {
            $visitor = $index < 6 ? str_repeat('a', 64) : hash('sha256', (string)$index);
            $process = proc_open(
                [PHP_BINARY, dirname(__DIR__) . '/Support/view-worker.php', (string)$id, $visitor, $baseline, $start],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            self::assertIsResource($process);
            $workers[] = [$process, $pipes];
        }
        foreach ($workers as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $output . $error);
        }
        self::assertSame('7', $this->redis->hGet('blog:views:active', (string)$id));
        self::assertSame(127, $this->buffer()->total($id));
        $this->flusher()->flush();
        self::assertSame(127, $this->views($id));
    }

    public function testParallelFlushesApplyOneBatchExactlyOnce(): void
    {
        $id = $this->id('articles', 'technology-1');
        $this->buffer()->record($id, str_repeat('a', 64), $this->views($id));
        $workers = [];
        $start = (string)(microtime(true) + 0.3);
        for ($index = 0; $index < 2; $index++) {
            $process = proc_open([PHP_BINARY, dirname(__DIR__) . '/Support/flush-worker.php', $start],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes);
            self::assertIsResource($process);
            $workers[] = [$process, $pipes];
        }
        foreach ($workers as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $output . $error);
        }
        self::assertSame(121, $this->views($id));
        self::assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM view_batches')->fetchColumn());
        self::assertSame([], $this->buffer()->pending());
    }
}
