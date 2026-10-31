<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Handlers;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Exceptions\InvalidParamValueException;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use GacelaTest\Feature\Router\Fixtures\FakeEnumController;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class InvalidParamValueTest extends HeaderTestCase
{
    #[DataProvider('invalidValueProvider')]
    public function test_a_value_that_does_not_fit_its_type_is_a_404_and_never_reaches_the_action(
        string $action,
        string $value,
    ): void {
        $this->get("/typed/{$value}", $action);

        $this->expectOutputString('');
        self::assertSame(['HTTP/1.0 404 Not Found'], array_column($this->headers(), 'header'));
    }

    public static function invalidValueProvider(): Generator
    {
        yield 'int, letters' => ['intParamAction', 'abc'];
        yield 'int, trailing letters' => ['intParamAction', '12abc'];
        yield 'int, a fraction' => ['intParamAction', '1.5'];
        yield 'int, leading zero' => ['intParamAction', '01'];
        yield 'int, overflow' => ['intParamAction', '9223372036854775808'];
        yield 'float, letters' => ['floatParamAction', 'abc'];
        yield 'float, trailing letters' => ['floatParamAction', '1.5x'];
        yield 'bool, letters' => ['boolParamAction', 'abc'];
        yield 'bool, a list' => ['boolParamAction', '[1]'];
        yield 'bool, a number that is not 0 or 1' => ['boolParamAction', '2'];
    }

    #[DataProvider('validValueProvider')]
    public function test_a_value_that_fits_its_type_reaches_the_action(
        string $action,
        string $value,
        string $expected,
    ): void {
        $this->get("/typed/{$value}", $action);

        $this->expectOutputString($expected);
    }

    public static function validValueProvider(): Generator
    {
        yield 'int, negative' => ['intParamAction', '-3', "The 'int' param is '-3'!"];
        yield 'int, plus sign' => ['intParamAction', '+7', "The 'int' param is '7'!"];
        yield 'float, an integer' => ['floatParamAction', '2', "The 'float' param is '2'!"];
        yield 'float, leading zeros' => ['floatParamAction', '007', "The 'float' param is '7'!"];
        yield 'float, no integer part' => ['floatParamAction', '.5', "The 'float' param is '0.5'!"];
        yield 'float, an exponent' => ['floatParamAction', '1e3', "The 'float' param is '1000'!"];
        yield 'bool, yes' => ['boolParamAction', 'yes', "The 'bool' param is 'true'!"];
        yield 'bool, on' => ['boolParamAction', 'on', "The 'bool' param is 'true'!"];
        yield 'bool, TRUE' => ['boolParamAction', 'TRUE', "The 'bool' param is 'true'!"];
        yield 'bool, no' => ['boolParamAction', 'no', "The 'bool' param is 'false'!"];
        yield 'bool, off' => ['boolParamAction', 'off', "The 'bool' param is 'false'!"];
        yield 'bool, False' => ['boolParamAction', 'False', "The 'bool' param is 'false'!"];
    }

    public function test_an_unknown_enum_case_is_a_404_by_default(): void
    {
        $_SERVER['REQUEST_URI'] = '/status/nope';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('status/{param}', FakeEnumController::class, 'stringEnumAction');
        });
        $router->run();

        $this->expectOutputString('');
        self::assertSame(['HTTP/1.0 404 Not Found'], array_column($this->headers(), 'header'));
    }

    public function test_one_handler_can_answer_for_scalar_and_enum_params(): void
    {
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $outputs = [];

        $configure = static function (Routes $routes, Handlers $handlers): void {
            $routes->get('number/{param}', FakeController::class, 'intParamAction');
            $routes->get('status/{param}', FakeEnumController::class, 'stringEnumAction');

            $handlers->handle(
                InvalidParamValueException::class,
                static fn (InvalidParamValueException $exception): string => $exception->getMessage(),
            );
        };

        foreach (['/number/abc', '/status/nope'] as $uri) {
            $_SERVER['REQUEST_URI'] = $uri;

            ob_start();
            (new Router($configure))->run();
            $outputs[] = (string)ob_get_clean();
        }

        self::assertSame("Invalid value 'abc' for route parameter 'param' of type 'int'.", $outputs[0]);
        self::assertStringStartsWith("Invalid value 'nope' for backed enum", $outputs[1]);
    }

    private function get(string $uri, string $action): void
    {
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes) use ($action): void {
            $routes->get('typed/{param}', FakeController::class, $action);
        });
        $router->run();
    }
}
