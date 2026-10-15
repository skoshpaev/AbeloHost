<?php
declare(strict_types=1);

namespace App\Http;

use App\Infrastructure\SmartyRenderer;
use Throwable;

final class Application
{
    public function __construct(private Router $router, private SmartyRenderer $renderer)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->router->dispatch($request);
        } catch (HttpException $error) {
            $response = new Response($this->renderer->render('errors/error.tpl', [
                'status' => $error->status,
                'message' => $error->getMessage(),
                'page_title' => $error->status . ' — Блог',
            ]), $error->status, $error->status === 405 ? ['Allow' => 'GET, HEAD'] : []);
        } catch (Throwable $error) {
            error_log((string) $error);
            $response = new Response($this->renderer->render('errors/error.tpl', [
                'status' => 500,
                'message' => 'Не удалось открыть страницу. Попробуйте немного позже.',
                'page_title' => 'Ошибка — Блог',
            ]), 500);
        }
        return new Response($response->body, $response->status, $response->headers + [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
