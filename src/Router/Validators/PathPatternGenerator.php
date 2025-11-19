<?php

declare(strict_types=1);

namespace Gacela\Router\Validators;

use Gacela\Router\Entities\RouteParams;

final class PathPatternGenerator
{
    public static function generate(string $path): string
    {
        if ($path === '') {
            return '#^/?$#';
        }

        $parts = explode('/', $path);
        $pattern = '';

        foreach ($parts as $part) {
            if (preg_match(RouteParams::MANDATORY_PARAM_PATTERN, $part)) {
                $pattern .= '/([^\/]+)';
            } elseif (preg_match(RouteParams::OPTIONAL_PARAM_PATTERN, $part)) {
                $pattern .= '/?([^\/]+)?';
            } else {
                $pattern .= '/' . $part;
            }
        }

        return '#^/' . ltrim($pattern, '/') . '$#';
    }
}
