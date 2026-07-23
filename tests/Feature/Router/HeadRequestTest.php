<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Handlers;
use Gacela\Router\Configure\Middlewares;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Response;
use Gacela\Router\Exceptions\NotFound404Exception;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use GacelaTest\Feature\Router\Fixtures\FakeMiddleware;

final class HeadRequestTest extends HeaderTestCase
{
    public function test_head_is_served_by_the_matching_get_route_without_a_body(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_HEAD;

        $this->expectOutputString('');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();
    }

    public function test_head_runs_the_get_route_so_its_headers_are_still_sent(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_HEAD;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', static fn (): Response => new Response('body', [
                'Content-Type: application/json',
            ]));
        });
        $router->run();

        // The route ran and produced its header; only the body is withheld.
        self::assertSame([
            ['header' => 'Content-Type: application/json', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_head_no_longer_reports_405_on_a_get_only_path(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_HEAD;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();

        self::assertSame([], $this->headers());
    }

    public function test_an_explicit_head_route_takes_precedence_over_get(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_HEAD;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', static fn (): Response => new Response('', ['X-Served-By: GET']));
            $routes->head('expected/uri', static fn (): Response => new Response('', ['X-Served-By: HEAD']));
        });
        $router->run();

        self::assertSame([
            ['header' => 'X-Served-By: HEAD', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_an_any_route_serves_head_without_a_body(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_HEAD;

        $this->expectOutputString('');

        $router = new Router(static function (Routes $routes): void {
            $routes->any('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();
    }

    public function test_head_on_a_dynamic_get_route_is_served(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/value';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_HEAD;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/{param}', static fn (): Response => new Response('body', ['X-Dynamic: yes']));
        });
        $router->run();

        self::assertSame([
            ['header' => 'X-Dynamic: yes', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_head_middlewares_still_run(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_HEAD;

        $this->expectOutputString('');

        $ran = new class() {
            public bool $value = false;
        };

        $router = new Router(static function (Routes $routes, Middlewares $middlewares) use ($ran): void {
            $middlewares->add(new FakeMiddleware());
            $routes->get('expected/uri', static function () use ($ran): string {
                $ran->value = true;
                return 'CONTENT';
            });
        });
        $router->run();

        self::assertTrue($ran->value);
    }

    public function test_head_on_an_unknown_path_is_still_404(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/nothing/here';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_HEAD;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();

        self::assertSame([
            ['header' => 'HTTP/1.0 404 Not Found', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_a_head_error_response_carries_no_body_either(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/nothing/here';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_HEAD;

        // A handler that would render content still must not produce a body.
        $this->expectOutputString('');

        $router = new Router(static function (Handlers $handlers): void {
            $handlers->handle(NotFound404Exception::class, static fn (): string => 'NOT FOUND PAGE');
        });
        $router->run();
    }

    public function test_get_still_returns_its_body(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();
    }

    public function test_allow_advertises_head_wherever_get_is_supported(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_DELETE;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction');
        });
        $router->run();

        self::assertSame([
            ['header' => 'HTTP/1.1 405 Method Not Allowed', 'replace' => true, 'response_code' => 0],
            ['header' => 'Allow: GET, HEAD', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }
}
