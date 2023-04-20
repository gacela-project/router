<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Exception;
use Gacela\Router\Entities\Request;
use Gacela\Router\Exceptions\NotFound404Exception;
use Gacela\Router\Handlers;
use Gacela\Router\Router;
use Gacela\Router\Routes;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithUnhandledException;
use GacelaTest\Feature\Router\Fixtures\UnhandledException;
use Generator;

final class ErrorHandlingTest extends HeaderTestCase
{
    public function test_respond_404_status_when_uri_does_not_match(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_OPTIONS;

        Router::configure(static function (): void {
        });

        self::assertSame([
            [
                'header' => 'HTTP/1.0 404 Not Found',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function test_respond_404_status_when_method_does_not_match(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Routes $routes): void {
            $routes->post('expected/uri', FakeController::class, 'basicAction');
        });

        self::assertSame([
            [
                'header' => 'HTTP/1.0 404 Not Found',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    /**
     * @dataProvider notMatchesMethodsProvider
     */
    public function test_respond_404_status_when_not_matches_match_methods(string $testMethod, array $givenMethods): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = $testMethod;

        Router::configure(static function (Routes $routes) use ($givenMethods): void {
            $routes->match($givenMethods, 'expected/uri', FakeController::class, 'basicAction');
        });

        self::assertSame([
            [
                'header' => 'HTTP/1.0 404 Not Found',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function notMatchesMethodsProvider(): Generator
    {
        yield [Request::METHOD_PUT, [Request::METHOD_GET, Request::METHOD_POST]];
        yield [Request::METHOD_OPTIONS, [Request::METHOD_GET, Request::METHOD_POST]];
        yield [Request::METHOD_GET, [Request::METHOD_PATCH, Request::METHOD_PUT, Request::METHOD_DELETE, Request::METHOD_POST]];
        yield [Request::METHOD_CONNECT, [
            Request::METHOD_GET, Request::METHOD_DELETE, Request::METHOD_HEAD, Request::METHOD_OPTIONS,
            Request::METHOD_PATCH, Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_TRACE,
        ]];
    }

    public function test_respond_500_status_when_unhandled_exception(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);
        });

        self::assertSame([
            [
                'header' => 'HTTP/1.1 500 Internal Server Error',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function test_handle_handled_exception_with_anonymous_function(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);

            $handlers->handle(UnhandledException::class, static function (): string {
                \Gacela\Router\header('HTTP/1.1 418 I\'m a teapot');
                return 'Handled!';
            });
        });

        $this->expectOutputString('Handled!');
        self::assertSame([
            [
                'header' => 'HTTP/1.1 418 I\'m a teapot',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function test_custom_404_handler(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Handlers $handlers): void {
            $handlers->handle(NotFound404Exception::class, static function (): string {
                \Gacela\Router\header('HTTP/1.1 418 I\'m a teapot');
                return 'Handled!';
            });
        });

        $this->expectOutputString('Handled!');
        self::assertSame([
            [
                'header' => 'HTTP/1.1 418 I\'m a teapot',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function test_custom_fallback_handler(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Handlers $handlers, Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);

            $handlers->handle(Exception::class, static function (): string {
                \Gacela\Router\header('HTTP/1.1 418 I\'m a teapot');
                return 'Handled!';
            });
        });

        $this->expectOutputString('Handled!');
        self::assertSame([
            [
                'header' => 'HTTP/1.1 418 I\'m a teapot',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }
}
