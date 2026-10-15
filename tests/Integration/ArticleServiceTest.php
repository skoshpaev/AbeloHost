<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\ArticleService;
use Tests\Support\DatabaseTestCase;

final class ArticleServiceTest extends DatabaseTestCase
{
    private function service(?string $publishedUntil = null): ArticleService
    {
        return new ArticleService(
            new ArticleRepository($this->pdo, $publishedUntil),
            new CategoryRepository($this->pdo, $publishedUntil),
        );
    }

    public function testReturnsArticleCategoriesAndThreeMostSimilarArticles(): void
    {
        $data = $this->service()->getPageData('technology-1');

        self::assertSame('technology-1', $data['article']['slug']);
        self::assertSame('Технологии', $data['categories'][0]['name']);
        self::assertCount(3, $data['similar']);
        self::assertNotContains('technology-1', array_column($data['similar'], 'slug'));
        self::assertSame('technology-2', $data['similar'][0]['slug']);
        self::assertFalse($this->pdo->inTransaction());
    }

    public function testSimilarArticlesPreferMoreSharedCategoriesThenNewest(): void
    {
        $candidate = $this->id('articles', 'life-1');
        $this->pdo->prepare('INSERT INTO article_category (article_id, category_id) VALUES (:article_id, :category_id)')
            ->execute(['article_id' => $candidate, 'category_id' => $this->id('categories', 'technology')]);

        $similar = $this->service()->getPageData('technology-1')['similar'];

        self::assertSame('life-1', $similar[0]['slug']);
        self::assertSame(2, (int)$similar[0]['shared_categories']);
        self::assertSame(1, (int)$similar[1]['shared_categories']);
    }

    public function testMissingAndUnpublishedArticlesAreNotReturned(): void
    {
        self::assertNull($this->service()->getPageData('missing'));
        self::assertNull($this->service('2026-09-27 10:00:00')->getPageData('technology-1'));
    }
}
