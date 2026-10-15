<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CategoryRepository
{
    private string $publishedUntil;

    public function __construct(private PDO $pdo, ?string $publishedUntil = null)
    {
        $this->publishedUntil = $publishedUntil ?? gmdate('Y-m-d H:i:s');
    }

    public function published(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.slug, c.name, c.description, COUNT(*) AS article_count
            FROM categories c
            JOIN article_category ac ON ac.category_id = c.id
            JOIN articles a ON a.id = ac.article_id
            WHERE a.published_at <= :published_until
            GROUP BY c.id, c.slug, c.name, c.description
            ORDER BY c.id'
        );
        $statement->execute(['published_until' => $this->publishedUntil]);
        return $statement->fetchAll();
    }

}
