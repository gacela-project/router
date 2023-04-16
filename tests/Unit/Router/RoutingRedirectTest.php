<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router;

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
    }

    /**
     * @dataProvider provideSimpleRedirect
     */
    public function test_default_redirect(string $destination): void
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
     * @dataProvider provideSimpleRedirect
     */
    public function test_simple_redirect(string $destination, int $statusCode): void
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

    public function provideSimpleRedirect(): iterable
    {
        yield ['https://gacela-project.com/', 301];
        yield ['https://chemaclass.com/', 308];
        yield ['https://katarn.es/', 302];
        yield ['https://jesusvalera.github.io/', 303];
        yield ['https://github.com/ImanolRP', 307];
    }
}
