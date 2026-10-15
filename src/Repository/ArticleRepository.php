<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ArticleRepository
{
    private string $publishedUntil;

    public function __construct(private PDO $pdo, ?string $publishedUntil = null)
    {
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

}
