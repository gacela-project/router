<?php

namespace Gacela\Router
{
    /** @var string $testHeader */
    $testHeader = '';

    function header(string $header): void {
        global $testHeader;

        $testHeader = $header;
    }
}

namespace GacelaTest\Unit\Router
{

use Gacela\Router\Routing;
use Gacela\Router\RoutingConfigurator;
use PHPUnit\Framework\TestCase;


class RoutingRedirectTest extends TestCase
{
    protected function tearDown(): void
    {
        global $testHeader;

        $testHeader = '';
    }

    /** @runInSeparateProcess */
    public function test_simple_redirect(): void
    {
        global $testHeader;
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Routing::configure(static function (RoutingConfigurator $routes): void {
            $routes->redirect('optional/uri', 'expected/uri', 'POST');
        });

        $this->assertEquals('hola', $testHeader);
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
}