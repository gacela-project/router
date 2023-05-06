<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithRequest;
use Generator;
use PHPUnit\Framework\TestCase;

final class RouterParamTest extends TestCase
{
    private const PROVIDER_TRIES = 10;

    public function test_pass_many_params_to_the_action(): void
    {
        $params = ['foo', 'bar', 'baz'];

        $_SERVER['REQUEST_URI'] = "https://example.org/{$params[0]}/{$params[1]}/{$params[2]}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The params are '{$params[0]}', '{$params[1]}' and '{$params[2]}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('{firstParam}/{secondParam}/{thirdParam}', FakeController::class, 'manyParamsAction');
        });
        $router->run();
    }

    public function test_pass_associated_params_by_name_to_the_action(): void
    {
        $params = ['foo', 'bar', 'baz'];

        $_SERVER['REQUEST_URI'] = "https://example.org/{$params[0]}/{$params[1]}/{$params[2]}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The params are '{$params[1]}', '{$params[0]}' and '{$params[2]}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('{secondParam}/{firstParam}/{thirdParam}', FakeController::class, 'manyParamsAction');
        });
        $router->run();
    }

    /**
     * @dataProvider stringProvider
     */
    public function test_pass_string_params_to_the_action(string $string): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/string/is/{$string}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'string' param is '{$string}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/string/is/{param}', FakeController::class, 'stringParamAction');
        });
        $router->run();
    }

    public function stringProvider(): Generator
    {
        for ($try = 0; $try < self::PROVIDER_TRIES; ++$try) {
            $randomString = 'word-' . mt_rand();
            yield $randomString => ['string' => $randomString];
        }
    }

    /**
     * @dataProvider intProvider
     */
    public function test_pass_int_params_to_the_action(string $int): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/integer/is/{$int}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'int' param is '{$int}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/integer/is/{param}', FakeController::class, 'intParamAction');
        });
        $router->run();
    }

    public function intProvider(): Generator
    {
        for ($try = 0; $try < self::PROVIDER_TRIES; ++$try) {
            $randomInt = (string)random_int(1, 9999);
            yield "#{$randomInt}" => ['int' => $randomInt];
        }
    }

    /**
     * @dataProvider floatProvider
     */
    public function test_pass_float_params_to_the_action(string $float): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/float/is/{$float}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'float' param is '{$float}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/float/is/{param}', FakeController::class, 'floatParamAction');
        });
        $router->run();
    }

    public function floatProvider(): Generator
    {
        for ($try = 0; $try < self::PROVIDER_TRIES; ++$try) {
            $randomFloat = (string)mt_rand();
            yield "#{$randomFloat}" => ['float' => $randomFloat];
        }
    }

    /**
     * @dataProvider boolProvider
     */
    public function test_pass_bool_params_to_the_action(string $given, string $expected): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/bool/is/{$given}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'bool' param is '{$expected}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/bool/is/{param}', FakeController::class, 'boolParamAction');
        });
        $router->run();
    }

    public function boolProvider(): iterable
    {
        yield 'true' => ['given' => 'true', 'expected' => 'true'];
        yield 'false' => ['given' => 'false', 'expected' => 'false'];
        yield '1' => ['given' => '1', 'expected' => 'true'];
        yield '0' => ['given' => '0', 'expected' => 'false'];
    }

    public function test_priori_post_params_over_get(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $_GET['name'] = 'Unexpected!';
        $_POST['name'] = 'Expected!';

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithRequest::class);
        });
        $router->run();
    }
}
