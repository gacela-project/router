<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Entities\JsonResponse;
use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use Gacela\Router\Routes;
use GacelaTest\Feature\HeaderTestCase;

final class RouterJsonResponseTest extends HeaderTestCase
{
    public function test_simple_redirect(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/optional/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        Router::configure(static function (Routes $routes): void {
            $routes->get('optional/uri', static fn () => new JsonResponse([
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
}
