<?php
declare(strict_types=1);

namespace App\Http;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = [$pattern, $handler];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as [$pattern, $handler]) {
            if (preg_match($pattern, $request->path, $matches) === 1) {
                if (!in_array($request->method, ['GET', 'HEAD'], true)) {
                    throw new HttpException(405, 'Метод запроса не поддерживается.');
                }
                $parameters = array_filter($matches, fn ($key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
                return $handler($request, ...array_values($parameters));
            }
        }
        throw new HttpException(404, 'Страница не найдена.');
    }
}
