<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Response;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;

final class RouterRunTest extends HeaderTestCase
{
    public function test_run_multiple_route_collections(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri-2';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router();

        $router->configure(static function (Routes $routes): void {
            $routes->get('uri-1', static fn () => new Response('first body'));
        });

        $router->configure(static function (Routes $routes): void {
            $routes->get('uri-2', static fn () => new Response('second body'));
        });

        $router->run();

        $this->expectOutputString('second body');

        self::assertSame([], $this->headers());
    }

    public function test_run_same_multiple_route_collections_then_first_with_priority(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router();

        $router->configure(static function (Routes $routes): void {
            $routes->get('uri', static fn () => new Response('first body'));
        });

        $router->configure(static function (Routes $routes): void {
            $routes->get('uri', static fn () => new Response('second body'));
        });

        $router->run();

        $this->expectOutputString('first body');

        self::assertSame([], $this->headers());
    }
}
