<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class ArrayResponseTest extends HeaderTestCase
{
    public function test_an_array_return_is_encoded_as_json(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('uri', static fn (): array => ['a' => 1]);
        });
        $router->run();

        $this->expectOutputString('{"a":1}');

        self::assertSame([
            ['header' => 'Content-Type: application/json', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    /**
     * @param array<mixed> $given
     */
    #[DataProvider('arrayShapeProvider')]
    public function test_array_shapes_are_encoded_as_json(array $given, string $expected): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes) use ($given): void {
            $routes->get('uri', static fn (): array => $given);
        });
        $router->run();

        $this->expectOutputString($expected);
    }

    public static function arrayShapeProvider(): Generator
    {
        yield 'empty' => [[], '[]'];
        yield 'list' => [[1, 2, 3], '[1,2,3]'];
        yield 'nested' => [['a' => ['b' => 'c']], '{"a":{"b":"c"}}'];
        yield 'mixed types' => [
            ['s' => 'x', 'i' => 1, 'f' => 1.5, 'b' => true, 'n' => null],
            '{"s":"x","i":1,"f":1.5,"b":true,"n":null}',
        ];
    }

    public function test_an_array_from_a_controller_action_is_encoded_as_json(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('uri', new class() {
                /**
                 * @return array<string, string>
                 */
                public function __invoke(): array
                {
                    return ['from' => 'object controller'];
                }
            });
        });
        $router->run();

        $this->expectOutputString('{"from":"object controller"}');

        self::assertSame([
            ['header' => 'Content-Type: application/json', 'replace' => true, 'response_code' => 0],
        ], $this->headers());
    }

    public function test_an_array_carrying_a_path_param_is_encoded_as_json(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/users/7';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('users/{id}', new class() {
                /**
                 * @return array<string, int>
                 */
                public function __invoke(int $id): array
                {
                    return ['id' => $id];
                }
            });
        });
        $router->run();

        $this->expectOutputString('{"id":7}');
    }

    public function test_a_string_return_is_untouched(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('uri', static fn (): string => 'plain');
        });
        $router->run();

        $this->expectOutputString('plain');

        // No JSON content type is invented for a string.
        self::assertSame([], $this->headers());
    }

}
