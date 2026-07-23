<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router\Configure;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use PHPUnit\Framework\TestCase;

use function GacelaTest\countedPregMatchCalls;
use function GacelaTest\resetPregMatchCalls;

include_once __DIR__ . '/../../../preg_match.php';

final class RoutesMatchingTest extends TestCase
{
    protected function setUp(): void
    {
        resetPregMatchCalls();
    }

    public function test_static_route_matches_without_any_regex(): void
    {
        $routes = new Routes();
        foreach (range(1, 20) as $i) {
            $routes->get("static/path/{$i}", FakeController::class, 'basicAction');
        }
        $routes->get('expected/uri', FakeController::class, 'basicAction');

        $route = $routes->findMatching(self::request(Request::METHOD_GET, '/expected/uri'));

        self::assertNotNull($route);
        self::assertSame('expected/uri', $route->path());
        self::assertSame(0, countedPregMatchCalls());
    }

    public function test_root_route_matches_without_any_regex(): void
    {
        $routes = new Routes();
        $routes->get('/', FakeController::class, 'basicAction');

        $route = $routes->findMatching(self::request(Request::METHOD_GET, '/'));

        self::assertNotNull($route);
        self::assertSame('', $route->path());
        self::assertSame(0, countedPregMatchCalls());
    }

    public function test_dynamic_route_still_matches_by_regex(): void
    {
        $routes = new Routes();
        $routes->get('expected/{param}', FakeController::class, 'stringParamAction');

        $route = $routes->findMatching(self::request(Request::METHOD_GET, '/expected/value'));

        self::assertNotNull($route);
        self::assertSame('expected/{param}', $route->path());
        self::assertGreaterThan(0, countedPregMatchCalls());
    }

    public function test_only_the_requested_method_is_scanned(): void
    {
        $routes = new Routes();
        foreach (range(1, 10) as $i) {
            $routes->post("other/{$i}/{param}", FakeController::class, 'stringParamAction');
        }
        $routes->get('expected/{param}', FakeController::class, 'stringParamAction');

        $route = $routes->findMatching(self::request(Request::METHOD_GET, '/expected/value'));

        self::assertNotNull($route);
        // Only the single GET route is a candidate; the ten POST ones are never tried.
        self::assertSame(1, countedPregMatchCalls());
    }

    public function test_static_route_wins_over_a_dynamic_one(): void
    {
        $routes = new Routes();
        $routes->get('expected/{param}', FakeController::class, 'stringParamAction');
        $routes->get('expected/uri', FakeController::class, 'basicAction');

        $route = $routes->findMatching(self::request(Request::METHOD_GET, '/expected/uri'));

        self::assertNotNull($route);
        self::assertSame('expected/uri', $route->path());
    }

    public function test_first_registered_route_wins_for_a_duplicated_static_path(): void
    {
        $routes = new Routes();
        $routes->get('expected/uri', FakeController::class, 'basicAction');
        $routes->get('expected/uri', FakeController::class, 'stringParamAction');

        $route = $routes->findMatching(self::request(Request::METHOD_GET, '/expected/uri'));

        self::assertNotNull($route);
        self::assertSame('basicAction', $route->action());
    }

    public function test_first_registered_route_wins_for_a_duplicated_dynamic_path(): void
    {
        $routes = new Routes();
        $routes->get('expected/{param}', FakeController::class, 'basicAction');
        $routes->get('expected/{other}', FakeController::class, 'stringParamAction');

        $route = $routes->findMatching(self::request(Request::METHOD_GET, '/expected/value'));

        self::assertNotNull($route);
        self::assertSame('basicAction', $route->action());
    }

    public function test_regex_metacharacters_in_a_static_path_are_matched_literally(): void
    {
        $routes = new Routes();
        $routes->get('a.c', FakeController::class, 'basicAction');

        // The map compares strings, so '.' is a dot. The previous regex scan
        // built '#^/a.c$#', where it was a wildcard and matched '/abc' too.
        self::assertNotNull($routes->findMatching(self::request(Request::METHOD_GET, '/a.c')));
        self::assertNull($routes->findMatching(self::request(Request::METHOD_GET, '/abc')));
    }

    public function test_no_match_returns_null(): void
    {
        $routes = new Routes();
        $routes->get('expected/uri', FakeController::class, 'basicAction');

        self::assertNull($routes->findMatching(self::request(Request::METHOD_GET, '/nope')));
    }

    public function test_no_match_for_an_unregistered_method_returns_null(): void
    {
        $routes = new Routes();
        $routes->get('expected/uri', FakeController::class, 'basicAction');

        self::assertNull($routes->findMatching(self::request(Request::METHOD_POST, '/expected/uri')));
        self::assertSame(0, countedPregMatchCalls());
    }

    public function test_a_multi_method_static_route_is_reachable_from_every_method(): void
    {
        $routes = new Routes();
        $routes->match(
            [Request::METHOD_GET, Request::METHOD_POST],
            'expected/uri',
            FakeController::class,
            'basicAction',
        );

        self::assertNotNull($routes->findMatching(self::request(Request::METHOD_GET, '/expected/uri')));
        self::assertNotNull($routes->findMatching(self::request(Request::METHOD_POST, '/expected/uri')));
    }

    public function test_a_multi_method_dynamic_route_is_reachable_from_every_method(): void
    {
        $routes = new Routes();
        $routes->match(
            [Request::METHOD_GET, Request::METHOD_POST],
            'expected/{param}',
            FakeController::class,
            'stringParamAction',
        );

        self::assertNotNull($routes->findMatching(self::request(Request::METHOD_GET, '/expected/value')));
        self::assertNotNull($routes->findMatching(self::request(Request::METHOD_POST, '/expected/value')));
    }

    public function test_an_empty_request_path_resolves_the_root_route(): void
    {
        $routes = new Routes();
        $routes->get('/', FakeController::class, 'basicAction');

        // Request::path() normalises an empty REQUEST_URI to '/', so the root
        // route is what an empty request path resolves to.
        $route = $routes->findMatching(self::request(Request::METHOD_GET, ''));

        self::assertNotNull($route);
        self::assertSame('', $route->path());
    }

    public function test_get_all_routes_still_lists_every_registration_in_order(): void
    {
        $routes = new Routes();
        $routes->get('first', FakeController::class, 'basicAction');
        $routes->match(
            [Request::METHOD_GET, Request::METHOD_POST],
            'second/{param}',
            FakeController::class,
            'stringParamAction',
        );
        $routes->post('third', FakeController::class, 'basicAction');

        $all = $routes->getAllRoutes();

        // One Route per registration, whatever the method count, in order.
        self::assertCount(3, $all);
        self::assertSame(['first', 'second/{param}', 'third'], array_map(
            static fn ($route): string => $route->path(),
            $all,
        ));
    }

    private static function request(string $method, string $uri): Request
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return Request::fromGlobals();
    }
}
