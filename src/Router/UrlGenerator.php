<?php

declare(strict_types=1);

namespace Gacela\Router;

use BackedEnum;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Route;
use Gacela\Router\Exceptions\UrlGenerationException;

use function is_bool;
use function is_scalar;

final class UrlGenerator
{
    /** @var array<string, Route>|null */
    private ?array $named = null;

    public function __construct(
        private readonly Routes $routes,
    ) {
    }

    /**
     * Build the url of a named route, filling its {params} from $params.
     *
     * @param array<string, mixed> $params
     */
    public function generate(string $name, array $params = []): string
    {
        $route = $this->named()[$name] ?? throw UrlGenerationException::unknownName($name);

        $segments = [];

        foreach (explode('/', $route->path()) as $segment) {
            $param = self::paramNameOf($segment);

            if ($param === null) {
                $segments[] = $segment;
                continue;
            }

            if (!isset($params[$param])) {
                if (!str_ends_with($segment, '?}')) {
                    throw UrlGenerationException::missingParam($name, $param);
                }

                // Optionals are always trailing, so the url stops here rather
                // than leaving a hole for a later one to fill.
                break;
            }

            $segments[] = self::stringify($name, $param, $params[$param]);
        }

        // The root path is stored as '', which explodes to a single empty
        // segment and so already yields '/'.
        return '/' . implode('/', $segments);
    }

    /**
     * Names are indexed on first use: a route is named after Routes hands it
     * back, so the map can only be complete once configuration has finished.
     *
     * @return array<string, Route>
     */
    private function named(): array
    {
        if ($this->named !== null) {
            return $this->named;
        }

        $named = [];

        foreach ($this->routes->getAllRoutes() as $route) {
            $name = $route->getName();

            if ($name === null) {
                continue;
            }

            if (isset($named[$name])) {
                throw UrlGenerationException::duplicateName($name);
            }

            $named[$name] = $route;
        }

        return $this->named = $named;
    }

    /**
     * '{id}' and '{id?}' yield 'id'; a literal segment yields null.
     */
    private static function paramNameOf(string $segment): ?string
    {
        if (!str_starts_with($segment, '{') || !str_ends_with($segment, '}')) {
            return null;
        }

        return trim($segment, '{?}');
    }

    private static function stringify(string $name, string $param, mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string)$value->value;
        }

        if (!is_scalar($value) || is_bool($value)) {
            throw UrlGenerationException::unsupportedParamType($name, $param, get_debug_type($value));
        }

        return (string)$value;
    }
}
