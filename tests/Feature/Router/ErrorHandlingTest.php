<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use Gacela\Router\Routes;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use Generator;

final class ErrorHandlingTest extends HeaderTestCase
{
    public function test_respond_404_status_when_uri_does_not_match(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_OPTIONS;

        Router::configure(static function (): void {
        });

        self::assertSame([
            [
                'header' => 'HTTP/1.0 404 Not Found',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function test_respond_404_status_when_method_does_not_match(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Router::configure(static function (Routes $routes): void {
            $routes->post('expected/uri', FakeController::class, 'basicAction');
        });

        self::assertSame([
            [
                'header' => 'HTTP/1.0 404 Not Found',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    /**
     * @dataProvider notMatchesMethodsProvider
     */
    public function test_respond_404_status_when_not_matches_match_methods(string $testMethod, array $givenMethods): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = $testMethod;

        Router::configure(static function (Routes $routes) use ($givenMethods): void {
            $routes->match($givenMethods, 'expected/uri', FakeController::class, 'basicAction');
        });

        self::assertSame([
            [
                'header' => 'HTTP/1.0 404 Not Found',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $this->headers());
    }

    public function notMatchesMethodsProvider(): Generator
    {
        yield [Request::METHOD_PUT, [Request::METHOD_GET, Request::METHOD_POST]];
        yield [Request::METHOD_OPTIONS, [Request::METHOD_GET, Request::METHOD_POST]];
        yield [Request::METHOD_GET, [Request::METHOD_PATCH, Request::METHOD_PUT, Request::METHOD_DELETE, Request::METHOD_POST]];
        yield [Request::METHOD_CONNECT, [
            Request::METHOD_GET, Request::METHOD_DELETE, Request::METHOD_HEAD, Request::METHOD_OPTIONS,
            Request::METHOD_PATCH, Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_TRACE,
        ]];
    }
}
