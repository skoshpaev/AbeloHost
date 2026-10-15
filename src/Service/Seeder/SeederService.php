<?php
declare(strict_types=1);

namespace App\Service\Seeder;

use App\Infrastructure\NamedLock;
use App\Infrastructure\TransactionRunner;
use App\Service\Seeder\Api\SeederServiceInterface;
use PDO;
use RuntimeException;
use Throwable;

final readonly class SeederService implements SeederServiceInterface
{
    public function __construct(
        private PDO $pdo,
        private string $fixtureFile,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function seed(): array
    {
        $fixtures = require $this->fixtureFile;
        foreach ($fixtures['articles'] as $article) {
            if ($article['categories'] === []) {
                throw new RuntimeException('Every article must have at least one category.');
            }
            foreach ($article['categories'] as $slug) {
                if (!isset($fixtures['categories'][$slug])) {
                    throw new RuntimeException("Unknown fixture category $slug.");
                }
            }
        }
        return new NamedLock($this->pdo)->run(
            'maintenance', function () use ($fixtures): array {
            return new TransactionRunner($this->pdo)->run(function () use ($fixtures): array {
                $categoryIds = [];
                $insertCategory = $this->pdo->prepare('INSERT INTO categories (slug, name, description)
                    VALUES (:slug, :name, :description) ON DUPLICATE KEY UPDATE id = categories.id');
                $findCategory = $this->pdo->prepare('SELECT id FROM categories WHERE slug = :slug');
                foreach ($fixtures['categories'] as $slug => $category) {
                    $insertCategory->execute(['slug' => $slug] + $category);
                    $findCategory->execute(['slug' => $slug]);
                    $categoryIds[$slug] = (int) $findCategory->fetchColumn();
                }
                $insertArticle = $this->pdo->prepare('INSERT INTO articles
                    (slug, image_path, title, description, body, published_at, views)
                    VALUES (:slug, :image_path, :title, :description, :body, :published_at, :views)
                    ON DUPLICATE KEY UPDATE id = articles.id');
                $findArticle = $this->pdo->prepare('SELECT id FROM articles WHERE slug = :slug');
                $insertLink = $this->pdo->prepare('INSERT INTO article_category (article_id, category_id)
                    VALUES (:article_id, :category_id) ON DUPLICATE KEY UPDATE article_id = article_category.article_id');
                foreach ($fixtures['articles'] as $article) {
                    $slugs = $article['categories'];
                    unset($article['categories']);
                    $insertArticle->execute($article);
                    $findArticle->execute(['slug' => $article['slug']]);
                    $articleId = (int) $findArticle->fetchColumn();
                    $ids = array_map(fn (string $slug): int => $categoryIds[$slug], $slugs);
                    sort($ids);
                    foreach ($ids as $categoryId) {
                        $insertLink->execute(['article_id' => $articleId, 'category_id' => $categoryId]);
                    }
                }
                return [
                    'categories' => (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
                    'articles' => (int) $this->pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
                ];
            });
        });
    }
}
