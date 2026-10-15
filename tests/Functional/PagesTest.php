<?php

declare(strict_types=1);

namespace Tests\Functional;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use Tests\Support\DatabaseTestCase;

final class PagesTest extends DatabaseTestCase
{
    private static mixed $server;
    private static string $baseUrl;
    private static string $logFile;

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($socket);
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        /** @noinspection HttpUrlsUsage */
        self::$baseUrl = 'http://' . $address;
        self::$logFile = tempnam(sys_get_temp_dir(), 'blog-http-');
        self::$server = proc_open([
            PHP_BINARY,
            '-S',
            $address,
            '-t',
            dirname(__DIR__, 2) . '/public',
            dirname(__DIR__) . '/Support/http-router.php',
        ], [1 => ['file', self::$logFile, 'a'], 2 => ['file', self::$logFile, 'a']], $pipes);
        self::assertIsResource(self::$server);
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
            if (is_resource($connection)) {
                fclose($connection);
                return;
            }
            usleep(20_000);
        }
        throw new RuntimeException('Test HTTP server failed to start: ' . file_get_contents(self::$logFile));
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        if (is_file(self::$logFile)) {
            unlink(self::$logFile);
        }
    }

    private function request(string $path, string $method = 'GET', string $cookie = ''): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'ignore_errors' => true,
                'timeout' => 10,
                'header' => $cookie === '' ? '' : 'Cookie: blog_visitor=' . $cookie,
            ],
        ]);
        $stream = fopen(self::$baseUrl . $path, 'r', false, $context);
        self::assertIsResource($stream);
        $headers = stream_get_meta_data($stream)['wrapper_data'];
        $body = stream_get_contents($stream);
        fclose($stream);
        self::assertIsString($body);
        preg_match('/^HTTP\/\S+ (\d+)/', $headers[0], $match);
        return ['status' => (int)$match[1], 'headers' => implode("\n", $headers), 'body' => $body];
    }

    private function xpath(string $body): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $body);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return new DOMXPath($document);
    }

    private function storedViews(): int
    {
        $statement = $this->pdo->prepare('SELECT views FROM articles WHERE slug = :slug');
        $statement->execute(['slug' => 'technology-1']);
        return (int)$statement->fetchColumn();
    }

    public function testHomeListsAllPopulatedCategoriesAndTheirThreeArticles(): void
    {
        $response = $this->request('/');
        self::assertSame(200, $response['status']);
        $xpath = $this->xpath($response['body']);
        self::assertSame(5, $xpath->query('//section[contains(@class,"category-section")]')->length);
        self::assertSame(15, $xpath->query('//article[@class="article-card"]')->length);
        self::assertSame(
            5,
            $xpath->query('//section[contains(@class,"category-section")]//a[contains(@class,"button")]')->length
        );
        self::assertStringNotContainsString('/categories/notebook', $response['body']);
        self::assertStringNotContainsString('a-look-ahead', $response['body']);
    }

    public function testCategorySortAndPaginationWorkOverHttp(): void
    {
        $response = $this->request('/categories/technology?sort=views&page=1');
        self::assertSame(200, $response['status']);
        $xpath = $this->xpath($response['body']);
        self::assertSame(9, $xpath->query('//article[@class="article-card"]')->length);
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        self::assertSame(
            '/articles/technology-12',
            $xpath->query('//article[@class="article-card"][1]/h3/a')->item(0)->getAttribute('href')
        );
        self::assertStringContainsString('sort=views&amp;page=2', $response['body']);
        $second = $this->request('/categories/technology?sort=views&page=2');
        self::assertSame(200, $second['status']);
        self::assertSame(6, $this->xpath($second['body'])->query('//article[@class="article-card"]')->length);
    }

    public function testArticleShowsAllFieldsWithoutChangingStoredViews(): void
    {
        $before = $this->storedViews();
        $response = $this->request('/articles/technology-1');
        self::assertSame(200, $response['status']);
        $xpath = $this->xpath($response['body']);
        self::assertSame(2, $xpath->query('//div[@class="article-tags"]/a')->length);
        self::assertSame(5, $xpath->query('//div[@class="article-body"]/p')->length);
        self::assertSame(3, $xpath->query('//section[contains(@class,"related-section")]//article')->length);
        self::assertStringContainsString('Просмотры: 121', $response['body']);
        self::assertStringContainsString('/assets/images/technology-1.svg', $response['body']);
        self::assertStringContainsString('2026-09-28 10:00:00', $response['body']);
        self::assertSame($before, $this->storedViews());
    }

    public function testRepeatArticleRequestWithSameCookieDoesNotAddAnotherView(): void
    {
        $first = $this->request('/articles/technology-1');
        self::assertStringContainsString('Просмотры: 121', $first['body']);
        preg_match('/^Set-Cookie: blog_visitor=([^;]+)/mi', $first['headers'], $match);
        self::assertArrayHasKey(1, $match);
        $second = $this->request('/articles/technology-1', 'GET', $match[1]);
        self::assertStringContainsString('Просмотры: 121', $second['body']);
        self::assertSame(120, $this->storedViews());
    }

    public function testEmptyCategoryIsStillAccessible(): void
    {
        $response = $this->request('/categories/notebook');
        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Пока здесь тихо', $response['body']);
        self::assertSame(0, $this->xpath($response['body'])->query('//article[@class="article-card"]')->length);
    }

    public function testNotFoundAndInvalidParameters(): void
    {
        foreach (
            [
                '/missing',
                '/categories/missing',
                '/articles/missing',
                '/articles/a-look-ahead',
                '/categories/technology?page=99',
            ] as $path
        ) {
            self::assertSame(404, $this->request($path)['status'], $path);
        }
        foreach (
            [
                '/categories/technology?page=0',
                '/categories/technology?page[]=1',
                '/categories/technology?sort=bad',
                '/categories/technology?sort[]=views',
            ] as $path
        ) {
            self::assertSame(400, $this->request($path)['status'], $path);
        }
        $response = $this->request('/', 'POST');
        self::assertSame(405, $response['status']);
        self::assertStringContainsString('Allow: GET, HEAD', $response['headers']);
    }

    public function testHeadHasNoBodyAndSecurityHeadersArePresent(): void
    {
        $response = $this->request('/articles/technology-1', 'HEAD');
        self::assertSame(200, $response['status']);
        self::assertSame('', $response['body']);
        self::assertStringContainsString('Cache-Control: no-store', $response['headers']);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $response['headers']);
        self::assertStringContainsString('X-Frame-Options: DENY', $response['headers']);
        self::assertStringContainsString('Content-Security-Policy:', $response['headers']);
        self::assertStringContainsString('Referrer-Policy: strict-origin-when-cross-origin', $response['headers']);
        self::assertStringContainsString('Permissions-Policy:', $response['headers']);
    }

    public function testUserContentIsEscapedIncludingSmartySyntax(): void
    {
        $payload = '<script>alert("x")</script> {include file="secret.tpl"}';
        $statement = $this->pdo->prepare(
            'UPDATE articles SET title = :title, description = :description, body = :body WHERE slug = :slug'
        );
        $statement->execute(
            ['title' => $payload, 'description' => $payload, 'body' => $payload, 'slug' => 'technology-1']
        );
        $response = $this->request('/articles/technology-1');
        self::assertSame(200, $response['status']);
        self::assertStringNotContainsString('<script>', $response['body']);
        self::assertStringContainsString('&lt;script&gt;', $response['body']);
        self::assertStringContainsString('{include file=', $response['body']);
    }

    public function testAssetsAndHealthAreAvailable(): void
    {
        $home = $this->request('/');
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $stylesheet = $this->xpath($home['body'])->query('//link[@rel="stylesheet"]')->item(0)->getAttribute('href');
        self::assertMatchesRegularExpression('~^/assets/css/main\.css\?v=\d+$~', $stylesheet);
        self::assertSame(200, $this->request($stylesheet)['status']);
        self::assertSame(200, $this->request('/assets/css/main.css')['status']);
        self::assertSame(200, $this->request('/assets/images/technology-1.svg')['status']);
        $health = $this->request('/health');
        self::assertSame(200, $health['status']);
        self::assertSame('ok', $health['body']);
    }
}
