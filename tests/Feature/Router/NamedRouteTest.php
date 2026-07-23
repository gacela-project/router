<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Exceptions\UrlGenerationException;
use Gacela\Router\Router;
use Gacela\Router\UrlGenerator;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use GacelaTest\Feature\Router\Fixtures\FakeIntEnum;
use GacelaTest\Feature\Router\Fixtures\FakeStringEnum;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class NamedRouteTest extends TestCase
{
    /**
     * @param array<string, mixed> $params
     */
    #[DataProvider('generationProvider')]
    public function test_it_generates_the_url_of_a_named_route(
        string $path,
        array $params,
        string $expected,
    ): void {
        $routes = new Routes();
        $routes->get($path, FakeController::class, 'basicAction')->name('target');

        self::assertSame($expected, (new UrlGenerator($routes))->generate('target', $params));
    }

    public static function generationProvider(): Generator
    {
        yield 'static' => ['users', [], '/users'];
        yield 'root' => ['/', [], '/'];
        yield 'one mandatory' => ['users/{id}', ['id' => 7], '/users/7'];
        yield 'string param' => ['users/{id}', ['id' => 'me'], '/users/me'];
        yield 'float param' => ['n/{v}', ['v' => 1.5], '/n/1.5'];
        yield 'several mandatory' => [
            'posts/{postId}/comments/{commentId}',
            ['postId' => 1, 'commentId' => 2],
            '/posts/1/comments/2',
        ];
        yield 'optional provided' => ['archive/{year?}', ['year' => 2026], '/archive/2026'];
        yield 'optional omitted' => ['archive/{year?}', [], '/archive'];
        yield 'mandatory kept, optional omitted' => ['a/{one}/{two?}', ['one' => 'x'], '/a/x'];
        yield 'extra params ignored' => ['users/{id}', ['id' => 1, 'unused' => 'x'], '/users/1'];
    }

    public function test_generation_stops_at_the_first_omitted_optional(): void
    {
        $routes = new Routes();
        $routes->get('a/{one?}/{two?}', FakeController::class, 'basicAction')->name('optionals');

        // 'two' cannot be placed without 'one', so the url stops rather than
        // producing '/a/2' where 2 would be read back as 'one'.
        self::assertSame('/a', (new UrlGenerator($routes))->generate('optionals', ['two' => 2]));
    }

    public function test_a_segment_with_unbalanced_braces_stays_literal(): void
    {
        $routes = new Routes();
        $routes->get('a/{b', FakeController::class, 'basicAction')->name('literal');

        self::assertSame('/a/{b', (new UrlGenerator($routes))->generate('literal'));
    }

    public function test_unnamed_routes_registered_first_do_not_stop_the_lookup(): void
    {
        $routes = new Routes();
        $routes->get('first', FakeController::class, 'basicAction');
        $routes->get('second', FakeController::class, 'basicAction');
        $routes->get('third', FakeController::class, 'basicAction')->name('third');

        self::assertSame('/third', (new UrlGenerator($routes))->generate('third'));
    }

    public function test_an_int_backed_enum_param_is_generated_from_its_value(): void
    {
        $routes = new Routes();
        $routes->get('level/{level}', FakeController::class, 'basicAction')->name('level.show');

        self::assertSame(
            '/level/1',
            (new UrlGenerator($routes))->generate('level.show', ['level' => FakeIntEnum::One]),
        );
    }

    public function test_a_backed_enum_param_is_generated_from_its_value(): void
    {
        $routes = new Routes();
        $routes->get('status/{status}', FakeController::class, 'basicAction')->name('status.show');

        self::assertSame(
            '/status/active',
            (new UrlGenerator($routes))->generate('status.show', ['status' => FakeStringEnum::Active]),
        );
    }

    public function test_an_unknown_name_throws(): void
    {
        $routes = new Routes();
        $routes->get('users', FakeController::class, 'basicAction')->name('users.index');

        $this->expectException(UrlGenerationException::class);
        $this->expectExceptionMessage("No route is named 'nope'.");

        (new UrlGenerator($routes))->generate('nope');
    }

    public function test_a_duplicate_name_throws(): void
    {
        $routes = new Routes();
        $routes->get('users', FakeController::class, 'basicAction')->name('dup');
        $routes->get('other', FakeController::class, 'basicAction')->name('dup');

        $this->expectException(UrlGenerationException::class);
        $this->expectExceptionMessage("Route name 'dup' is already taken.");

        (new UrlGenerator($routes))->generate('dup');
    }

    public function test_a_missing_mandatory_param_throws(): void
    {
        $routes = new Routes();
        $routes->get('users/{id}', FakeController::class, 'basicAction')->name('users.show');

        $this->expectException(UrlGenerationException::class);
        $this->expectExceptionMessage("Missing param 'id' to generate the url for route 'users.show'.");

        (new UrlGenerator($routes))->generate('users.show');
    }

    /**
     * @param mixed $value
     */
    #[DataProvider('unsupportedParamProvider')]
    public function test_an_unsupported_param_type_throws($value, string $type): void
    {
        $routes = new Routes();
        $routes->get('users/{id}', FakeController::class, 'basicAction')->name('users.show');

        $this->expectException(UrlGenerationException::class);
        $this->expectExceptionMessage(
            "Unsupported type '{$type}' for param 'id' generating the url for route 'users.show'."
            . ' Must be a scalar or a backed enum.',
        );

        (new UrlGenerator($routes))->generate('users.show', ['id' => $value]);
    }

    public static function unsupportedParamProvider(): Generator
    {
        yield 'array' => [[1], 'array'];
        yield 'object' => [new stdClass(), 'stdClass'];
        yield 'bool' => [true, 'bool'];
    }

    public function test_name_is_chainable_with_middleware(): void
    {
        $routes = new Routes();
        $route = $routes->get('users', FakeController::class, 'basicAction')
            ->middleware('web')
            ->name('users.index');

        self::assertSame('users.index', $route->getName());
        self::assertSame(['web'], $route->getMiddlewares());
    }

    public function test_an_unnamed_route_is_not_reachable_by_name(): void
    {
        $routes = new Routes();
        $routes->get('users', FakeController::class, 'basicAction');

        $this->expectException(UrlGenerationException::class);

        (new UrlGenerator($routes))->generate('users');
    }

    public function test_a_named_route_still_matches_normally(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeController::class, 'basicAction')->name('expected');
        });
        $router->run();
    }

    public function test_the_generator_is_injectable_into_a_controller(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/here';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('/users/7');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('users/{id}', FakeController::class, 'basicAction')->name('users.show');
            $routes->get('here', UrlGeneratingController::class);
        });
        $router->run();
    }
}

final class UrlGeneratingController
{
    public function __construct(
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(): string
    {
        return $this->urlGenerator->generate('users.show', ['id' => 7]);
    }
}
