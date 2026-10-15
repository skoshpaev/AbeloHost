<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;

final class ArticleService
{
    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly CategoryRepository $categories,
    ) {
    }

    public function getPageData(string $slug): ?array
    {
        $article = $this->articles->findBySlug($slug);
        if ($article === null) {
            return null;
        }

        return [
            'article' => $article,
            'categories' => $this->categories->forArticle((int)$article['id']),
            'similar' => $this->articles->similar((int)$article['id']),
        ];
    }
}
