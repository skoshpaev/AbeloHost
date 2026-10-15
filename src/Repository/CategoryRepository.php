<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Pagination;
use InvalidArgumentException;
use PDO;

final class CategoryRepository
{
    private string $publishedUntil;

    public function __construct(
        private readonly PDO $pdo,
        ?string $publishedUntil = null,
    ) {
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

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, slug, name, description FROM categories WHERE slug = :slug');
        $statement->execute(['slug' => $slug]);

        return $statement->fetch() ?: null;
    }

    public function getPageData(string $slug, int $page, string $sort): ?array
    {
        $category = $this->findBySlug($slug);
        if ($category === null) {
            return null;
        }

        $order = match ($sort) {
            'views' => 'a.views DESC, a.published_at DESC, a.id DESC',
            'date' => 'a.published_at DESC, a.id DESC',
            default => throw new InvalidArgumentException('Unknown sort.'),
        };
        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM articles a
            JOIN article_category ac ON ac.article_id = a.id
            WHERE ac.category_id = :category_id AND a.published_at <= :published_until'
        );
        $count->execute([
            'category_id' => $category['id'],
            'published_until' => $this->publishedUntil,
        ]);
        $pagination = Pagination::build($page, (int)$count->fetchColumn());

        $statement = $this->pdo->prepare(
            "SELECT a.id, a.slug, a.image_path, a.title, a.description, a.published_at, a.views
            FROM articles a JOIN article_category ac ON ac.article_id = a.id
            WHERE ac.category_id = :category_id AND a.published_at <= :published_until
            ORDER BY {$order} LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue('category_id', (int)$category['id'], PDO::PARAM_INT);
        $statement->bindValue('published_until', $this->publishedUntil);
        $statement->bindValue('limit', Pagination::PER_PAGE, PDO::PARAM_INT);
        $statement->bindValue('offset', ($page - 1) * Pagination::PER_PAGE, PDO::PARAM_INT);
        $statement->execute();

        return [
            'category' => $category,
            'pagination' => $pagination,
            'articles' => $statement->fetchAll(),
            'sort' => $sort,
        ];
    }
}
