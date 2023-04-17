<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router;

use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use Gacela\Router\Routes;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/Fake/header.php';

final class RouterRedirectTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    protected function setUp(): void
    {
        global $testHeaders;

        $testHeaders = null;
    }

    /**
     * @dataProvider destinationProvider
     */
    public function test_simple_redirect(string $destination): void
    {
        global $testHeaders;

        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Routes $routes) use ($destination): void {
            $routes->redirect('optional/uri', $destination);
        });

        self::assertSame([
            [
                'header' => 'Location: ' . $destination,
                'replace' => true,
                'response_code' => 302,
            ],
        ], $testHeaders);
    }

    /**
     * @dataProvider destinationProvider
     */
    public function test_redirect_with_status_code(string $destination, int $statusCode): void
    {
        global $testHeaders;

        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Routes $routes) use ($destination, $statusCode): void {
            $routes->redirect('optional/uri', $destination, $statusCode);
        });

        self::assertSame([
            [
                'header' => 'Location: ' . $destination,
                'replace' => true,
                'response_code' => $statusCode,
            ],
        ], $testHeaders);
    }

    /**
     * @dataProvider destinationProvider
     */
    public function test_redirect_with_custom_method(string $destination, int $statusCode, string $method): void
    {
        global $testHeaders;

        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = $method;

        Router::configure(
            static function (Routes $routes) use ($destination, $statusCode, $method): void {
                $routes->redirect('optional/uri', $destination, $statusCode, $method);
            },
        );

        self::assertSame([
            [
                'header' => 'Location: ' . $destination,
                'replace' => true,
                'response_code' => $statusCode,
            ],
        ], $testHeaders);
    }

    /**
     * @dataProvider destinationProvider
     */
    public function test_not_redirect_non_registered_method(string $destination, int $statusCode, string $method): void
    {
        global $testHeaders;

        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_OPTIONS;

        Router::configure(
            static function (Routes $routes) use ($destination, $statusCode, $method): void {
                $routes->redirect('optional/uri', $destination, $statusCode, $method);
            },
        );

        self::assertSame([
            [
                'header' => 'HTTP/1.0 404 Not Found',
                'replace' => true,
                'response_code' => 0,
            ],
        ], $testHeaders);
    }

    public function destinationProvider(): iterable
    {
        yield ['https://gacela-project.com/', 301, Request::METHOD_GET];
        yield ['https://chemaclass.com/', 308, Request::METHOD_POST];
        yield ['https://katarn.es/', 302, Request::METHOD_PUT];
        yield ['https://jesusvalera.github.io/', 303, Request::METHOD_DELETE];
        yield ['https://github.com/ImanolRP', 307, Request::METHOD_PATCH];
    }
}
