<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\SmartyRenderer;
use App\Repository\ArticleRepository;

final class ArticleController
{
    public function __construct(
        private readonly ArticleRepository $articlesRepository,
        private readonly SmartyRenderer $renderer,
    ) {
    }

    public function index(Request $request): Response
    {
        $slug = $request->routeParameter('slug');
        if ($slug === null) {
            throw new HttpException(404, 'Статья не найдена.');
        }

        $data = $this->articlesRepository->getPageData($slug);
        if ($data === null) {
            throw new HttpException(404, 'Статья не найдена.');
        }

        return new Response(
            $this->renderer->render(
                'article.tpl',
                $data + [
                    'page_title'       => $data['article']['title'] . ' — Блог',
                    'page_description' => $data['article']['description'],
                    'paragraphs'       => preg_split('/\R{2,}/u', trim($data['article']['body'])) ?: [],
                ]
            ), 200, ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }
}
