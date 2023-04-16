<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router;

use Gacela\Router\Request;
use Gacela\Router\Routing;
use Gacela\Router\RoutingConfigurator;
use GacelaTest\Unit\Router\Fixtures\HeadersTearDown;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/Fake/header.php';

class RoutingRedirectTest extends TestCase
{
    use HeadersTearDown;

    /**
     * @runInSeparateProcess
     */
    protected function setUp(): void
    {
        Request::resetCache();
    }

    /**
     * @dataProvider destinationProvider
     */
    public function test_simple_redirect(string $destination): void
    {
        global $testHeaders;

        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Routing::configure(static function (RoutingConfigurator $routes) use ($destination): void {
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
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Routing::configure(static function (RoutingConfigurator $routes) use ($destination, $statusCode): void {
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

        Routing::configure(
            static function (RoutingConfigurator $routes) use ($destination, $statusCode, $method): void {
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
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        Routing::configure(
            static function (RoutingConfigurator $routes) use ($destination, $statusCode, $method): void {
                $routes->redirect('optional/uri', $destination, $statusCode, $method);
            },
        );

        self::assertNull($testHeaders);
    }

    public function destinationProvider(): iterable
    {
        yield ['https://gacela-project.com/', 301, 'GET'];
        yield ['https://chemaclass.com/', 308, 'POST'];
        yield ['https://katarn.es/', 302, 'PUT'];
        yield ['https://jesusvalera.github.io/', 303, 'DELETE'];
        yield ['https://github.com/ImanolRP', 307, 'PATCH'];
    }
}
