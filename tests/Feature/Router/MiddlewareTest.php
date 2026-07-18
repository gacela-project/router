<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Closure;
use Gacela\Router\Configure\Middlewares;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Middleware\MiddlewareInterface;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeMiddleware;
use GacelaTest\Feature\Router\Fixtures\TagMiddleware;
use Override;

final class MiddlewareTest extends HeaderTestCase
{
    public function test_middleware_can_modify_response(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->add(new TagMiddleware('WRAPPED'));

            $routes->get('/', static fn () => 'CONTENT');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $output = $this->runRouter($router);

        self::assertSame('[WRAPPED]CONTENT[/WRAPPED]', $output);
    }

    public function test_multiple_middlewares_execute_in_onion_pattern(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->add(new TagMiddleware('FIRST'));
            $middlewares->add(new TagMiddleware('SECOND'));
            $middlewares->add(new TagMiddleware('THIRD'));

            $routes->get('/', static fn () => 'CORE');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $output = $this->runRouter($router);

        // Onion pattern: first middleware added wraps outermost
        self::assertSame('[FIRST][SECOND][THIRD]CORE[/THIRD][/SECOND][/FIRST]', $output);
    }

    public function test_middleware_can_short_circuit_request(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->add(
                new class() implements MiddlewareInterface {
                    #[Override]
                    public function handle(Request $request, Closure $next): string
                    {
                        return 'SHORT-CIRCUITED';
                    }
                },
            );

            $routes->get('/', static fn () => 'NEVER REACHED');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $output = $this->runRouter($router);

        self::assertSame('SHORT-CIRCUITED', $output);
    }

    public function test_route_specific_middleware_only_applies_to_that_route(): void
    {
        $router = new Router(static function (Routes $routes): void {
            $routes
                ->get('protected', static fn () => 'PROTECTED')
                ->middleware(new TagMiddleware('AUTH'));

            $routes->get('public', static fn () => 'PUBLIC');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/protected';

        $output = $this->runRouter($router);

        self::assertSame('[AUTH]PROTECTED[/AUTH]', $output);

        $_SERVER['REQUEST_URI'] = '/public';

        $output = $this->runRouter($router);

        self::assertSame('PUBLIC', $output);
    }

    public function test_route_middleware_combines_with_global_middleware(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->add(new TagMiddleware('GLOBAL'));

            $routes
                ->get('/', static fn () => 'CONTENT')
                ->middleware(new TagMiddleware('ROUTE'));
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $output = $this->runRouter($router);

        // Global middleware wraps outside route middleware
        self::assertSame('[GLOBAL][ROUTE]CONTENT[/ROUTE][/GLOBAL]', $output);
    }

    public function test_middleware_can_access_request(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->add(
                new class() implements MiddlewareInterface {
                    #[Override]
                    public function handle(Request $request, Closure $next): string
                    {
                        $path = $request->path();
                        return "[PATH:{$path}]" . $next($request);
                    }
                },
            );

            $routes->get('test-path', static fn () => 'CONTENT');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test-path';

        $output = $this->runRouter($router);

        self::assertSame('[PATH:/test-path]CONTENT', $output);
    }

    public function test_middleware_can_be_class_string(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->add(FakeMiddleware::class);
            $routes->get('/', static fn () => 'CONTENT');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $output = $this->runRouter($router);

        self::assertSame('[TEST]CONTENT[/TEST]', $output);
    }

    public function test_route_can_have_multiple_middlewares(): void
    {
        $router = new Router(static function (Routes $routes): void {
            $routes
                ->get('/', static fn () => 'CONTENT')
                ->middleware(new TagMiddleware('ONE'))
                ->middleware(new TagMiddleware('TWO'));
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $output = $this->runRouter($router);

        self::assertSame('[ONE][TWO]CONTENT[/TWO][/ONE]', $output);
    }
}
