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

    public function test_simple_redirect(): void
    {
        global $testHeaders;

        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->redirect('optional/uri', 'https://gacela-project.com/');
        });

        self::assertSame([
            [
                'header' => 'Location: https://gacela-project.com/',
                'replace' => true,
                'response_code' => 302,
            ],
        ], $testHeaders);
    }

//    public function test_redirect_different_method(): void
//    {
//        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
//        $_SERVER['REQUEST_METHOD'] = 'GET';
//
//        $this->expectOutputString('');
//
//        Routing::configure(static function (RoutingConfigurator $routes): void {
//            $routes->redirect('optional/uri', 'expected/uri', 'POST');
//            $routes->get('expected/uri', FakeController::class, 'basicAction');
//        });
//    }
//
//    public function test_redirect_different_method_get_by_default(): void
//    {
//        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
//        $_SERVER['REQUEST_METHOD'] = 'POST';
//
//        $this->expectOutputString('');
//
//        Routing::configure(static function (RoutingConfigurator $routes): void {
//            $routes->redirect('optional/uri', 'expected/uri');
//            $routes->get('expected/uri', FakeController::class, 'basicAction');
//        });
//    }
//
//    /**
//     * @dataProvider anyHttpMethodProvider
//     */
//    public function test_redirect_any_method(string $method): void
//    {
//        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
//        $_SERVER['REQUEST_METHOD'] = $method;
//
//        $this->expectOutputString('Expected!');
//
//        Routing::configure(static function (RoutingConfigurator $routes): void {
//            $routes->redirect('optional/uri', 'expected/uri');
//            $routes->any('expected/uri', FakeController::class, 'basicAction');
//        });
//    }
}
