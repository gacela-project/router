<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router\Entities;

use Gacela\Router\Configure\Bindings;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Route;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    public function test_single_method_given_as_string(): void
    {
        $route = new Route(Request::METHOD_GET, 'expected/uri', FakeController::class, 'basicAction');

        self::assertTrue($route->requestMatches(self::request(Request::METHOD_GET, '/expected/uri')));
        self::assertFalse($route->requestMatches(self::request(Request::METHOD_POST, '/expected/uri')));
    }

    #[DataProvider('declaredMethodProvider')]
    public function test_every_declared_method_matches(string $requestMethod): void
    {
        $route = new Route(
            [Request::METHOD_GET, Request::METHOD_POST],
            'expected/uri',
            FakeController::class,
            'basicAction',
        );

        self::assertTrue($route->requestMatches(self::request($requestMethod, '/expected/uri')));
    }

    public static function declaredMethodProvider(): iterable
    {
        yield 'GET' => [Request::METHOD_GET];
        yield 'POST' => [Request::METHOD_POST];
    }

    public function test_undeclared_method_does_not_match(): void
    {
        $route = new Route(
            [Request::METHOD_GET, Request::METHOD_POST],
            'expected/uri',
            FakeController::class,
            'basicAction',
        );

        self::assertFalse($route->requestMatches(self::request(Request::METHOD_DELETE, '/expected/uri')));
    }

    public function test_declared_method_does_not_match_another_path(): void
    {
        $route = new Route(Request::METHOD_GET, 'expected/uri', FakeController::class, 'basicAction');

        self::assertFalse($route->requestMatches(self::request(Request::METHOD_GET, '/another/uri')));
    }

    public function test_middleware_is_shared_by_every_declared_method(): void
    {
        $route = new Route(
            [Request::METHOD_GET, Request::METHOD_POST],
            'expected/uri',
            FakeController::class,
            'basicAction',
        );

        $route->middleware('some-middleware');

        self::assertSame(['some-middleware'], $route->getMiddlewares());
        self::assertTrue($route->requestMatches(self::request(Request::METHOD_GET, '/expected/uri')));
        self::assertTrue($route->requestMatches(self::request(Request::METHOD_POST, '/expected/uri')));
    }

    public function test_run_resolves_the_controller_action(): void
    {
        $route = new Route(Request::METHOD_GET, 'expected/uri', FakeController::class, 'basicAction');

        $result = $route->run(new Bindings(), self::request(Request::METHOD_GET, '/expected/uri'));

        self::assertSame('Expected!', (string) $result);
    }

    private static function request(string $method, string $uri): Request
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return Request::fromGlobals();
    }
}
