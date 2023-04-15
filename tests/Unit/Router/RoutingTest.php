<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router;

use Gacela\Router\Request;
use Gacela\Router\Routing;
use Gacela\Router\RoutingConfigurator;
use Gacela\Router\UnsupportedHttpMethodException;
use GacelaTest\Unit\Router\Fake\Name;
use GacelaTest\Unit\Router\Fake\NameInterface;
use Generator;
use PHPUnit\Framework\TestCase;

final class RoutingTest extends TestCase
{
    private const PROVIDER_TRIES = 10;

    protected function setUp(): void
    {
        Request::resetCache();
    }

    public function test_it_should_respond_if_everything_matches(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('Expected!');

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
    }

    public function test_it_should_not_respond_if_the_uri_does_not_matches(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/unexpected/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('');

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->get('other/uri', FakeController::class, 'basicAction');
        });
    }

    public function test_it_should_not_respond_if_the_method_does_not_matches(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('');

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->post('expected/uri', FakeController::class, 'basicAction');
        });
    }

    public function test_it_should_pass_many_params_to_the_action(): void
    {
        /** @var list<string> $params */
        $params = ['foo', 'bar', 'baz'];

        $_SERVER['REQUEST_URI'] = "https://example.org/{$params[0]}/{$params[1]}/{$params[2]}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The params are '{$params[0]}', '{$params[1]}' and '{$params[2]}'!");

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->get('{firstParam}/{secondParam}/{thirdParam}', FakeController::class, 'manyParamsAction');
        });
    }

    public function test_it_should_pass_associated_params_by_name_to_the_action(): void
    {
        /** @var list<string> $params */
        $params = ['foo', 'bar', 'baz'];

        $_SERVER['REQUEST_URI'] = "https://example.org/{$params[0]}/{$params[1]}/{$params[2]}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The params are '{$params[1]}', '{$params[0]}' and '{$params[2]}'!");

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->get('{secondParam}/{firstParam}/{thirdParam}', FakeController::class, 'manyParamsAction');
        });
    }

    /**
     * @dataProvider stringProvider
     */
    public function test_it_should_pass_string_params_to_the_action(string $string): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/string/is/{$string}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The 'string' param is '{$string}'!");

        Routing::configure(static function (RoutingConfigurator $routes): void {
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
    public function test_it_should_pass_int_params_to_the_action(string $int): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/integer/is/{$int}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The 'int' param is '{$int}'!");

        Routing::configure(static function (RoutingConfigurator $routes): void {
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
    public function test_it_should_pass_float_params_to_the_action(string $float): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/float/is/{$float}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The 'float' param is '{$float}'!");

        Routing::configure(static function (RoutingConfigurator $routes): void {
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
    public function test_it_should_pass_bool_params_to_the_action(string $given, string $expected): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/bool/is/{$given}";
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString("The 'bool' param is '{$expected}'!");

        Routing::configure(static function (RoutingConfigurator $routes): void {
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

    public function test_it_should_respond_only_the_first_match(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('Expected!');

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
            $routes->get('expected/{param}', FakeController::class, 'stringParamAction');
        });
    }

    public function test_optional_argument(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('Expected!');

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->get('optional/{param?}', FakeController::class, 'basicAction');
        });
    }

    public function test_multiple_optional_argument(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('Expected!');

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->get('optional/{param1?}/{param2?}', FakeController::class, 'basicAction');
        });
    }

    public function test_it_should_thrown_exception_if_method_does_not_exist(): void
    {
        $this->expectException(UnsupportedHttpMethodException::class);

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->invalidName('', FakeController::class);
        });
    }

    /**
     * @dataProvider anyHttpMethodProvider
     */
    public function test_any_http_method(string $httpMethod): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = $httpMethod;

        $this->expectOutputString('Expected!');

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->any('expected/uri', FakeController::class, 'basicAction');
        });
    }

    public function anyHttpMethodProvider(): Generator
    {
        yield ['METHOD_GET' => Request::METHOD_GET];
        yield ['METHOD_CONNECT' => Request::METHOD_CONNECT];
        yield ['METHOD_DELETE' => Request::METHOD_DELETE];
        yield ['METHOD_HEAD' => Request::METHOD_HEAD];
        yield ['METHOD_OPTIONS' => Request::METHOD_OPTIONS];
        yield ['METHOD_PATCH' => Request::METHOD_PATCH];
        yield ['METHOD_POST' => Request::METHOD_POST];
        yield ['METHOD_PUT' => Request::METHOD_PUT];
        yield ['METHOD_TRACE' => Request::METHOD_TRACE];
    }

    public function test_inject_dependencies_in_controllers(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('default-Expected!');

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->get('expected/uri', FakeControllerWithDependencies::class);
            $routes->setMappingInterfaces([NameInterface::class => new Name('Expected!')]);
        });
    }

    public function test_redirect(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('Expected!');

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->redirect('optional/uri', 'expected/uri');

            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
    }
}
