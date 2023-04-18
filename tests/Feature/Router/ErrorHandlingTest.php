<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use Gacela\Router\Routes;
use GacelaTest\Feature\HeaderTestCase;
use GacelaTest\Feature\Router\Fixtures\FakeController;

final class ErrorHandlingTest extends HeaderTestCase
{
    public function test_match_does_not_matches_other_methods(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('');

        Router::configure(static function (Routes $routes): void {
            $routes->post('expected/uri', FakeController::class, 'basicAction');
        });
    }

    public function test_respond_status_404_when_the_uri_does_not_matches(): void
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
}
