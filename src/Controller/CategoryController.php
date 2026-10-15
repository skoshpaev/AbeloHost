<?php
declare(strict_types=1);

namespace App\Controller;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\SmartyRenderer;
use App\Model\Pagination;
use App\Service\CategoryService;

final class CategoryController
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly SmartyRenderer $renderer,
    ) {
    }

    public function index(Request $request): Response
    {
        $slug = $request->routeParameter('slug');
        if ($slug === null) {
            throw new HttpException(404, 'Категория не найдена.');
        }

        $page = Pagination::parsePage($request->query['page'] ?? null);
        $sort = Pagination::parseSort($request->query['sort'] ?? null);
        $data = $this->categories->getPage($slug, $page, $sort);
        if ($data === null) {
            throw new HttpException(404, 'Категория не найдена.');
        }

        return new Response($this->renderer->render('category.tpl', $data + [
            'page_title' => $data['category']['name'] . ' — Блог',
            'page_description' => $data['category']['description'],
        ]), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
