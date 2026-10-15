<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\ArticleRepository;

final class HomeService
{
    public function __construct(private readonly ArticleRepository $articles)
    {
    }

    public function getPage(): array
    {
        return $this->articles->getHomePageData();
    }
}
