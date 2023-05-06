<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Bindings;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use GacelaTest\Feature\Router\Fake\Name;
use GacelaTest\Feature\Router\Fake\NameInterface;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithDependencies;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithRequest;
use PHPUnit\Framework\TestCase;

final class RouterBindingTest extends TestCase
{
    public function test_inject_dependencies_in_controllers(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('default-Expected!');

        $router = new Router(static function (Bindings $binding, Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithDependencies::class);
            $binding->bind(NameInterface::class, new Name('Expected!'));
        });
        $router->run();
    }

    public function test_inject_controller_with_request_dependency(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $_GET['name'] = 'Katarn';

        $this->expectOutputString('Katarn');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected', FakeControllerWithRequest::class);
        });
        $router->run();
    }
}
