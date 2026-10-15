<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\RedisViewBufferRepository;
use Tests\Support\DatabaseTestCase;

final class RepositoryTest extends DatabaseTestCase
{
    public function testHomeHasThreeLatestPerNonemptyCategory(): void
    {
        $categories = new CategoryRepository($this->pdo)->published();
        self::assertCount(5, $categories);
        self::assertNotContains('notebook', array_column($categories, 'slug'));
        $groups = new ArticleRepository($this->pdo)->latestByCategory();
        foreach ($groups as $group) {
            self::assertCount(3, $group);
            self::assertNotContains('a-look-ahead', array_column($group, 'slug'));
        }
        self::assertSame(['technology-1', 'technology-2', 'life-1'],
            array_column($groups[$this->id('categories', 'technology')], 'slug'));
    }

    public function testPublicationCutoffIsSharedByCategoryCountsAndHome(): void
    {
        $cutoff = '2026-09-27 10:00:00';
        $articles = new ArticleRepository($this->pdo, $cutoff);
        $categories = new CategoryRepository($this->pdo, $cutoff);
        $id = $this->id('categories', 'technology');
        self::assertNotContains('technology-1', array_column($articles->latestByCategory()[$id], 'slug'));
        $category = array_values(
                        array_filter($categories->published(), fn(array $item): bool => (int)$item['id'] === $id)
                    )[0];
        self::assertSame(14, (int)$category['article_count']);
    }

    public function testLatestArticlesHaveStableOrderWhenDatesMatch(): void
    {
        $this->pdo->exec(
            "UPDATE articles SET published_at = '2026-12-01 00:00:00' WHERE slug IN ('technology-1', 'technology-2')"
        );
        $groups = new ArticleRepository($this->pdo, '2026-12-31 23:59:59')->latestByCategory();
        $group = $groups[$this->id('categories', 'technology')];
        self::assertSame(['technology-2', 'technology-1'], array_slice(array_column($group, 'slug'), 0, 2));
    }

    public function testCategoriesWithOnlyFutureArticlesAreHidden(): void
    {
        $this->pdo->prepare('UPDATE articles SET published_at = :published_at WHERE 1')->execute(['published_at' => '2099-01-01 00:00:00']);
        $cutoff = '2026-12-31 23:59:59';
        self::assertSame([], new CategoryRepository($this->pdo, $cutoff)->published());
        self::assertSame([], new ArticleRepository($this->pdo, $cutoff)->latestByCategory());
    }

    public function testHomeLoadsArticlesAndCategoryDetailsInOneSelect(): void
    {
        $status = $this->pdo->prepare('SHOW SESSION STATUS LIKE :metric');
        $status->execute(['metric' => 'Com_select']);
        $before = (int)$status->fetch()['Value'];
        $groups = new ArticleRepository($this->pdo)->latestByCategory();
        $status->execute(['metric' => 'Com_select']);
        $after = (int)$status->fetch()['Value'];
        self::assertSame(1, $after - $before);
        self::assertFalse($this->pdo->inTransaction());
        $post = $groups[$this->id('categories', 'technology')][0];
        self::assertSame('technology', $post['category_slug']);
        self::assertSame('Технологии', $post['category_name']);
        self::assertSame(15, (int) $post['article_count']);
    }

    public function testHomePageDataIncludesLiveRedisViewTotals(): void
    {
        $articleId = $this->id('articles', 'technology-1');
        $buffer = new RedisViewBufferRepository($this->redis);
        self::assertTrue($buffer->record($articleId, str_repeat('a', 64), 120));

        $data = new ArticleRepository($this->pdo, null, $buffer)->getHomePageData();
        $articles = $data['groups'][$this->id('categories', 'technology')];
        $matches = array_filter($articles, static fn(array $item): bool => (int)$item['id'] === $articleId);
        $article = reset($matches);

        self::assertSame(121, (int)$article['views']);
    }
}
