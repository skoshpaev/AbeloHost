<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Migrator;
use App\Service\Seeder\SeederService;
use PDOException;
use Tests\Support\DatabaseTestCase;

final class SeederTest extends DatabaseTestCase
{
    public function testRepeatedSeedPreservesExistingDataAndViews(): void
    {
        $links = (int) $this->pdo->query('SELECT COUNT(*) FROM article_category')->fetchColumn();
        $this->pdo->prepare('UPDATE articles SET views = :views, title = :title WHERE slug = :slug')->execute([
            'views' => 9876,
            'title' => 'Edited title',
            'slug' => 'technology-1',
        ]);
        self::assertSame(['categories' => 6, 'articles' => 61], $this->seed());
        self::assertSame($links, (int) $this->pdo->query('SELECT COUNT(*) FROM article_category')->fetchColumn());
        $article = $this->pdo->prepare('SELECT views, title FROM articles WHERE slug = :slug');
        $article->execute(['slug' => 'technology-1']);
        self::assertSame(['views' => 9876, 'title' => 'Edited title'], $article->fetch());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM articles a WHERE NOT EXISTS (SELECT 1 FROM article_category ac WHERE ac.article_id = a.id)')->fetchColumn());
    }

    public function testFailedSeedRollsBackAllData(): void
    {
        $this->clearData();
        $fixtures = require dirname(__DIR__, 2) . '/database/fixtures/blog.php';
        $fixtures['articles'][2]['title'] = str_repeat('x', 201);
        $file = tempnam(sys_get_temp_dir(), 'blog-fixture-');
        file_put_contents($file, '<?php return ' . var_export($fixtures, true) . ';');
        try {
            new SeederService($this->pdo, $file)->seed();
            self::fail('Invalid seed should fail.');
        } catch (PDOException $error) {
            self::assertSame(1406, (int) $error->errorInfo[1]);
            self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn());
            self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn());
        } finally {
            unlink($file);
        }
    }

    public function testParallelSeedDoesNotDuplicateData(): void
    {
        $this->clearData();
        $workers = [];
        $start = (string) (microtime(true) + 0.3);
        for ($index = 0; $index < 4; $index++) {
            $process = proc_open([PHP_BINARY, dirname(__DIR__) . '/Support/seed-worker.php', $start], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
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
        self::assertSame(6, (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn());
        self::assertSame(61, (int) $this->pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn());
        self::assertSame(76, (int) $this->pdo->query('SELECT COUNT(*) FROM article_category')->fetchColumn());
    }

    public function testMigrationsAreRepeatable(): void
    {
        $appliedBefore = (int) $this->pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
        new Migrator($this->pdo, dirname(__DIR__, 2) . '/database/migrations')->migrate();
        self::assertSame($appliedBefore, (int) $this->pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn());
        self::assertSame(61, (int) $this->pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn());
    }
}
