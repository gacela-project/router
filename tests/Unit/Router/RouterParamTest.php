<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router;

use Gacela\Router\Router;
use Gacela\Router\Routes;
use GacelaTest\Unit\Router\Fixtures\FakeController;
use Generator;
use PHPUnit\Framework\TestCase;

final class RouterParamTest extends TestCase
{
    private const PROVIDER_TRIES = 10;

    public function test_pass_many_params_to_the_action(): void
    {
        $params = ['foo', 'bar', 'baz'];

        $_SERVER['REQUEST_URI'] = "https://example.org/{$params[0]}/{$params[1]}/{$params[2]}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The params are '{$params[0]}', '{$params[1]}' and '{$params[2]}'!");

        Router::configure(static function (Routes $routes): void {
            $routes->get('{firstParam}/{secondParam}/{thirdParam}', FakeController::class, 'manyParamsAction');
        });
    }

    public function test_pass_associated_params_by_name_to_the_action(): void
    {
        $params = ['foo', 'bar', 'baz'];

        $_SERVER['REQUEST_URI'] = "https://example.org/{$params[0]}/{$params[1]}/{$params[2]}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The params are '{$params[1]}', '{$params[0]}' and '{$params[2]}'!");

        Router::configure(static function (Routes $routes): void {
            $routes->get('{secondParam}/{firstParam}/{thirdParam}', FakeController::class, 'manyParamsAction');
        });
    }

    /**
     * @dataProvider stringProvider
     */
    public function test_pass_string_params_to_the_action(string $string): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/string/is/{$string}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The 'string' param is '{$string}'!");

        Router::configure(static function (Routes $routes): void {
            $routes->get('expected/string/is/{param}', FakeController::class, 'stringParamAction');
        });
    }

    public function stringProvider(): Generator
    {
        for ($try = 0; $try < self::PROVIDER_TRIES; ++$try) {
            $randomString = (string)'word-' . mt_rand();
            yield $randomString => ['string' => $randomString];
        }
    }

    /**
     * @dataProvider intProvider
     */
    public function test_pass_int_params_to_the_action(string $int): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/integer/is/{$int}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The 'int' param is '{$int}'!");

        Router::configure(static function (Routes $routes): void {
            $routes->get('expected/integer/is/{param}', FakeController::class, 'intParamAction');
        });
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
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The 'float' param is '{$float}'!");

        Router::configure(static function (Routes $routes): void {
            $routes->get('expected/float/is/{param}', FakeController::class, 'floatParamAction');
        });
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
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The 'bool' param is '{$expected}'!");

        Router::configure(static function (Routes $routes): void {
            $routes->get('expected/bool/is/{param}', FakeController::class, 'boolParamAction');
        });
    }

    public function boolProvider(): iterable
    {
        yield 'true' => ['given' => 'true', 'expected' => 'true'];
        yield 'false' => ['given' => 'false', 'expected' => 'false'];
        yield '1' => ['given' => '1', 'expected' => 'true'];
        yield '0' => ['given' => '0', 'expected' => 'false'];
    }
}
