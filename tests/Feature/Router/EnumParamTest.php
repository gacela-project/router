<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Handlers;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Exceptions\InvalidEnumValueException;
use Gacela\Router\Exceptions\UnsupportedParamTypeException;
use Gacela\Router\Router;
use GacelaTest\Feature\Router\Fixtures\FakeEnumController;
use GacelaTest\Feature\Router\Fixtures\FakeIntEnum;
use GacelaTest\Feature\Router\Fixtures\FakeStringEnum;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnumParamTest extends TestCase
{
    #[DataProvider('stringEnumProvider')]
    public function test_a_string_backed_enum_param_is_bound(string $value): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/status/{$value}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The enum is '{$value}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('status/{param}', FakeEnumController::class, 'stringEnumAction');
        });
        $router->run();
    }

    public static function stringEnumProvider(): Generator
    {
        foreach (FakeStringEnum::cases() as $case) {
            yield $case->value => [$case->value];
        }
    }

    #[DataProvider('intEnumProvider')]
    public function test_an_int_backed_enum_param_is_bound(int $value): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/level/{$value}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The enum is '{$value}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('level/{param}', FakeEnumController::class, 'intEnumAction');
        });
        $router->run();
    }

    public static function intEnumProvider(): Generator
    {
        foreach (FakeIntEnum::cases() as $case) {
            yield (string) $case->value => [$case->value];
        }
    }

    public function test_an_unknown_string_enum_value_throws_a_clear_exception(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/status/nope';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('status/{param}', FakeEnumController::class, 'stringEnumAction');

            $handlers->handle(
                InvalidEnumValueException::class,
                static fn (InvalidEnumValueException $exception): string => $exception->getMessage(),
            );
        });
        $router->run();

        $this->expectOutputString(
            "Invalid value 'nope' for backed enum 'GacelaTest\Feature\Router\Fixtures\FakeStringEnum'.",
        );
    }

    public function test_an_unknown_int_enum_value_throws_a_clear_exception(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/level/42';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('level/{param}', FakeEnumController::class, 'intEnumAction');

            $handlers->handle(
                InvalidEnumValueException::class,
                static fn (InvalidEnumValueException $exception): string => $exception->getMessage(),
            );
        });
        $router->run();

        $this->expectOutputString(
            "Invalid value '42' for backed enum 'GacelaTest\Feature\Router\Fixtures\FakeIntEnum'.",
        );
    }

    public function test_a_non_numeric_value_for_an_int_backed_enum_does_not_fall_through_to_zero(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/level/abc';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        // FakeIntEnum has a case backed by 0, so a naive (int) cast of 'abc'
        // would silently bind it. It must be rejected instead.
        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('level/{param}', FakeEnumController::class, 'intEnumAction');

            $handlers->handle(
                InvalidEnumValueException::class,
                static fn (InvalidEnumValueException $exception): string => $exception->getMessage(),
            );
        });
        $router->run();

        $this->expectOutputString(
            "Invalid value 'abc' for backed enum 'GacelaTest\Feature\Router\Fixtures\FakeIntEnum'.",
        );
    }

    public function test_a_pure_enum_param_is_still_unsupported(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/pure/Active';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes, Handlers $handlers): void {
            $routes->get('pure/{param}', FakeEnumController::class, 'pureEnumAction');

            $handlers->handle(
                UnsupportedParamTypeException::class,
                static fn (UnsupportedParamTypeException $exception): string => $exception->getMessage(),
            );
        });
        $router->run();

        $this->expectOutputString(
            "Unsupported param type 'GacelaTest\Feature\Router\Fixtures\FakePureEnum'. Must be a scalar or a backed enum.",
        );
    }
}
