<?php

declare(strict_types=1);

namespace Gacela\Router\Middleware;

use Closure;
use Gacela\Container\Container;
use Gacela\Router\Entities\Request;

use function is_string;

/**
 * @psalm-import-type ResolvedMiddleware from MiddlewareInterface
 *
 * @phpstan-import-type ResolvedMiddleware from MiddlewareInterface
 */
final class MiddlewarePipeline
{
    /**
     * @param list<ResolvedMiddleware> $middlewares
     */
    public function __construct(
        private readonly array $middlewares = [],
    ) {
    }

    public function handle(Request $request, Closure $finalHandler): string
    {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         *
         * @var Closure(Request): string $pipeline
         */
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            $this->carry(),
            $finalHandler,
        );

        return $pipeline($request);
    }

    private function carry(): Closure
    {
        return static function (Closure $next, MiddlewareInterface|string $middleware): Closure {
            return static function (Request $request) use ($next, $middleware): string {
                if (is_string($middleware)) {
                    /** @var class-string<MiddlewareInterface> $middleware */
                    /** @var MiddlewareInterface $instance */
                    $instance = Container::create($middleware);
                } else {
                    $instance = $middleware;
                }

                return $instance->handle($request, $next);
            };
        };
    }
}
