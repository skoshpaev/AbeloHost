<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\SmartyRenderer;
use App\Service\HomeService;
use Throwable;

final class HomeController
{
    public function __construct(
        private readonly HomeService $home,
        private readonly SmartyRenderer $renderer,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function index(Request $request): Response
    {
        $data = $this->home->getPageData();

        return new Response($this->renderer->render('home.tpl', $data), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
