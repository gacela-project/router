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
use Override;

final class MiddlewareTest extends HeaderTestCase
{
    public function test_middleware_can_modify_response(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->add(self::createAnonMiddleware('WRAPPED'));

            $routes->get('/', static fn () => 'CONTENT');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[WRAPPED]CONTENT[/WRAPPED]', $output);
    }

    public function test_multiple_middlewares_execute_in_onion_pattern(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->add(self::createAnonMiddleware('FIRST'));
            $middlewares->add(self::createAnonMiddleware('SECOND'));
            $middlewares->add(self::createAnonMiddleware('THIRD'));

            $routes->get('/', static fn () => 'CORE');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

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
                        // Short-circuit: don't call $next
                        return 'SHORT-CIRCUITED';
                    }
                },
            );

            $routes->get('/', static fn () => 'NEVER REACHED');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('SHORT-CIRCUITED', $output);
    }

    public function test_route_specific_middleware_only_applies_to_that_route(): void
    {
        $router = new Router(static function (Routes $routes): void {
            $routes
                ->get('protected', static fn () => 'PROTECTED')
                ->middleware(self::createAnonMiddleware('AUTH'));

            $routes->get('public', static fn () => 'PUBLIC');
        });

        // Test protected route
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/protected';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[AUTH]PROTECTED[/AUTH]', $output);

        // Test public route
        $_SERVER['REQUEST_URI'] = '/public';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('PUBLIC', $output);
    }

    public function test_route_middleware_combines_with_global_middleware(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->add(self::createAnonMiddleware('GLOBAL'));

            $routes
                ->get('/', static fn () => 'CONTENT')
                ->middleware(self::createAnonMiddleware('ROUTE'));
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

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

        ob_start();
        $router->run();
        $output = ob_get_clean();

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

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[TEST]CONTENT[/TEST]', $output);
    }

    public function test_route_can_have_multiple_middlewares(): void
    {
        $router = new Router(static function (Routes $routes): void {
            $routes
                ->get('/', static fn () => 'CONTENT')
                ->middleware(self::createAnonMiddleware('ONE'))
                ->middleware(self::createAnonMiddleware('TWO'));
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[ONE][TWO]CONTENT[/TWO][/ONE]', $output);
    }

    private static function createAnonMiddleware(string $tag): MiddlewareInterface
    {
        return new class($tag) implements MiddlewareInterface {
            public function __construct(
                private readonly string $tag,
            ) {
            }

            #[Override]
            public function handle(Request $request, Closure $next): string
            {
                return "[{$this->tag}]" . $next($request) . "[/{$this->tag}]";
            }
        };
    }
}
