<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\ArticleRepository;

final class HomeService
{
    public function __construct(private readonly ArticleRepository $articles)
    {
    }

    public function getPageData(): array
    {
        $groups = $this->articles->latestByCategory();
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
}
