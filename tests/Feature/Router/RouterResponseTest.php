<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\JsonResponse;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Response;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class RouterResponseTest extends HeaderTestCase
{
    public function test_string_response(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('uri', static fn () => new Response('body'));
        });
        $router->run();

        $this->expectOutputString('body');

        self::assertSame([], $this->headers());
    }

    public function test_json_response(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('uri', static fn () => new JsonResponse([
                'key' => 'value',
            ]));
        });
        $router->run();

        $this->expectOutputString('{"key":"value"}');

        self::assertSame([
            [
                'header' => 'Content-Type: application/json',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    /**
     * @param list<string> $given
     * @param list<string> $expected
     */
    #[DataProvider('callerContentTypeProvider')]
    public function test_json_response_keeps_the_callers_content_type(array $given, array $expected): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes) use ($given): void {
            $routes->get('uri', static fn () => new JsonResponse(['key' => 'value'], $given));
        });
        $router->run();

        $this->expectOutputString('{"key":"value"}');

        self::assertSame($expected, array_column($this->headers(), 'header'));
    }

    public static function callerContentTypeProvider(): Generator
    {
        yield 'lowercase name with a charset' => [
            ['content-type: application/json; charset=utf-8'],
            ['content-type: application/json; charset=utf-8'],
        ];
        yield 'uppercase name, another type' => [
            ['CONTENT-TYPE: text/plain'],
            ['CONTENT-TYPE: text/plain'],
        ];
        yield 'leading space before the name' => [
            [' Content-Type: application/problem+json'],
            [' Content-Type: application/problem+json'],
        ];
        yield 'other headers only' => [
            ['Cache-Control: no-store'],
            ['Cache-Control: no-store', 'Content-Type: application/json'],
        ];
        yield 'a header that only mentions content-type' => [
            ['X-Content-Type-Options: nosniff'],
            ['X-Content-Type-Options: nosniff', 'Content-Type: application/json'],
        ];
    }

    public function test_response_headers(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('uri', static fn () => new Response('{"key":"value"}', [
                'Access-Control-Allow-Origin: *',
                'Content-Type: application/json',
            ]));
        });
        $router->run();

        $this->expectOutputString('{"key":"value"}');

        self::assertSame([
            [
                'header' => 'Access-Control-Allow-Origin: *',
                'replace' => true,
                'response_code' => 0,
            ],
            [
                'header' => 'Content-Type: application/json',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function test_json_response_headers(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes): void {
            $routes->get('uri', static fn () => new JsonResponse(['key' => 'value'], [
                'Access-Control-Allow-Origin: *',
                'Content-Type: application/json',
            ]));
        });
        $router->run();

        $this->expectOutputString('{"key":"value"}');

        self::assertSame([
            [
                'header' => 'Access-Control-Allow-Origin: *',
                'replace' => true,
                'response_code' => 0,
            ],
            [
                'header' => 'Content-Type: application/json',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }
}
