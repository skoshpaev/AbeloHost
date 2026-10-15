<?php
declare(strict_types=1);

namespace App\Http;

final readonly class Request
{
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public array $routeParameters = [],
        public array $cookies = [],
        public bool $secure = false,
    ) {
    }

    public function withRouteParameters(array $parameters): self
    {
        return new self($this->method, $this->path, $this->query, $parameters, $this->cookies, $this->secure);
    }

    public function routeParameter(string $name): ?string
    {
        $value = $this->routeParameters[$name] ?? null;
        return is_string($value) ? $value : null;
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            is_string($path) ? $path : '/',
            $_GET,
            [],
            $_COOKIE,
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off',
        );
    }
}
