<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $routeParameters = [],
    ) {
    }

    public function withRouteParameters(array $parameters): self
    {
        return new self($this->method, $this->path, $this->query, $parameters);
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
        );
    }
}
