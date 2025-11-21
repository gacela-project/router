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

final class MiddlewareGroupTest extends HeaderTestCase
{
    public function test_can_define_middleware_group(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->group('test', [self::createAnonMiddleware('GROUP')]);

            $routes->get('/', static fn () => 'CONTENT')->middleware('test');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[GROUP]CONTENT[/GROUP]', $output);
    }

    public function test_middleware_group_with_multiple_middlewares(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->group('web', [
                self::createAnonMiddleware('FIRST'),
                self::createAnonMiddleware('SECOND'),
            ]);

            $routes->get('/', static fn () => 'CONTENT')->middleware('web');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[FIRST][SECOND]CONTENT[/SECOND][/FIRST]', $output);
    }

    public function test_multiple_groups_can_be_applied_to_route(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->group('group1', [
                self::createAnonMiddleware('G1'),
            ]);

            $middlewares->group('group2', [
                self::createAnonMiddleware('G2'),
            ]);

            $routes
                ->get('/', static fn () => 'CONTENT')
                ->middleware('group1')
                ->middleware('group2');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[G1][G2]CONTENT[/G2][/G1]', $output);
    }

    public function test_can_mix_groups_and_individual_middleware(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->group('auth', [
                self::createAnonMiddleware('AUTH'),
            ]);

            $routes
                ->get('/', static fn () => 'CONTENT')
                ->middleware('auth')
                ->middleware(self::createAnonMiddleware('CUSTOM'));
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[AUTH][CUSTOM]CONTENT[/CUSTOM][/AUTH]', $output);
    }

    public function test_global_middleware_groups(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->group('global', [self::createAnonMiddleware('GLOBAL')]);

            // Add group to global middleware
            $middlewares->add('global');

            $routes->get('/', static fn () => 'CONTENT');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[GLOBAL]CONTENT[/GLOBAL]', $output);
    }

    public function test_middleware_group_with_class_strings(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->group('test', [
                FakeMiddleware::class,
            ]);

            $routes->get('/', static fn () => 'CONTENT')->middleware('test');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[TEST]CONTENT[/TEST]', $output);
    }

    public function test_undefined_group_uses_name_as_class_string(): void
    {
        $router = new Router(static function (Routes $routes): void {
            // Use class name directly without defining as group
            $routes
                ->get('/', static fn () => 'CONTENT')
                ->middleware(FakeMiddleware::class);
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('[TEST]CONTENT[/TEST]', $output);
    }

    public function test_can_retrieve_defined_groups(): void
    {
        $middlewares = new Middlewares();

        $middleware1 = new class() implements MiddlewareInterface {
            #[Override]
            public function handle(Request $request, Closure $next): string
            {
                return $next($request);
            }
        };

        $middlewares->group('test', [$middleware1]);

        $groups = $middlewares->getGroups();

        self::assertArrayHasKey('test', $groups);
        self::assertSame([$middleware1], $groups['test']);
    }

    public function test_empty_group_does_nothing(): void
    {
        $router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
            $middlewares->group('empty', []);
            $routes->get('/', static fn () => 'CONTENT')->middleware('empty');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        ob_start();
        $router->run();
        $output = ob_get_clean();

        self::assertSame('CONTENT', $output);
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
