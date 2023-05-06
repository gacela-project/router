<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;

final class RouterRedirectTest extends HeaderTestCase
{
    /**
     * @dataProvider destinationProvider
     */
    public function test_simple_redirect(string $destination): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes) use ($destination): void {
            $routes->redirect('optional/uri', $destination);
        });
        $router->run();

        self::assertSame([
            [
                'header' => 'Location: ' . $destination,
                'replace' => true,
                'response_code' => 302,
            ],
        ], $this->headers());
    }

    /**
     * @dataProvider destinationProvider
     */
    public function test_redirect_with_status_code(string $destination, int $statusCode): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $router = new Router(static function (Routes $routes) use ($destination, $statusCode): void {
            $routes->redirect('optional/uri', $destination, $statusCode);
        });
        $router->run();

        self::assertSame([
            [
                'header' => 'Location: ' . $destination,
                'replace' => true,
                'response_code' => $statusCode,
            ],
        ], $this->headers());
    }

    /**
     * @dataProvider destinationProvider
     */
    public function test_redirect_with_custom_method(string $destination, int $statusCode, string $method): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = $method;

        $router = new Router(
            static function (Routes $routes) use ($destination, $statusCode, $method): void {
                $routes->redirect('optional/uri', $destination, $statusCode, $method);
            },
        );
        $router->run();

        self::assertSame([
            [
                'header' => 'Location: ' . $destination,
                'replace' => true,
                'response_code' => $statusCode,
            ],
        ], $this->headers());
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
