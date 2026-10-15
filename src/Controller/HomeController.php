<?php
declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\SmartyRenderer;

final class HomeController
{
    public function __construct(private SmartyRenderer $renderer)
    {
    }

    public function index(Request $request): Response
    {
        $body = $this->renderer->render('home.tpl', [
            'heading' => 'Блог',
            'message' => 'Приложение запущено.',
        ]);
        return new Response($body, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
