<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\HttpException;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\CategoryService;
use InvalidArgumentException;
use Tests\Support\DatabaseTestCase;

final class CategoryServiceTest extends DatabaseTestCase
{
    private function service(?string $publishedUntil = null): CategoryService
    {
        return new CategoryService(
            new CategoryRepository($this->pdo, $publishedUntil), new ArticleRepository($this->pdo, $publishedUntil),
        );
    }

    public function testReturnsPaginatedCategoryArticlesInDateOrder(): void
    {
        $data = $this->service()->getPageData('technology', 1, 'date');
        self::assertSame('technology', $data['category']['slug']);
        self::assertSame(15, $data['pagination']['total']);
        self::assertSame(2, $data['pagination']['pages']);
        self::assertCount(9, $data['articles']);
        self::assertSame('technology-1', $data['articles'][0]['slug']);
        self::assertSame('technology-8', $this->service()->getPageData('technology', 2, 'date')['articles'][0]['slug']);
    }

    public function testSortsByViewsWithDateAndIdTieBreakers(): void
    {
        $items = $this->service()->getPageData('technology', 1, 'views')['articles'];
        self::assertSame(['technology-12', 'technology-11', 'life-9'], array_slice(array_column($items, 'slug'), 0, 3));
        $this->pdo->exec("UPDATE articles SET views = 10, published_at = '2026-01-01 00:00:00'");
        $items = $this->service()->getPageData('technology', 1, 'views')['articles'];
        $ids = array_column($items, 'id');
        $sorted = $ids;
        rsort($sorted);
        self::assertSame($sorted, $ids);
    }

    public function testUnknownCategoryAndOutOfRangePage(): void
    {
        self::assertNull($this->service()->getPageData('missing', 1, 'date'));
        try {
            $this->service()->getPageData('technology', 3, 'date');
            self::fail('Out-of-range page was accepted.');
        } catch (HttpException $error) {
            self::assertSame(404, $error->status);
        }
    }

    public function testEmptyAndFutureOnlyCategoriesHaveOneEmptyPage(): void
    {
        $cutoff = '2026-12-31 23:59:59';
        $empty = $this->service($cutoff)->getPageData('notebook', 1, 'date');
        self::assertSame(0, $empty['pagination']['total']);
        self::assertSame(1, $empty['pagination']['pages']);
        self::assertSame([], $empty['articles']);
        $this->pdo->exec("UPDATE articles SET published_at = '2099-01-01 00:00:00'");
        self::assertSame([], $this->service($cutoff)->getPageData('technology', 1, 'date')['articles']);
    }

    public function testRepositoryRejectsUnrecognisedSortBeforeBuildingSql(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ArticleRepository($this->pdo))->inCategory(
            $this->id('categories', 'technology'),
            'date; DROP TABLE articles',
            9,
            0
        );
    }
}
