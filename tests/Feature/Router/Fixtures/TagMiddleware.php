<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use Closure;
use Gacela\Router\Entities\Request;
use Gacela\Router\Middleware\MiddlewareInterface;
use Override;

final class TagMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $tag,
    ) {
    }

    #[Override]
    public function handle(Request $request, Closure $next): string
    {
        return "[{$this->tag}]" . $next($request) . "[/{$this->tag}]";
    }
}
