<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testMatchesSlugAndHead(): void
    {
        $router = new Router();
        $router->get(
            '~^/articles/(?P<slug>[a-z0-9-]+)$~D',
            static fn(Request $request): Response => new Response($request->routeParameter('slug') ?? '')
        );
        self::assertSame('technology-1', $router->dispatch(new Request('GET', '/articles/technology-1'))->body);
        self::assertSame('technology-1', $router->dispatch(new Request('HEAD', '/articles/technology-1'))->body);
    }

    public function testUnknownRouteReturns404(): void
    {
        try {
            (new Router())->dispatch(new Request('GET', '/missing'));
            self::fail('Unknown route was accepted.');
        } catch (HttpException $error) {
            self::assertSame(404, $error->status);
        }
    }

    public function testRejectsUnsupportedMethod(): void
    {
        $router = new Router();
        $router->get('~^/$~D', static fn(): Response => new Response('ok'));
        try {
            $router->dispatch(new Request('POST', '/'));
            self::fail('POST was accepted.');
        } catch (HttpException $error) {
            self::assertSame(405, $error->status);
        }
    }

    public function testRouteParametersAreAvailableOnRequestWithoutOverridingQuery(): void
    {
        $router = new Router();
        $router->get(
            '~^/categories/(?P<slug>[a-z0-9-]+)$~D',
            static fn(Request $request): Response => new Response(
                ($request->routeParameter('slug') ?? '') . ':' . (string)($request->query['slug'] ?? '')
            )
        );
        $response = $router->dispatch(new Request('GET', '/categories/real-category', ['slug' => 'query-value']));
        self::assertSame('real-category:query-value', $response->body);
    }
}
