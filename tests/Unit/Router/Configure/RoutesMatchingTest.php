<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router\Configure;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('optionalBoundaryProvider')]
    public function test_a_trailing_optional_only_matches_at_a_segment_boundary(
        string $path,
        string $uri,
        bool $matches,
    ): void {
        $routes = new Routes();
        $routes->get($path, FakeController::class, 'basicAction');

        $route = $routes->findMatching(self::request(Request::METHOD_GET, $uri));

        self::assertSame($matches, $route !== null);
    }

    public static function optionalBoundaryProvider(): Generator
    {
        yield 'bare path' => ['custom/{id?}', '/custom', true];
        yield 'with the optional' => ['custom/{id?}', '/custom/5', true];
        yield 'prefix of a longer word' => ['custom/{id?}', '/customers', false];
        yield 'digit glued to the prefix' => ['custom/{id?}', '/custom5', false];
        yield 'too many segments' => ['custom/{id?}', '/custom/5/6', false];
        yield 'two optionals, none' => ['a/{one?}/{two?}', '/a', true];
        yield 'two optionals, one' => ['a/{one?}/{two?}', '/a/1', true];
        yield 'two optionals, both' => ['a/{one?}/{two?}', '/a/1/2', true];
        yield 'two optionals, glued' => ['a/{one?}/{two?}', '/ab', false];
        yield 'only optionals, root' => ['{a?}/{b?}', '/', true];
        yield 'only optionals, one' => ['{a?}/{b?}', '/x', true];
        yield 'only optionals, both' => ['{a?}/{b?}', '/x/y', true];
        yield 'only optionals, too many' => ['{a?}/{b?}', '/x/y/z', false];
    }

    public function test_a_longer_word_than_an_optional_route_is_not_a_known_path(): void
    {
        $routes = new Routes();
        $routes->get('custom/{id?}', FakeController::class, 'basicAction');

        self::assertSame([], $routes->allowedMethodsFor(self::request(Request::METHOD_DELETE, '/customers')));
        self::assertSame(
            [Request::METHOD_GET, Request::METHOD_HEAD],
            $routes->allowedMethodsFor(self::request(Request::METHOD_DELETE, '/custom')),
        );
    }

    #[DataProvider('literalStaticSegmentProvider')]
    public function test_a_static_segment_of_a_dynamic_route_matches_literally(
        string $path,
        string $uri,
        bool $matches,
    ): void {
        $routes = new Routes();
        $routes->get($path, FakeController::class, 'basicAction');

        $route = $routes->findMatching(self::request(Request::METHOD_GET, $uri));

        self::assertSame($matches, $route !== null);
    }

    public static function literalStaticSegmentProvider(): Generator
    {
        yield 'dot, itself' => ['v1.0/{id}', '/v1.0/5', true];
        yield 'dot, another character' => ['v1.0/{id}', '/v1X0/5', false];
        yield 'plus, itself' => ['a+b/{id}', '/a+b/5', true];
        yield 'plus, repeated first letter' => ['a+b/{id}', '/aab/5', false];
        yield 'parens, itself' => ['api(v1)/{id}', '/api(v1)/5', true];
        yield 'parens, as a group' => ['api(v1)/{id}', '/apiv1/5', false];
        yield 'star, itself' => ['x*/{id}', '/x*/5', true];
        yield 'star, as a quantifier' => ['x*/{id}', '/xxx/5', false];
        yield 'question mark, not optional' => ['wha?/{id}', '/wh/5', false];
        yield 'question mark, never in a request path' => ['wha?/{id}', '/wha/5', false];
        yield 'pipe, itself' => ['a|b/{id}', '/a|b/5', true];
        yield 'pipe, as an alternation' => ['a|b/{id}', '/a/5', false];
        yield 'hash, never in a request path' => ['a#b/{id}', '/a#b/5', false];
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
