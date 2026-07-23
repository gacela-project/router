<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router\Entities;

use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Route;
use Gacela\Router\Entities\RouteParams;
use Gacela\Router\Exceptions\UnsupportedParamTypeException;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class RouteParamsTest extends TestCase
{
    protected function setUp(): void
    {
        self::clearActionParamsCache();
    }

    /**
     * The cache is process-global, and one test below seeds it with a type the
     * real signature does not declare. Without this, that entry leaks into any
     * later test resolving the same action, which under `executionOrder=random`
     * fails somewhere else entirely.
     */
    protected function tearDown(): void
    {
        self::clearActionParamsCache();
    }

    public function test_reflects_a_controller_action_only_once(): void
    {
        $route = new Route(Request::METHOD_GET, 'expected/{param}', FakeController::class, 'stringParamAction');

        $first = self::resolve($route, '/expected/one');
        $second = self::resolve($route, '/expected/two');

        self::assertSame(['param' => 'one'], $first);
        self::assertSame(['param' => 'two'], $second);
        self::assertSame(
            [FakeController::class . '::stringParamAction'],
            array_keys(self::actionParamsCache()),
        );
    }

    public function test_a_cached_signature_is_used_instead_of_reflecting_again(): void
    {
        $route = new Route(Request::METHOD_GET, 'expected/{param}', FakeController::class, 'stringParamAction');

        // Seeded with a type the real signature does not declare: a cache hit
        // casts to int, whereas reflecting again would yield the string the
        // action actually declares.
        self::seedActionParamsCache(
            FakeController::class . '::stringParamAction',
            [['name' => 'param', 'type' => 'int', 'enumBacking' => null]],
        );

        self::assertSame(['param' => 42], self::resolve($route, '/expected/42'));
    }

    public function test_an_object_and_a_class_string_controller_share_one_cache_entry(): void
    {
        $fromClassString = new Route(
            Request::METHOD_GET,
            'expected/{param}',
            FakeController::class,
            'stringParamAction',
        );
        $fromObject = new Route(
            Request::METHOD_GET,
            'other/{param}',
            new FakeController(),
            'stringParamAction',
        );

        self::resolve($fromClassString, '/expected/one');
        self::resolve($fromObject, '/other/two');

        self::assertSame(
            [FakeController::class . '::stringParamAction'],
            array_keys(self::actionParamsCache()),
        );
    }

    public function test_each_action_of_the_same_controller_is_cached_separately(): void
    {
        self::resolve(
            new Route(Request::METHOD_GET, 'str/{param}', FakeController::class, 'stringParamAction'),
            '/str/one',
        );
        self::resolve(
            new Route(Request::METHOD_GET, 'int/{param}', FakeController::class, 'intParamAction'),
            '/int/42',
        );

        self::assertSame([
            FakeController::class . '::stringParamAction',
            FakeController::class . '::intParamAction',
        ], array_keys(self::actionParamsCache()));
    }

    public function test_cached_metadata_still_casts_every_supported_type(): void
    {
        $cases = [
            ['intParamAction', 'int/{param}', '/int/42', ['param' => 42]],
            ['floatParamAction', 'float/{param}', '/float/4.5', ['param' => 4.5]],
            ['boolParamAction', 'bool/{param}', '/bool/true', ['param' => true]],
            ['stringParamAction', 'str/{param}', '/str/abc', ['param' => 'abc']],
        ];

        foreach ($cases as [$action, $path, $uri, $expected]) {
            $route = new Route(Request::METHOD_GET, $path, FakeController::class, $action);

            // Twice each, so the second pass runs off the cached metadata.
            self::assertSame($expected, self::resolve($route, $uri));
            self::assertSame($expected, self::resolve($route, $uri));
        }
    }

    public function test_a_non_typed_param_still_throws_on_every_attempt(): void
    {
        $route = new Route(Request::METHOD_GET, 'expected/{param}', FakeController::class, 'nonTypedParam');

        foreach (range(1, 2) as $ignored) {
            try {
                self::resolve($route, '/expected/value');
                self::fail('Expected UnsupportedParamTypeException');
            } catch (UnsupportedParamTypeException $exception) {
                self::assertSame('Unsupported non-typed param. Must be a scalar or a backed enum.', $exception->getMessage());
            }
        }

        // A signature that cannot be resolved must not be cached.
        self::assertSame([], self::actionParamsCache());
    }

    public function test_a_non_scalar_param_still_throws_on_every_attempt(): void
    {
        $route = new Route(Request::METHOD_GET, 'expected/{param}', FakeController::class, 'nonScalarParam');

        foreach (range(1, 2) as $ignored) {
            try {
                self::resolve($route, '/expected/value');
                self::fail('Expected UnsupportedParamTypeException');
            } catch (UnsupportedParamTypeException $exception) {
                self::assertSame("Unsupported param type 'array'. Must be a scalar or a backed enum.", $exception->getMessage());
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolve(Route $route, string $uri): array
    {
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $_SERVER['REQUEST_URI'] = $uri;

        return (new RouteParams($route, Request::fromGlobals()))->getAll();
    }

    /**
     * @return array<string, mixed>
     */
    private static function actionParamsCache(): array
    {
        /** @var array<string, mixed> $cache */
        $cache = self::cacheProperty()->getValue();

        return $cache;
    }

    private static function clearActionParamsCache(): void
    {
        self::cacheProperty()->setValue(null, []);
    }

    /**
     * @param list<array{name: string, type: string, enumBacking: string|null}> $actionParams
     */
    private static function seedActionParamsCache(string $key, array $actionParams): void
    {
        self::cacheProperty()->setValue(null, [$key => $actionParams]);
    }

    private static function cacheProperty(): ReflectionProperty
    {
        return new ReflectionProperty(RouteParams::class, 'actionParamsCache');
    }
}
