<?php

declare(strict_types=1);

namespace Gacela\Router\Middleware;

use Closure;
use Gacela\Router\Entities\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next): string;
}
