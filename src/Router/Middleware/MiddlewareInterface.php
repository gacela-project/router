<?php

declare(strict_types=1);

namespace Gacela\Router\Middleware;

use Closure;
use Gacela\Router\Entities\Request;

/**
 * @psalm-type RawMiddleware = MiddlewareInterface|class-string<MiddlewareInterface>|string
 * @psalm-type ResolvedMiddleware = MiddlewareInterface|class-string<MiddlewareInterface>
 *
 * @phpstan-type RawMiddleware MiddlewareInterface|class-string<MiddlewareInterface>|string
 * @phpstan-type ResolvedMiddleware MiddlewareInterface|class-string<MiddlewareInterface>
 */
interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next): string;
}
