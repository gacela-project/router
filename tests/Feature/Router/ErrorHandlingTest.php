<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use GacelaTest\Feature\HeaderTestCase;

final class ErrorHandlingTest extends HeaderTestCase
{
    public function test_not_redirect_non_registered_method(): void
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
