<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Request;
use App\Http\Response;
use DOMDocument;
use DOMXPath;
use Tests\Support\DatabaseTestCase;

final class HomePageTest extends DatabaseTestCase
{
    private function home(): Response
    {
        $application = require dirname(__DIR__, 2) . '/config/bootstrap.php';
        return $application->handle(new Request('GET', '/'));
    }

    public function testDisplaysThreeArticlesForEachPublishedCategory(): void
    {
        $response = $this->home();
        self::assertSame(200, $response->status);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $response->body, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $sections = $xpath->query('//main/section');
        self::assertCount(5, $sections);
        foreach ($sections as $section) {
            self::assertCount(3, $xpath->query('.//article', $section));
        }
        self::assertNotContains(
            'Заметки',
            array_map(static fn($node): string => $node->textContent, iterator_to_array($xpath->query('//section//h2')))
        );
        $future = $this->pdo->prepare('SELECT title FROM articles WHERE slug = :slug');
        $future->execute(['slug' => 'a-look-ahead']);
        $futureTitle = $future->fetchColumn();
        self::assertStringNotContainsString($futureTitle, $response->body);
    }

    public function testDisplaysEmptyState(): void
    {
        $this->clearData();
        $response = $this->home();
        self::assertSame(200, $response->status);
        self::assertStringContainsString('Скоро здесь появятся первые статьи.', $response->body);
        self::assertStringNotContainsString('<article>', $response->body);
    }

    public function testEscapesDatabaseContent(): void
    {
        $category = $this->pdo->prepare('UPDATE categories SET name = :name WHERE slug = :slug');
        /** @noinspection JSUnresolvedReference */
        $category->execute(['name' => '<script>category</script>', 'slug' => 'technology']);
        $article = $this->pdo->prepare('UPDATE articles SET title = :title WHERE slug = :slug');
        /** @noinspection JSUnresolvedReference */
        $article->execute(['title' => '<script>article</script>', 'slug' => 'technology-1']);
        $body = $this->home()->body;
        self::assertStringNotContainsString('<script>', $body);
        self::assertStringContainsString('&lt;script&gt;category&lt;/script&gt;', $body);
        self::assertStringContainsString('&lt;script&gt;article&lt;/script&gt;', $body);
    }
}
