<?php
declare(strict_types=1);

namespace App\Service\Category\Api;

interface CategoryServiceInterface
{
    public function getPage(string $slug, int $page, string $sort): ?array;
}
