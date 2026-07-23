<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Exceptions\MalformedPathException;
use Gacela\Router\Router;
use Gacela\Router\UrlGenerator;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class RouteGroupTest extends HeaderTestCase
{
    public function test_a_route_inside_a_group_gets_the_prefix(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/admin/users';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->group('admin', static function (Routes $routes): void {
                $routes->get('users', FakeController::class, 'basicAction');
            });
        });
        $router->run();
    }

    public function test_the_unprefixed_path_no_longer_matches(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/users';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->group('admin', static function (Routes $routes): void {
                $routes->get('users', FakeController::class, 'basicAction');
            });
        });
        $router->run();

        self::assertSame([
            ['header' => 'HTTP/1.0 404 Not Found', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_the_group_root_is_the_prefix_itself(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/admin';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->group('admin', static function (Routes $routes): void {
                $routes->get('/', FakeController::class, 'basicAction');
            });
        });
        $router->run();
    }

    public function test_nested_groups_compose_their_prefixes(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/admin/v1/users';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->group('admin', static function (Routes $routes): void {
                $routes->group('v1', static function (Routes $routes): void {
                    $routes->get('users', FakeController::class, 'basicAction');
                });
            });
        });
        $router->run();
    }

    public function test_registrations_after_a_group_are_not_prefixed(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/public';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->group('admin', static function (Routes $routes): void {
                $routes->get('users', FakeController::class, 'basicAction');
            });

            $routes->get('public', FakeController::class, 'basicAction');
        });
        $router->run();
    }

    public function test_a_sibling_group_does_not_inherit_the_previous_prefix(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/api/users';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->group('admin', static function (Routes $routes): void {
                $routes->get('users', FakeController::class, 'basicAction');
            });
            $routes->group('api', static function (Routes $routes): void {
                $routes->get('users', FakeController::class, 'basicAction');
            });
        });
        $router->run();
    }

    public function test_params_inside_a_group_still_resolve(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/admin/users/alice';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'string' param is 'alice'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->group('admin', static function (Routes $routes): void {
                $routes->get('users/{param}', FakeController::class, 'stringParamAction');
            });
        });
        $router->run();
    }

    public function test_a_param_in_the_prefix_is_passed_to_the_action(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/tenants/acme/users';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'string' param is 'acme'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->group('tenants/{param}', static function (Routes $routes): void {
                $routes->get('users', FakeController::class, 'stringParamAction');
            });
        });
        $router->run();
    }

    public function test_a_redirect_inside_a_group_is_prefixed(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/admin/docs';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->group('admin', static function (Routes $routes): void {
                $routes->redirect('docs', 'https://gacela-project.com/');
            });
        });
        $router->run();

        self::assertSame([
            [
                'header' => 'Location: https://gacela-project.com/',
                'replace' => true,
                'response_code' => 302,
            ],
        ], $this->headers());
    }

    public function test_a_named_route_inside_a_group_generates_the_prefixed_url(): void
    {
        $routes = new Routes();
        $routes->group('admin', static function (Routes $routes): void {
            $routes->group('v1', static function (Routes $routes): void {
                $routes->get('users/{id}', FakeController::class, 'basicAction')->name('admin.users.show');
            });
        });

        self::assertSame(
            '/admin/v1/users/7',
            (new UrlGenerator($routes))->generate('admin.users.show', ['id' => 7]),
        );
    }

    #[DataProvider('malformedPrefixProvider')]
    public function test_the_composed_path_is_validated(string $prefix, string $path): void
    {
        $this->expectException(MalformedPathException::class);

        $routes = new Routes();
        $routes->group($prefix, static function (Routes $routes) use ($path): void {
            $routes->get($path, FakeController::class, 'basicAction');
        });
    }

    public static function malformedPrefixProvider(): Generator
    {
        yield 'leading slash in prefix' => ['/admin', 'users'];
        yield 'trailing slash in prefix' => ['admin/', 'users'];
        yield 'empty prefix' => ['', 'users'];
        yield 'empty segment in prefix' => ['admin//v1', 'users'];
    }

    public function test_a_failed_group_does_not_leak_its_prefix(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/public';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            try {
                $routes->group('admin', static function (Routes $routes): void {
                    $routes->get('bad/', FakeController::class, 'basicAction');
                });
            } catch (MalformedPathException) {
                // Swallowed on purpose: the prefix must not survive the failure.
            }

            $routes->get('public', FakeController::class, 'basicAction');
        });
        $router->run();
    }

    public function test_group_is_chainable(): void
    {
        $routes = new Routes();
        $result = $routes->group('admin', static function (Routes $routes): void {
            $routes->get('users', FakeController::class, 'basicAction');
        });

        self::assertSame($routes, $result);
        self::assertCount(1, $routes->getAllRoutes());
        self::assertSame('admin/users', $routes->getAllRoutes()[0]->path());
    }
}
