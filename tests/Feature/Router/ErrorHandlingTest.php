<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Exception;
use Gacela\Router\Entities\Request;
use Gacela\Router\Exceptions\NotFound404Exception;
use Gacela\Router\Exceptions\UnsupportedParamTypeException;
use Gacela\Router\Exceptions\UnsupportedResponseTypeException;
use Gacela\Router\Handlers;
use Gacela\Router\Router;
use Gacela\Router\Routes;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithUnhandledException;
use GacelaTest\Feature\Router\Fixtures\UnhandledException;
use Generator;
use stdClass;

final class ErrorHandlingTest extends HeaderTestCase
{
    public function test_respond_404_status_when_uri_does_not_match(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_OPTIONS;

        $router = new Router(static function (): void {
        });
        $router->run();

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

        $router = new Router(static function (Routes $routes): void {
            $routes->post('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();

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

        $router = new Router(static function (Routes $routes) use ($givenMethods): void {
            $routes->match($givenMethods, 'expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();

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

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);
        });
        $router->run();

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

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);

            $handlers->handle(UnhandledException::class, static function (): string {
                \Gacela\Router\header('HTTP/1.1 418 I\'m a teapot');
                return 'Handled!';
            });
        });
        $router->run();

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

        $router = new Router(static function (Handlers $handlers): void {
            $handlers->handle(NotFound404Exception::class, static function (NotFound404Exception $exception): string {
                \Gacela\Router\header('HTTP/1.1 418 I\'m a teapot');
                return "'{$exception->getMessage()}' Handled!";
            });
        });
        $router->run();

        $this->expectOutputString("'Error 404 - Not Found' Handled!");
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

        $router = new Router(static function (Handlers $handlers, Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);

            $handlers->handle(Exception::class, static function (): string {
                \Gacela\Router\header('HTTP/1.1 418 I\'m a teapot');
                return 'Handled!';
            });
        });
        $router->run();

        $this->expectOutputString('Handled!');
        self::assertSame([
            [
                'header' => 'HTTP/1.1 418 I\'m a teapot',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function test_handle_handled_exception_with_anonymous_class(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Handlers $handlers, Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);

            $handlers->handle(UnhandledException::class, new class() {
                public function __invoke(): string
                {
                    \Gacela\Router\header('HTTP/1.1 418 I\'m a teapot');
                    return 'Handled!';
                }
            });
        });
        $router->run();

        $this->expectOutputString('Handled!');
        self::assertSame([
            [
                'header' => 'HTTP/1.1 418 I\'m a teapot',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    /**
     * @dataProvider nonStringProvider
     *
     * @param mixed $given
     * @param mixed $type
     */
    public function test_throws_exception_if_response_is_not_a_string_or_stringable($given, $type): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Handlers $handlers, Routes $routes) use ($given): void {
            $routes->get('expected/uri', static fn () => $given);

            $handlers->handle(
                UnsupportedResponseTypeException::class,
                static fn (UnsupportedResponseTypeException $exception): string => $exception->getMessage(),
            );
        });
        $router->run();

        $this->expectOutputString("Unsupported response type '{$type}'. Must be a string or implement Stringable interface.");
    }

    public function nonStringProvider(): Generator
    {
        yield [42, 'integer'];
        yield [false, 'boolean'];
        yield [[], 'array'];
        yield [new stdClass(), 'stdClass'];
    }

    public function test_throws_exception_when_param_has_no_type(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/param/is/any';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/param/is/{param}', FakeController::class, 'nonTypedParam');

            $handlers->handle(
                UnsupportedParamTypeException::class,
                static fn (UnsupportedParamTypeException $exception): string => $exception->getMessage(),
            );
        });
        $router->run();

        $this->expectOutputString('Unsupported non-typed param. Must be a scalar.');
    }

    public function test_throws_exception_when_param_is_no_scalar(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/param/is/array';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/param/is/{param}', FakeController::class, 'nonScalarParam');

            $handlers->handle(
                UnsupportedParamTypeException::class,
                static fn (UnsupportedParamTypeException $exception): string => $exception->getMessage(),
            );
        });
        $router->run();

        $this->expectOutputString("Unsupported param type 'array'. Must be a scalar.");
    }

    public function test_configure_throws_unsupported_closure_param(): void
    {
        $this->expectExceptionMessage("'unrecognised' parameter in configuration Closure for Router must be from types Routes, Bindings or Handlers.");

        new Router(static function ($unrecognised): void {});
    }

    public function test_configure_non_callable_handler(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectExceptionMessage('Handler assigned to \'GacelaTest\Feature\Router\Fixtures\UnhandledException\' exception cannot be called.');

        $router = new Router(static function (Handlers $handlers, Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);

            $handlers->handle(UnhandledException::class, 'non-callable');
        });
        $router->run();
    }
}
