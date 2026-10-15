<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Pagination;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;

final class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly ArticleRepository $articles,
    ) {
    }

    public function getPageData(string $slug, int $page, string $sort): ?array
    {
        $category = $this->categories->findBySlug($slug);
        if ($category === null) {
            return null;
        }

        $pagination = Pagination::build($page, $this->articles->countInCategory((int)$category['id']));
        return [
            'category'   => $category,
            'pagination' => $pagination,
            'articles'   => $this->articles->inCategory(
                (int)$category['id'],
                $sort,
                Pagination::PER_PAGE,
                ($page - 1) * Pagination::PER_PAGE,
            ),
            'sort'       => $sort,
        ];
    }
}
