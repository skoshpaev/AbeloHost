<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CategoryRepository;

final class CategoryService
{
    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    public function getPage(string $slug, int $page, string $sort): ?array
    {
        return $this->categories->getPageData($slug, $page, $sort);
    }
}
