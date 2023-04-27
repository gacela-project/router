<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Entities\Request;
use Gacela\Router\Exceptions\UnsupportedHttpMethodException;
use Gacela\Router\Router;
use Gacela\Router\Routes;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use Generator;
use PHPUnit\Framework\TestCase;

final class RouterMatchTest extends TestCase
{
    public function test_respond_when_everything_matches(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();
    }

    public function test_respond_only_the_first_match(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
            $routes->get('expected/{param}', FakeController::class, 'stringParamAction');
        });
        $router->run();
    }

    public function test_optional_argument(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('optional/{param?}', FakeController::class, 'basicAction');
        });
        $router->run();
    }

    public function test_multiple_optional_argument(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('optional/{param1?}/{param2?}', FakeController::class, 'basicAction');
        });
        $router->run();
    }

    public function test_thrown_exception_when_method_does_not_exist(): void
    {
        $this->expectException(UnsupportedHttpMethodException::class);

        $router = new Router(static function (Routes $routes): void {
            $routes->invalidName('', FakeController::class);
        });
        $router->run();
    }

    /**
     * @dataProvider anyHttpMethodProvider
     */
    public function test_any_http_method(string $httpMethod): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = $httpMethod;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->any('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();
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

    /**
     * @dataProvider matchesMethodsProvider
     */
    public function test_match_matches_all_its_methods(string $testMethod, array $givenMethods): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = $testMethod;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes) use ($givenMethods): void {
            $routes->match($givenMethods, 'expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();
    }

    public function matchesMethodsProvider(): Generator
    {
        yield [Request::METHOD_GET, [Request::METHOD_GET, Request::METHOD_POST]];
        yield [Request::METHOD_POST, [Request::METHOD_GET, Request::METHOD_POST]];
        yield [Request::METHOD_PATCH, [Request::METHOD_PATCH, Request::METHOD_PUT]];
        yield [Request::METHOD_PUT, [Request::METHOD_PATCH, Request::METHOD_PUT]];
    }
}
