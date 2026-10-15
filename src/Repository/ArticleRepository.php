<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ArticleRepository
{
    private string $publishedUntil;

    public function __construct(
        private readonly PDO $pdo,
        ?string $publishedUntil = null,
    ) {
        $this->publishedUntil = $publishedUntil ?? gmdate('Y-m-d H:i:s');
    }

    public function latestByCategory(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ranked.* FROM (
            SELECT a.id, a.slug, a.image_path, a.title, a.description, a.published_at, a.views,
                ac.category_id, c.slug AS category_slug, c.name AS category_name,
                c.description AS category_description,
                COUNT(*) OVER (PARTITION BY ac.category_id) AS article_count,
                ROW_NUMBER() OVER (PARTITION BY ac.category_id ORDER BY a.published_at DESC, a.id DESC) AS position
            FROM articles a JOIN article_category ac ON ac.article_id = a.id
            JOIN categories c ON c.id = ac.category_id
            WHERE a.published_at <= :published_until
        ) ranked WHERE ranked.position <= 3
        ORDER BY ranked.category_id, ranked.position'
        );
        $statement->execute(['published_until' => $this->publishedUntil]);
        $rows = $statement->fetchAll();
        $groups = [];

        foreach ($rows as $row) {
            $groups[$row['category_id']][] = $row;
        }

        return $groups;
    }

    public function getHomePageData(): array
    {
        $groups = $this->latestByCategory();
        $categories = [];
        foreach ($groups as $categoryId => $posts) {
            $first = $posts[0];
            $categories[] = [
                'id' => $categoryId,
                'slug' => $first['category_slug'],
                'name' => $first['category_name'],
                'description' => $first['category_description'],
                'article_count' => $first['article_count'],
            ];
        }

        return ['categories' => $categories, 'groups' => $groups];
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, slug, image_path, title, description, body, published_at, views
            FROM articles WHERE slug = :slug AND published_at <= :published_until'
        );
        $statement->execute(['slug' => $slug, 'published_until' => $this->publishedUntil]);

        return $statement->fetch() ?: null;
    }

    public function getPageData(string $slug): ?array
    {
        $article = $this->findBySlug($slug);
        if ($article === null) {
            return null;
        }

        $categories = $this->pdo->prepare(
            'SELECT c.id, c.slug, c.name, c.description
            FROM categories c
            JOIN article_category ac ON ac.category_id = c.id
            WHERE ac.article_id = :article_id ORDER BY c.id'
        );
        $categories->execute(['article_id' => $article['id']]);

        return [
            'article' => $article,
            'categories' => $categories->fetchAll(),
            'similar' => $this->similar((int)$article['id']),
        ];
    }

    public function similar(int $articleId, int $limit = 3): array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.id, a.slug, a.image_path, a.title, a.description, a.published_at, a.views,
                COUNT(*) AS shared_categories
            FROM article_category source
            JOIN article_category candidate ON candidate.category_id = source.category_id
            JOIN articles a ON a.id = candidate.article_id
            WHERE source.article_id = :source_id AND a.id <> :excluded_id
                AND a.published_at <= :published_until
            GROUP BY a.id, a.slug, a.image_path, a.title, a.description, a.published_at, a.views
            ORDER BY shared_categories DESC, a.published_at DESC, a.id DESC
            LIMIT :limit'
        );
        $statement->bindValue('source_id', $articleId, PDO::PARAM_INT);
        $statement->bindValue('excluded_id', $articleId, PDO::PARAM_INT);
        $statement->bindValue('published_until', $this->publishedUntil);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

}
