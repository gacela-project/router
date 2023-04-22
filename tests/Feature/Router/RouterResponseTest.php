<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Entities\JsonResponse;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Response;
use Gacela\Router\Router;
use Gacela\Router\Routes;
use GacelaTest\Feature\HeaderTestCase;

final class RouterResponseTest extends HeaderTestCase
{
    public function test_string_response(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Routes $routes): void {
            $routes->get('uri', static fn () => new Response('body'));
        });

        $this->expectOutputString('body');

        self::assertSame([], $this->headers());
    }

    public function test_json_response(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Routes $routes): void {
            $routes->get('uri', static fn () => new JsonResponse([
                'key' => 'value',
            ]));
        });

        $this->expectOutputString('{"key":"value"}');

        self::assertSame([
            [
                'header' => 'Content-Type: application/json',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function test_response_headers(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Routes $routes): void {
            $routes->get('uri', static fn () => new Response('{"key":"value"}', [
                'Access-Control-Allow-Origin: *',
                'Content-Type: application/json',
            ]));
        });

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
