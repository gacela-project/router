<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Handlers;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Exceptions\MethodNotAllowed405Exception;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class MethodNotAllowedTest extends HeaderTestCase
{
    public function test_static_path_with_the_wrong_method_responds_405(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_POST;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();

        self::assertSame([
            ['header' => 'HTTP/1.1 405 Method Not Allowed', 'replace' => true, 'response_code' => 0],
            ['header' => 'Allow: GET', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_dynamic_path_with_the_wrong_method_responds_405(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/value';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_DELETE;

        $router = new Router(static function (Routes $routes): void {
            $routes->post('expected/{param}', FakeController::class, 'stringParamAction');
        });
        $router->run();

        self::assertSame([
            ['header' => 'HTTP/1.1 405 Method Not Allowed', 'replace' => true, 'response_code' => 0],
            ['header' => 'Allow: POST', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_allow_lists_every_method_registered_for_the_path(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_DELETE;

        $router = new Router(static function (Routes $routes): void {
            $routes->match(
                [Request::METHOD_POST, Request::METHOD_GET],
                'expected/uri',
                FakeController::class,
                'basicAction',
            );
        });
        $router->run();

        // Declared POST-then-GET, reported in the canonical order of ALL_METHODS.
        self::assertSame([
            ['header' => 'HTTP/1.1 405 Method Not Allowed', 'replace' => true, 'response_code' => 0],
            ['header' => 'Allow: GET, POST', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_allow_does_not_repeat_a_method_registered_twice_for_the_path(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_DELETE;

        $router = new Router(static function (Routes $routes): void {
            // The same method reachable through both a static and a dynamic route.
            $routes->get('expected/uri', FakeController::class, 'basicAction');
            $routes->get('expected/{param}', FakeController::class, 'stringParamAction');
        });
        $router->run();

        self::assertSame([
            ['header' => 'HTTP/1.1 405 Method Not Allowed', 'replace' => true, 'response_code' => 0],
            ['header' => 'Allow: GET', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    /**
     * @param list<string> $givenMethods
     */
    #[DataProvider('notMatchesMethodsProvider')]
    public function test_responds_405_for_any_unregistered_method(
        string $testMethod,
        array $givenMethods,
        string $expectedAllow,
    ): void {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = $testMethod;

        $router = new Router(static function (Routes $routes) use ($givenMethods): void {
            $routes->match($givenMethods, 'expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();

        self::assertSame([
            ['header' => 'HTTP/1.1 405 Method Not Allowed', 'replace' => true, 'response_code' => 0],
            ['header' => "Allow: {$expectedAllow}", 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public static function notMatchesMethodsProvider(): Generator
    {
        yield [Request::METHOD_PUT, [Request::METHOD_GET, Request::METHOD_POST], 'GET, POST'];
        yield [Request::METHOD_OPTIONS, [Request::METHOD_GET, Request::METHOD_POST], 'GET, POST'];
        yield [
            Request::METHOD_GET,
            [Request::METHOD_PATCH, Request::METHOD_PUT, Request::METHOD_DELETE, Request::METHOD_POST],
            'DELETE, PATCH, POST, PUT',
        ];
        yield [
            Request::METHOD_CONNECT,
            [
                Request::METHOD_GET, Request::METHOD_DELETE, Request::METHOD_HEAD, Request::METHOD_OPTIONS,
                Request::METHOD_PATCH, Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_TRACE,
            ],
            'DELETE, GET, HEAD, OPTIONS, PATCH, POST, PUT, TRACE',
        ];
    }

    public function test_an_unknown_path_still_responds_404(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/nothing/here';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();

        self::assertSame([
            ['header' => 'HTTP/1.0 404 Not Found', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_no_route_at_all_still_responds_404(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (): void {
        });
        $router->run();

        self::assertSame([
            ['header' => 'HTTP/1.0 404 Not Found', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    #[DataProvider('matchingMethodProvider')]
    public function test_a_registered_method_still_resolves_normally(string $method): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = $method;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->match(
                [Request::METHOD_GET, Request::METHOD_POST],
                'expected/uri',
                FakeController::class,
                'basicAction',
            );
        });
        $router->run();

        self::assertSame([], $this->headers());
    }

    public static function matchingMethodProvider(): Generator
    {
        yield 'GET' => [Request::METHOD_GET];
        yield 'POST' => [Request::METHOD_POST];
    }

    public function test_the_405_handler_can_be_replaced(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_POST;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');

            $handlers->handle(
                MethodNotAllowed405Exception::class,
                static fn (MethodNotAllowed405Exception $exception): string => 'Try: '
                    . implode('/', $exception->allowedMethods()),
            );
        });
        $router->run();

        $this->expectOutputString('Try: GET');
        self::assertSame([], $this->headers());
    }
}
