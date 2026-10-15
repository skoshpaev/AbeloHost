<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Request;
use App\Http\Response;
use DOMDocument;
use DOMXPath;
use Tests\Support\DatabaseTestCase;

final class CategoryPageTest extends DatabaseTestCase
{
    private function page(string $slug, array $query = []): Response
    {
        $application = require dirname(__DIR__, 2) . '/config/bootstrap.php';
        return $application->handle(new Request('GET', '/categories/' . $slug, $query));
    }

    public function testDisplaysCategoryDetailsAndFirstPageOfArticles(): void
    {
        $response = $this->page('technology');
        self::assertSame(200, $response->status);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $response->body, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        self::assertSame('Технологии✳', trim($xpath->query('//main/header/h1')->item(0)->textContent));
        self::assertSame('Статей: 15', trim($xpath->query('//main//span[@class="results-count"]')->item(0)->textContent));
        self::assertCount(9, $xpath->query('//main/article'));
        self::assertSame('Технологии, которые оставляют время для жизни', trim($xpath->query('//main/article[1]/h3')->item(0)->textContent));
    }

    public function testPageTwoAndViewSorting(): void
    {
        $secondPage = $this->page('technology', ['page' => '2']);
        self::assertSame(200, $secondPage->status);
        $title = $this->pdo->prepare('SELECT title FROM articles WHERE slug = :slug');
        $title->execute(['slug' => 'technology-8']);
        self::assertStringContainsString((string)$title->fetchColumn(), $secondPage->body);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $secondPage->body, LIBXML_NOERROR | LIBXML_NOWARNING);
        self::assertCount(6, (new DOMXPath($document))->query('//main/article'));

        $popular = $this->page('technology', ['sort' => 'views']);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $popular->body, LIBXML_NOERROR | LIBXML_NOWARNING);
        self::assertSame('Когда хороший инструмент почти незаметен', trim((new DOMXPath($document))->query('//main/article[1]/h3')->item(0)->textContent));
    }

    public function testInvalidCategorySortAndPageReturnClientErrors(): void
    {
        self::assertSame(404, $this->page('missing')->status);
        self::assertSame(400, $this->page('technology', ['sort' => ['DROP TABLE articles']])->status);
        self::assertSame(404, $this->page('technology', ['page' => '3'])->status);
    }

    public function testEmptyCategoryHasAnEmptyState(): void
    {
        $response = $this->page('notebook');
        self::assertSame(200, $response->status);
        self::assertStringContainsString('В этой категории ещё нет опубликованных статей.', $response->body);
        self::assertStringNotContainsString('<article>', $response->body);
    }
}
