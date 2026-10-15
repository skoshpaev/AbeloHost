<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response;
use App\Infrastructure\SmartyRenderer;
use App\Repository\ArticleRepository;

final readonly class HomeController
{
    public function __construct(
        private SmartyRenderer $renderer,
        private ArticleRepository $articles
    ) {
    }

    public function index(): Response
    {
        $data = $this->articles->getHomePageData();

        return new Response($this->renderer->render('home.tpl', $data), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
