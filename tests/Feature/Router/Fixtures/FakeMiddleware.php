<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use Closure;
use Gacela\Router\Entities\Request;
use Gacela\Router\Middleware\MiddlewareInterface;
use Override;

final class FakeMiddleware implements MiddlewareInterface
{
    #[Override]
    public function handle(Request $request, Closure $next): string
    {
        return '[TEST]' . $next($request) . '[/TEST]';
    }
}
