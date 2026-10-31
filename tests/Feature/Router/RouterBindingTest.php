<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Closure;
use Gacela\Router\Configure\Bindings;
use Gacela\Router\Configure\Middlewares;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Middleware\MiddlewareInterface;
use Gacela\Router\Router;
use GacelaTest\Feature\Router\Fake\Name;
use GacelaTest\Feature\Router\Fake\NameInterface;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithDependencies;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithRequest;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithRequestDetails;
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

    public function test_the_controller_gets_the_request_of_the_run_not_of_the_construction(): void
    {
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $_SERVER['REQUEST_URI'] = '/first';
        $_GET = [];

        $router = new Router(static function (Routes $routes): void {
            $routes->get('second', FakeControllerWithRequestDetails::class);
        });

        $_SERVER['REQUEST_URI'] = '/second?q=live';
        $_GET = ['q' => 'live'];

        ob_start();
        $router->run();
        $output = (string)ob_get_clean();

        self::assertStringStartsWith('/second|live|', $output);
    }

    public function test_two_runs_of_one_router_see_two_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('page', FakeControllerWithRequestDetails::class);
        });

        $outputs = [];
        foreach (['one', 'two'] as $query) {
            $_SERVER['REQUEST_URI'] = '/page?q=' . $query;
            $_GET = ['q' => $query];

            ob_start();
            $router->run();
            $outputs[] = (string)ob_get_clean();
        }

        self::assertStringStartsWith('/page|one|', $outputs[0]);
        self::assertStringStartsWith('/page|two|', $outputs[1]);
    }

    public function test_the_middleware_and_the_controller_share_one_request(): void
    {
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $_SERVER['REQUEST_URI'] = '/page';
        $_GET = [];

        $middleware = new class() implements MiddlewareInterface {
            public int $seenRequestId = 0;

            public function handle(Request $request, Closure $next): string
            {
                $this->seenRequestId = spl_object_id($request);

                return $next($request);
            }
        };

        $router = new Router(static function (Routes $routes, Middlewares $middlewares) use ($middleware): void {
            $middlewares->add($middleware);
            $routes->get('page', FakeControllerWithRequestDetails::class);
        });

        ob_start();
        $router->run();
        $output = (string)ob_get_clean();

        self::assertStringEndsWith('|' . $middleware->seenRequestId, $output);
    }

    public function test_a_request_bound_by_the_user_wins_over_the_live_one(): void
    {
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $_SERVER['REQUEST_URI'] = '/page';
        $_GET = ['name' => 'bound'];
        $bound = Request::fromGlobals();

        $_GET = ['name' => 'live'];

        $this->expectOutputString('bound');

        $router = new Router(static function (Routes $routes, Bindings $bindings) use ($bound): void {
            $routes->get('page', FakeControllerWithRequest::class);
            $bindings->bind(Request::class, $bound);
        });
        $router->run();
    }
}
