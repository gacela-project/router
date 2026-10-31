<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Exception;
use Gacela\Router\Configure\Handlers;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Exceptions\NotFound404Exception;
use Gacela\Router\Exceptions\UnsupportedParamTypeException;
use Gacela\Router\Exceptions\UnsupportedResponseTypeException;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithError;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithUnhandledException;
use GacelaTest\Feature\Router\Fixtures\UnhandledException;
use Generator;
use JsonSerializable;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use stdClass;
use Throwable;
use TypeError;
use UnexpectedValueException;

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

    public function test_an_authority_lookalike_uri_does_not_reach_the_route_it_ends_with(): void
    {
        $_SERVER['REQUEST_URI'] = '//evil.example/admin';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('admin', FakeController::class, 'basicAction');
        });
        $router->run();

        self::assertContains('HTTP/1.0 404 Not Found', array_column($this->headers(), 'header'));
    }

    // A known path requested with an unregistered method is a 405, not a 404.
    // See MethodNotAllowedTest.

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

    public function test_respond_500_status_when_unhandled_error(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithError::class);
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

    public function test_handle_error_by_its_own_class(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', FakeControllerWithError::class);

            $handlers->handle(
                TypeError::class,
                static fn (TypeError $error): string => "Handled '{$error->getMessage()}'!",
            );
        });
        $router->run();

        $this->expectOutputString("Handled 'failed'!");
    }

    public function test_a_throwable_handler_catches_an_error(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', FakeControllerWithError::class);

            $handlers->handle(Throwable::class, static fn (): string => 'Handled!');
        });
        $router->run();

        $this->expectOutputString('Handled!');
    }

    public function test_an_error_does_not_reach_an_exception_handler(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        // A handler registered for Exception::class may type-hint Exception, so an
        // Error must not be routed to it. It falls through to the Throwable one.
        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', FakeControllerWithError::class);

            $handlers->handle(Exception::class, static fn (Exception $exception): string => 'Exception handler!');
            $handlers->handle(Throwable::class, static fn (Throwable $throwable): string => 'Throwable handler!');
        });
        $router->run();

        $this->expectOutputString('Throwable handler!');
    }

    public function test_a_throwable_handler_catches_an_exception_with_no_closer_handler(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);

            $handlers->handle(Throwable::class, static fn (): string => 'Throwable handler!');
        });
        $router->run();

        $this->expectOutputString('Throwable handler!');
    }

    public function test_a_handler_also_catches_the_subclasses_of_its_exception(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', static fn (): string => throw new UnexpectedValueException('failed'));

            $handlers->handle(RuntimeException::class, static fn (): string => 'Runtime handler!');
        });
        $router->run();

        $this->expectOutputString('Runtime handler!');
    }

    public function test_the_closest_handler_wins(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', static fn (): string => throw new UnexpectedValueException('failed'));

            $handlers->handle(Throwable::class, static fn (): string => 'Throwable handler!');
            $handlers->handle(Exception::class, static fn (): string => 'Exception handler!');
            $handlers->handle(RuntimeException::class, static fn (): string => 'Runtime handler!');
            $handlers->handle(UnexpectedValueException::class, static fn (): string => 'Exact handler!');
        });
        $router->run();

        $this->expectOutputString('Exact handler!');
    }

    public function test_an_interface_handler_catches_an_exception_implementing_it(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', static fn (): string => throw new class('failed') extends Exception implements JsonSerializable {
                public function jsonSerialize(): string
                {
                    return 'unused';
                }
            });

            $handlers->handle(Throwable::class, static fn (): string => 'Throwable handler!');
            $handlers->handle(JsonSerializable::class, static fn (): string => 'Interface handler!');
        });
        $router->run();

        $this->expectOutputString('Interface handler!');
    }

    public function test_a_class_handler_beats_an_interface_handler(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', static fn (): string => throw new class('failed') extends RuntimeException implements JsonSerializable {
                public function jsonSerialize(): string
                {
                    return 'unused';
                }
            });

            $handlers->handle(JsonSerializable::class, static fn (): string => 'Interface handler!');
            $handlers->handle(RuntimeException::class, static fn (): string => 'Runtime handler!');
        });
        $router->run();

        $this->expectOutputString('Runtime handler!');
    }

    public function test_the_built_in_404_handler_still_wins_over_a_user_catch_all(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/unknown';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Handlers $handlers): void {
            $handlers->handle(Exception::class, static fn (): string => 'Exception handler!');
            $handlers->handle(Throwable::class, static fn (): string => 'Throwable handler!');
        });
        $router->run();

        self::assertSame(['HTTP/1.0 404 Not Found'], array_column($this->headers(), 'header'));
    }

    public function test_an_error_with_only_an_exception_handler_falls_back_to_the_built_in_500(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', FakeControllerWithError::class);

            $handlers->handle(Exception::class, static fn (Exception $exception): string => 'Exception handler!');
        });
        $router->run();

        $this->expectOutputString('');
        self::assertSame(['HTTP/1.1 500 Internal Server Error'], array_column($this->headers(), 'header'));
    }

    public function test_an_exception_handler_beats_the_throwable_one_for_an_exception(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('expected/uri', FakeControllerWithUnhandledException::class);

            $handlers->handle(Exception::class, static fn (): string => 'Exception handler!');
            $handlers->handle(Throwable::class, static fn (): string => 'Throwable handler!');
        });
        $router->run();

        $this->expectOutputString('Exception handler!');
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
     * @param mixed $given
     * @param mixed $type
     */
    #[DataProvider('nonStringProvider')]
    public function test_throws_exception_if_response_is_not_a_string_array_or_stringable($given, $type): void
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

        $this->expectOutputString(
            "Unsupported response type '{$type}'. Must be a string, an array, or implement Stringable interface.",
        );
    }

    public static function nonStringProvider(): Generator
    {
        yield [42, 'integer'];
        yield [false, 'boolean'];
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

        $this->expectOutputString('Unsupported non-typed param. Must be a scalar or a backed enum.');
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

        $this->expectOutputString("Unsupported param type 'array'. Must be a scalar or a backed enum.");
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
