<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Request;
use App\Http\Response;
use DOMDocument;
use DOMXPath;
use Tests\Support\DatabaseTestCase;

final class ArticlePageTest extends DatabaseTestCase
{
    private function page(string $slug): Response
    {
        $application = require dirname(__DIR__, 2) . '/config/bootstrap.php';

        return $application->handle(new Request('GET', '/articles/' . $slug));
    }

    public function testDisplaysArticleAndSimilarArticles(): void
    {
        $response = $this->page('technology-1');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('Технологии, которые оставляют время для жизни', $response->body);
        self::assertStringContainsString('Просмотров:', $response->body);

        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $response->body, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        self::assertCount(3, $xpath->query('//section[@aria-labelledby="related-title"]/article'));
        self::assertCount(2, $xpath->query('//main/nav/a'));
    }

    public function testMissingAndUnpublishedArticlesReturnNotFound(): void
    {
        self::assertSame(404, $this->page('missing')->status);
        $this->pdo->exec("UPDATE articles SET published_at = '2099-01-01 00:00:00' WHERE 1");
        self::assertSame(404, $this->page('technology-1')->status);
    }
}
