<?php

declare(strict_types=1);

namespace App\Service\Category;

use App\Repository\CategoryRepository;
use App\Service\Category\Api\CategoryServiceInterface;

final readonly class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private CategoryRepository $categories,
    ) {
    }˚

    public function getPage(string $slug, int $page, string $sort): ?array
    {
        return $this->categories->getPageData($slug, $page, $sort);
    }
}
